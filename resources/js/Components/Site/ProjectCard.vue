<template>
  <figure class="group card overflow-hidden flex flex-col">
    <div class="relative aspect-[4/3] overflow-hidden s-surface-2">
      <img :src="project.image ? img(project.image, 640) : '/logo.png'" :srcset="srcset(project.image, 1024)" sizes="(min-width: 1024px) 380px, (min-width: 640px) 50vw, 100vw" :alt="project.name" loading="lazy" decoding="async" width="640" height="480" class="img-cover group-hover:scale-[1.04] transition-transform duration-500" />
      <a v-if="project.video" :href="project.video" target="_blank" rel="noopener" class="absolute top-3 right-3 chip !bg-black/60 !text-white !border-white/20 backdrop-blur">▶ Video</a>
    </div>
    <figcaption class="p-4 flex-1 flex flex-col">
      <p class="text-[14.5px] font-semibold s-heading leading-snug line-clamp-2">{{ project.name }}</p>
      <p v-if="details" class="mt-1 text-[12.5px] s-subtle">{{ details }}</p>
      <p v-if="project.summary" class="mt-2 text-[13px] s-muted line-clamp-3 leading-relaxed">{{ project.summary }}</p>
      <Link v-if="project.service && showService" :href="`/service/${project.service.slug}`" class="mt-auto pt-3 text-[12.5px] link">{{ project.service.name }} →</Link>
    </figcaption>
  </figure>
</template>

<script setup>
import { monthYear } from '@/utils/fmt';
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { img, srcset } from '@/utils/img';

const props = defineProps({ project: { type: Object, required: true }, showService: { type: Boolean, default: true } });
const details = computed(() => [
  props.project.area || props.project.location?.name,
  props.project.property_type,
  props.project.completed_on && monthYear(props.project.completed_on),
].filter(Boolean).join(' · '));
</script>
