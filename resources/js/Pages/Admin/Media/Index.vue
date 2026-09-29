<template>
  <AdminLayout title="Media Library">
    <PageHeader title="Media library" description="Every uploaded image and file. Files used on any page, logo, banner or inside article text are locked and cannot be deleted; remove them from the page first.">
      <button @click="go({}, true)" class="admin-btn-secondary">Re-scan</button>
      <label v-if="can('media.create')" class="admin-btn-primary cursor-pointer">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" /></svg>
        {{ uploading ? 'Uploading…' : 'Upload' }}
        <input type="file" multiple accept="image/jpeg,image/png,image/webp,image/gif,image/avif,application/pdf" class="hidden" @change="upload" :disabled="uploading" />
      </label>
    </PageHeader>

    <StickyBar>
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
      <div class="admin-card a-stat">
        <p class="a-stat-label">Files</p>
        <p class="a-stat-value">{{ summary.total }}</p>
        <p class="a-stat-hint">{{ formatSize(summary.total_size) }}</p>
      </div>
      <button @click="go({ status: 'used' })" class="admin-card a-stat text-left a-hover">
        <p class="a-stat-label">In use (locked)</p>
        <p class="a-stat-value a-text-success">{{ summary.total - summary.unused }}</p>
      </button>
      <button @click="go({ status: 'unused' })" class="admin-card a-stat text-left a-hover">
        <p class="a-stat-label">Not used anywhere</p>
        <p class="a-stat-value a-text-warning">{{ summary.unused }}</p>
        <p class="a-stat-hint">{{ formatSize(summary.unused_size) }} can be freed</p>
      </button>
      <div class="admin-card a-stat" title="Pages point to image files that do not exist on the server">
        <p class="a-stat-label">Broken image links</p>
        <p :class="['a-stat-value', summary.missing && 'a-text-danger']">{{ summary.missing }}</p>
      </div>
    </div>
    </StickyBar>

    <p v-if="uploadError" class="a-alert a-alert-danger mb-4">{{ uploadError }}</p>

    <div class="admin-card overflow-hidden">
      <div class="p-4 border-b a-border flex flex-col lg:flex-row gap-2">
        <div class="a-seg self-start">
          <button v-for="st in statusTabs" :key="st.key" @click="go({ status: st.key })" :class="(filters.status || '') === st.key && 'is-on'">{{ st.label }}</button>
        </div>
        <SelectBox :model-value="filters.folder || ''" @update:model-value="v => go({ folder: v })" class="admin-input lg:max-w-xs">
          <option value="">All folders</option>
          <option v-for="f in folders" :key="f" :value="f">{{ f }}</option>
        </SelectBox>
        <SelectBox :model-value="filters.sort || ''" @update:model-value="v => go({ sort: v })" class="admin-input lg:max-w-[11rem]">
          <option value="">Newest first</option>
          <option value="size">Largest first</option>
        </SelectBox>
        <form @submit.prevent="go({ search })" class="lg:ml-auto">
          <input v-model="search" type="search" placeholder="Search file name…" class="admin-input lg:w-64" />
        </form>
      </div>

      <div v-if="can('media.delete') && (selected.length || unusedOnPage.length)" class="px-4 py-2.5 border-b a-border flex flex-wrap items-center gap-3 text-sm" :class="selected.length && 'a-tint-danger'">
        <button v-if="unusedOnPage.length" @click="selectAllUnused" class="font-semibold a-accent">Select all unused on this page ({{ unusedOnPage.length }})</button>
        <template v-if="selected.length">
          <span class="a-muted">{{ selected.length }} selected</span>
          <button @click="deleteSelected" class="a-btn-danger a-btn-sm">Delete selected</button>
          <button @click="selected = []" class="a-btn-ghost a-btn-sm">Clear</button>
        </template>
      </div>

      <div class="p-4 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-6 gap-3">
        <div v-for="f in files.data" :key="f.path"
          :class="['group relative rounded-xl border overflow-hidden a-panel transition', selected.includes(f.path) ? 'border-[var(--a-danger)] ring-2 ring-[var(--a-danger-soft)]' : 'a-border hover:border-[var(--a-border-2)]']">
          <button type="button" @click="detail = f" class="block w-full aspect-square a-panel-3">
            <img v-if="isImage(f)" :src="f.url" :alt="f.alt || ''" loading="lazy" class="w-full h-full object-cover" />
            <span v-else class="w-full h-full flex items-center justify-center text-xs font-bold a-muted uppercase">{{ f.ext }}</span>
          </button>
          <label v-if="!f.usage_count && can('media.delete')" class="absolute top-2 left-2 rounded-md p-1 cursor-pointer" style="background: var(--a-panel)" title="Select for deletion">
            <input v-model="selected" type="checkbox" :value="f.path" class="block" />
          </label>
          <span :class="['absolute top-2 right-2 a-badge', f.usage_count ? 'a-badge-success' : 'a-badge-warning']" style="backdrop-filter: blur(4px)">
            {{ f.usage_count ? `In use · ${f.usage_count}` : 'Unused' }}
          </span>
          <div class="px-2.5 py-2">
            <div class="text-xs font-semibold truncate" :title="f.name">{{ f.name }}</div>
            <div class="text-[11px] a-subtle flex justify-between">
              <span :class="f.size > 500000 && 'a-text-warning'">{{ formatSize(f.size) }}</span>
              <span v-if="!f.alt && isImage(f)" class="a-text-warning">no alt</span>
            </div>
          </div>
        </div>
      </div>
      <div v-if="!files.data.length" class="a-empty">
        <p class="font-semibold">No files match</p>
      </div>
      <div class="px-4 pb-4"><Pagination :meta="files" /></div>
    </div>

    <Modal :show="!!detail" :title="detail?.name" :subtitle="detail?.folder" width="4xl" @close="detail = null">
      <div v-if="detail" class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="a-panel-2 rounded-xl flex items-center justify-center min-h-[16rem] overflow-hidden">
          <img v-if="isImage(detail)" :src="detail.url" :alt="detail.alt || ''" class="max-h-[26rem] w-auto" />
          <a v-else :href="detail.url" target="_blank" class="text-sm font-semibold underline">Open {{ detail.ext.toUpperCase() }}</a>
        </div>
        <div class="space-y-4 text-sm">
          <dl class="grid grid-cols-2 gap-3">
            <div><dt class="text-xs a-subtle">Size</dt><dd class="font-semibold" :class="detail.size > 500000 ? 'a-text-warning' : ''">{{ formatSize(detail.size) }}</dd></div>
            <div><dt class="text-xs a-subtle">Dimensions</dt><dd class="font-semibold">{{ detail.width ? `${detail.width}×${detail.height}` : '—' }}</dd></div>
            <div class="col-span-2"><dt class="text-xs a-subtle">Modified</dt><dd class="font-semibold">{{ new Date(detail.modified).toLocaleString('en-SG') }}</dd></div>
          </dl>
          <p v-if="detail.size > 500000" class="text-xs a-text-warning">Over 500 KB. Compress or convert to WebP before re-uploading; big images slow the page (LCP).</p>

          <div>
            <label class="admin-label">URL</label>
            <div class="flex gap-2">
              <input :value="detail.url" readonly class="admin-input a-mono text-xs" @focus="e => e.target.select()" />
              <button type="button" @click="copy(detail.url)" class="admin-btn-secondary">{{ copied ? 'Copied' : 'Copy' }}</button>
            </div>
          </div>

          <form v-if="isImage(detail) && can('media.edit')" @submit.prevent="saveAlt" class="space-y-2">
            <label class="admin-label">Default alt text</label>
            <div class="flex gap-2">
              <input v-model="altForm.alt" type="text" maxlength="255" class="admin-input" placeholder="Describe the photo" />
              <button type="submit" class="admin-btn-primary">Save</button>
            </div>
            <p class="a-help">Suggested when this image is inserted into a page. Describe what is in the photo, e.g. “Technician replacing a water heater in an HDB bathroom”.</p>
          </form>

          <div>
            <p class="admin-label">Used in</p>
            <ul v-if="detail.usages.length" class="space-y-1">
              <li v-for="(u, i) in detail.usages" :key="i">
                <Link :href="u.url" class="text-sm hover:underline"><span class="a-subtle">{{ u.label }}:</span> {{ u.title }} <span class="text-xs a-subtle">({{ u.field }})</span></Link>
              </li>
              <li v-if="detail.usage_count > detail.usages.length" class="text-xs a-muted">…and {{ detail.usage_count - detail.usages.length }} more</li>
            </ul>
            <p v-else class="text-sm a-text-warning">Not used anywhere.</p>
          </div>

          <div class="pt-3 border-t a-border">
            <button v-if="!detail.usage_count && can('media.delete')" type="button" @click="deleteOne(detail)" class="a-btn-danger">Delete file permanently</button>
            <p v-else-if="detail.usage_count" class="a-alert a-alert-info text-xs">Locked: this file is in use. Replace or remove it on the pages above first.</p>
          </div>
        </div>
      </div>
    </Modal>
  </AdminLayout>
