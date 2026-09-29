<template>
  <Link :href="`/service/${service.slug}`" class="group card card-link overflow-hidden flex sm:flex-col">
    <div class="w-28 shrink-0 self-stretch sm:w-auto sm:aspect-[16/10] overflow-hidden s-surface-2">
      <img :src="service.image ? img(service.image, 640) : '/logo.png'" :srcset="srcset(service.image, 1024)" sizes="(min-width: 1024px) 380px, (min-width: 640px) 50vw, 100vw" :alt="service.name" loading="lazy" decoding="async" width="640" height="400" class="img-cover group-hover:scale-[1.04] transition-transform duration-500" />
    </div>
    <div class="p-3.5 sm:p-5 flex-1 min-w-0 flex flex-col">
      <h3 class="h-card !text-[15px] sm:!text-[17px] group-hover:text-[var(--s-accent-text)] transition-colors">{{ service.name }}</h3>
      <p v-if="service.short_summary" class="mt-1.5 sm:mt-2 text-[13px] sm:text-[13.5px] s-muted line-clamp-2 leading-relaxed">{{ service.short_summary }}</p>
      <div class="mt-auto pt-2.5 sm:pt-4 flex items-center justify-between gap-3">
        <span v-if="service.from_price" class="badge-price"><span class="text-[11px] font-medium s-subtle">from</span> S${{ Number(service.from_price).toLocaleString() }}</span>
        <span v-else-if="service.response_time" class="text-xs s-subtle">{{ service.response_time }}</span>
        <span v-else></span>
        <span class="text-[13px] font-semibold s-accent inline-flex items-center gap-1 group-hover:gap-2 transition-all">
          View service
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
        </span>
      </div>
    </div>
  </Link>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import { img, srcset } from '@/utils/img';

defineProps({ service: { type: Object, required: true } });
</script>
