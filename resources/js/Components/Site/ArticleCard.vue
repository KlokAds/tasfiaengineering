<template>
  <Link :href="`/blogs/${article.slug}`" class="group card card-link overflow-hidden flex flex-col">
    <div class="aspect-[16/9] overflow-hidden s-surface-2">
      <img :src="article.image ? img(article.image, 640) : '/logo.png'" :srcset="srcset(article.image, 1024)" sizes="(min-width: 1024px) 380px, (min-width: 640px) 50vw, 100vw" :alt="article.name" loading="lazy" decoding="async" width="640" height="360" class="img-cover group-hover:scale-[1.04] transition-transform duration-500" />
    </div>
    <div class="p-5 flex-1 flex flex-col">
      <p class="text-[12px] s-subtle">
        <span v-if="article.primary_service" class="s-accent font-semibold">{{ article.primary_service.name }} · </span>
        <time v-if="article.published_at" :datetime="article.published_at">{{ date(article.published_at) }}</time>
      </p>
      <h3 class="h-card mt-1.5 line-clamp-2 group-hover:text-[var(--s-accent-text)] transition-colors">{{ article.name }}</h3>
      <p v-if="article.excerpt" class="mt-2 text-[13.5px] s-muted line-clamp-3 leading-relaxed">{{ article.excerpt }}</p>
      <span class="mt-auto pt-4 text-[13px] font-semibold s-accent inline-flex items-center gap-1 group-hover:gap-2 transition-all">
        Read the guide
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
      </span>
    </div>
  </Link>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import { img, srcset } from '@/utils/img';

defineProps({ article: { type: Object, required: true } });
const date = d => new Date(d).toLocaleDateString('en-SG', { day: 'numeric', month: 'short', year: 'numeric' });
</script>