</template>

<script setup>
import StickyBar from '@/Components/Admin/StickyBar.vue';
import SelectBox from '@/Components/SelectBox.vue';
import { confirmDialog } from '@/Composables/useConfirm';
import { computed, ref, watch } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import { compressImage } from '@/Composables/compressImage';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Modal from '@/Components/Admin/Modal.vue';
import Pagination from '@/Components/Admin/Pagination.vue';
import PageHeader from '@/Components/Admin/PageHeader.vue';
import { usePermissions } from '@/Composables/usePermissions';

const { can } = usePermissions();

const props = defineProps({
  files: Object,
  summary: Object,
  folders: Array,
  filters: Object,
});

const statusTabs = [
  { key: '', label: 'All' },
  { key: 'used', label: 'In use' },
  { key: 'unused', label: 'Unused' },
];

const search = ref(props.filters?.search || '');
const selected = ref([]);
const detail = ref(null);
const uploading = ref(false);
const uploadError = ref('');
const copied = ref(false);

const unusedOnPage = computed(() => props.files.data.filter(f => !f.usage_count).map(f => f.path));

function go(patch, refresh = false) {
  const params = { ...props.filters, ...patch };
  Object.keys(params).forEach(k => { if (!params[k]) delete params[k]; });
  if (refresh) params.refresh = 1;
  selected.value = [];
  router.get('/admin/media', params, { preserveState: true, preserveScroll: true });
}

