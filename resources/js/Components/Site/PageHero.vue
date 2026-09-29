<template>
  <section class="relative s-dark overflow-hidden">
    <img v-if="image" :src="img(image, 1280)" :srcset="srcset(image, 1920)" sizes="100vw" alt="" class="absolute inset-0 img-cover opacity-25" fetchpriority="high" decoding="async" />
    <div class="absolute inset-0 grid-bg opacity-60"></div>
    <div class="absolute -right-32 -top-32 w-[30rem] h-[30rem] rounded-full blur-3xl opacity-20" style="background: radial-gradient(circle, #f97316, transparent 65%)"></div>
    <div :class="['relative container-app', compact ? 'py-10 sm:py-12' : 'py-14 sm:py-20']">
      <nav v-if="crumbs.length" class="crumbs !text-white/55 mb-5" aria-label="Breadcrumb">
        <Link href="/" class="hover:!text-white">Home</Link>
        <template v-for="c in crumbs" :key="c.label">
          <span class="sep">/</span>
          <Link v-if="c.href" :href="c.href" class="hover:!text-white">{{ c.label }}</Link>
          <span v-else class="text-white/85" aria-current="page">{{ c.label }}</span>
        </template>
      </nav>
      <div class="grid lg:grid-cols-[1fr_auto] gap-8 items-end">
        <div class="max-w-3xl">
          <p v-if="eyebrow" class="eyebrow !text-[#fdae72]">{{ eyebrow }}</p>
          <h1 class="h-page !text-white mt-3">{{ title }}</h1>
          <p v-if="lead" class="mt-4 text-[1.05rem] leading-relaxed text-white/70 max-w-2xl">{{ lead }}</p>
          <slot name="below" />
        </div>
        <slot />
      </div>
    </div>
  </section>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import { img, srcset } from '@/utils/img';

defineProps({
  title: { type: String, required: true },
  eyebrow: String,
  lead: String,
  image: String,
  crumbs: { type: Array, default: () => [] },
  compact: Boolean,
});
</script>
