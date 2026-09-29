<template>
  <AdminLayout title="Page banners">
    <PageHeader title="Page banners" description="The heading strip and background photo at the top of each listing page. The banner title is the page's H1, so name the topic plainly (e.g. “Renovation & repair services”)." />

    <form @submit.prevent="submit" class="space-y-4">
      <section v-for="b in banners" :key="b.key" class="admin-card overflow-hidden">
        <div class="grid grid-cols-1 md:grid-cols-[16rem_1fr]">
          <label class="a-dropzone !rounded-none !border-0 md:border-r a-border aspect-[16/7] md:aspect-auto md:min-h-[9rem]">
            <img v-if="previews[b.image] || breadcrumb?.[b.image]" :src="previews[b.image] || '/' + breadcrumb[b.image]" class="w-full h-full object-cover" alt="" />
            <span v-else class="text-xs px-4">Click to add a background photo</span>
            <input type="file" accept="image/*" class="hidden" :disabled="readOnly" @change="e => pick(b.image, e)" />
          </label>
          <div class="p-5 space-y-3">
            <div class="flex items-center justify-between gap-3">
              <h3 class="a-card-title">{{ b.label }}</h3>
              <a :href="b.path" target="_blank" class="text-xs a-mono a-subtle a-hover-text">{{ b.path }}</a>
            </div>
            <div>
              <label class="admin-label">Banner title (H1)</label>
              <input v-model="form[b.name]" type="text" class="admin-input" :placeholder="b.placeholder" :disabled="readOnly" />
            </div>
            <p class="a-help">Photo: 1920×500 or larger. Click the picture to change it.</p>
          </div>
        </div>
      </section>

      <div v-if="!readOnly" class="flex justify-end">
        <button type="submit" :disabled="form.processing" class="admin-btn-primary">{{ form.processing ? 'Saving…' : 'Save banners' }}</button>
      </div>
    </form>
  </AdminLayout>
</template>

<script setup>
import { computed, reactive } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PageHeader from '@/Components/Admin/PageHeader.vue';
import { compressImage } from '@/Composables/compressImage';
import { usePermissions } from '@/Composables/usePermissions';

const props = defineProps({ breadcrumb: Object });
const { can } = usePermissions();
const readOnly = computed(() => !can('banners.edit'));

const banners = [
  { key: 's', label: 'Services', path: '/services', name: 's_bread_name', image: 's_bread_image', placeholder: 'Renovation & repair services' },
  { key: 'p', label: 'Projects', path: '/projects', name: 'p_bread_name', image: 'p_bread_image', placeholder: 'Recent projects in Singapore' },
  { key: 'b', label: 'Articles', path: '/blogs', name: 'b_bread_name', image: 'b_bread_image', placeholder: 'Guides & cost advice' },
  { key: 'f', label: 'Reviews', path: '/reviews', name: 'f_bread_name', image: 'f_bread_image', placeholder: 'Customer reviews' },
  { key: 'c', label: 'Contact', path: '/contact', name: 'c_bread_name', image: 'c_bread_image', placeholder: 'Contact us' },
];

const previews = reactive({});
const form = useForm(Object.fromEntries(banners.flatMap(b => [[b.name, props.breadcrumb?.[b.name] || ''], [b.image, null]])));

async function pick(field, e) {
  const file = await compressImage(e.target.files[0], { maxSide: 2400 });
  if (file) { form[field] = file; previews[field] = URL.createObjectURL(file); }
}

function submit() {
  form.post('/admin/breadcrumbs', { forceFormData: true, preserveScroll: true });
}
</script>