const isImage = f => ['jpg', 'jpeg', 'png', 'webp', 'gif', 'avif', 'svg'].includes(f.ext);

function formatSize(bytes) {
  if (!bytes) return '0 KB';
  if (bytes > 1048576) return (bytes / 1048576).toFixed(1) + ' MB';
  return Math.max(1, Math.round(bytes / 1024)) + ' KB';
}

function selectAllUnused() {
  selected.value = [...new Set([...selected.value, ...unusedOnPage.value])];
}

function destroy(paths) {
  router.post('/admin/media/delete', { paths }, {
    preserveScroll: true,
    onSuccess: () => { selected.value = []; detail.value = null; },
  });
}

async function deleteSelected() {
  if (await confirmDialog({ title: `Delete ${selected.value.length} unused file(s)?`, message: 'They are not used on any page. This cannot be undone.', confirmText: 'Delete files' })) destroy(selected.value);
}

async function deleteOne(f) {
  if (await confirmDialog({ title: `Delete ${f.name}?`, message: 'It is not used on any page. This cannot be undone.', confirmText: 'Delete file' })) destroy([f.path]);
}

async function upload(e) {
  const list = [...e.target.files];
  if (!list.length) return;
  uploading.value = true;
  uploadError.value = '';
  const fd = new FormData();
  for (const file of list) fd.append('files[]', await compressImage(file));
  try {
    await axios.post('/admin/media', fd, { headers: { Accept: 'application/json' } });
    router.reload({ preserveScroll: true });
  } catch (err) {
    uploadError.value = err.response?.data?.errors ? Object.values(err.response.data.errors).flat()[0] : (err.response?.data?.message || 'Upload failed.');
  } finally {
    uploading.value = false;
    e.target.value = '';
  }
}

const altForm = useForm({ path: '', alt: '' });
watch(detail, (f) => {
  if (f) { altForm.path = f.path; altForm.alt = f.alt || ''; copied.value = false; }
});
function saveAlt() {
  altForm.post('/admin/media/alt', { preserveScroll: true, onSuccess: () => { if (detail.value) detail.value.alt = altForm.alt; } });
}

async function copy(url) {
  try {
    await navigator.clipboard.writeText(window.location.origin + url);
    copied.value = true;
  } catch (e) { /* clipboard blocked */ }
}
</script>
