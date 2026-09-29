<template>
  <AdminLayout title="Website checklist">
    <PageHeader title="Website checklist" description="Everything the public website needs, checked live against your data. Work from the red items down; each one links to where you fix it and the page where it shows." />

    <section class="admin-card p-5 mb-5 flex flex-wrap items-center gap-5">
      <div class="relative w-16 h-16 shrink-0">
        <svg viewBox="0 0 36 36" class="w-16 h-16 -rotate-90"><circle cx="18" cy="18" r="15.5" fill="none" stroke="var(--a-panel-3)" stroke-width="4" /><circle cx="18" cy="18" r="15.5" fill="none" stroke="var(--a-accent)" stroke-width="4" stroke-linecap="round" :stroke-dasharray="`${pct * 0.974} 100`" /></svg>
        <span class="absolute inset-0 grid place-items-center text-sm font-bold">{{ pct }}%</span>
      </div>
      <div class="flex-1 min-w-0">
        <p class="text-base font-bold">{{ done }} of {{ total }} done</p>
        <p class="text-sm a-muted">{{ mustOpen ? `${mustOpen} important item${mustOpen > 1 ? 's' : ''} left` : 'All important items are done.' }}</p>
      </div>
      <div class="a-seg">
        <button type="button" :class="!showDone && 'is-on'" @click="showDone = false">To do</button>
        <button type="button" :class="showDone && 'is-on'" @click="showDone = true">All</button>
      </div>
    </section>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-5">
      <section v-for="g in visible" :key="g.group" class="admin-card overflow-hidden h-fit">
        <header class="a-card-head">
          <h3 class="a-card-title">{{ g.group }}</h3>
          <span class="text-xs a-subtle">{{ g.items.filter(i => i.ok).length }}/{{ g.items.length }}</span>
        </header>
        <ul class="divide-y a-divide">
          <li v-for="i in g.shown" :key="i.key" class="px-5 py-3.5 flex items-start gap-3">
            <span :class="['mt-0.5 w-5 h-5 shrink-0 rounded-full grid place-items-center text-[11px] font-bold text-white', i.ok ? 'bg-emerald-600' : i.level === 'must' ? 'bg-red-600' : 'bg-amber-500']">{{ i.ok ? '✓' : '!' }}</span>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-semibold">{{ i.label }} <span v-if="!i.ok && i.level === 'must'" class="a-badge a-badge-danger ml-1 !text-[10px]">Important</span></p>
              <p class="text-xs a-muted mt-0.5 leading-relaxed">{{ i.detail }}</p>
            </div>
            <div class="flex gap-1.5 shrink-0">
              <a v-if="i.view" :href="i.view" target="_blank" class="admin-btn-secondary !py-1 !px-2 !text-[11px]" title="See it on the website">View ↗</a>
              <Link v-if="i.fix && !i.ok" :href="i.fix" class="admin-btn-primary !py-1 !px-2.5 !text-[11px]">Fix</Link>
            </div>
          </li>
          <li v-if="!g.shown.length" class="px-5 py-4 text-sm a-muted">All done here.</li>
        </ul>
      </section>
    </div>
  </AdminLayout>
</template>

<script setup>
import { computed, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PageHeader from '@/Components/Admin/PageHeader.vue';

const props = defineProps({ groups: Array });
const showDone = ref(false);
const all = computed(() => props.groups.flatMap((g) => g.items));
const done = computed(() => all.value.filter((i) => i.ok).length);
const total = computed(() => all.value.length);
const mustOpen = computed(() => all.value.filter((i) => !i.ok && i.level === 'must').length);
const pct = computed(() => (total.value ? Math.round((done.value / total.value) * 100) : 0));
const rank = (i) => (i.ok ? 2 : i.level === 'must' ? 0 : 1);
const visible = computed(() => props.groups
  .map((g) => ({ ...g, shown: [...g.items].filter((i) => showDone.value || !i.ok).sort((a, b) => rank(a) - rank(b)) }))
  .filter((g) => showDone.value || g.shown.length));
</script>
