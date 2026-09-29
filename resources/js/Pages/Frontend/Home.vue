<template>
  <FrontendLayout>
    <!-- ============ Hero ============ -->
    <section class="relative s-dark overflow-hidden">
      <template v-if="hero.image">
        <img :src="img(hero.image, 1280)" :srcset="srcset(hero.image, 1920)" sizes="100vw" alt="" class="absolute inset-0 img-cover" fetchpriority="high" decoding="async" />
        <!-- Readability: solid behind the text, photo fades in towards the right (bottom on phones) -->
        <div class="absolute inset-0 hidden lg:block" style="background: linear-gradient(90deg, #0c0f14 0%, rgba(12,15,20,0.96) 38%, rgba(12,15,20,0.72) 62%, rgba(12,15,20,0.45) 100%)"></div>
        <div class="absolute inset-0 lg:hidden" style="background: linear-gradient(180deg, rgba(12,15,20,0.93) 0%, rgba(12,15,20,0.88) 55%, rgba(12,15,20,0.97) 100%)"></div>
      </template>
      <div v-else class="absolute inset-0 grid-bg opacity-70"></div>
      <div class="absolute -left-40 -top-40 w-[36rem] h-[36rem] rounded-full blur-3xl opacity-20" style="background: radial-gradient(circle, #f97316, transparent 65%)"></div>

      <div class="relative container-app py-16 lg:py-24">
        <div :class="['grid gap-12 items-center', hero.show_quote_form ? 'lg:grid-cols-[1.25fr_1fr]' : '']">
          <div :class="!hero.show_quote_form && 'max-w-3xl'">
            <p v-if="hero.eyebrow" class="eyebrow !text-[#fdae72]">{{ hero.eyebrow }}</p>
            <h1 class="h-display !text-white mt-4">{{ hero.title || `Renovation & repair services in Singapore` }}</h1>
            <p v-if="hero.subtitle" class="mt-5 text-[1.1rem] leading-relaxed text-white/70 max-w-2xl">{{ hero.subtitle }}</p>

            <div class="mt-8 flex flex-col sm:flex-row gap-3">
              <WhatsAppButton size="lg">Get a free quote</WhatsAppButton>
              <component v-if="hero.btn_link && hero.btn_link !== '/contact'" :is="isInternal(hero.btn_link) ? Link : 'a'" :href="hero.btn_link" class="btn btn-light btn-lg">
                {{ hero.btn_name || 'Our services' }}
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
              </component>
              <a v-else-if="company.tel" :href="'tel:' + tel" class="btn btn-light btn-lg">Call {{ company.phone }}</a>
            </div>

            <div v-if="reviews.google" class="mt-7 inline-flex items-center gap-3 text-sm text-white/80">
              <Stars :value="reviews.google.rating" />
              <span><strong class="text-white">{{ reviews.google.rating?.toFixed(1) }}</strong> from {{ reviews.google.total }} Google reviews</span>
            </div>

            <ul v-if="stats.length" class="mt-10 grid grid-cols-2 sm:grid-cols-4 rounded-2xl border border-white/10 bg-white/[0.04] backdrop-blur-sm divide-white/10 overflow-hidden">
              <li v-for="(c, i) in stats" :key="c.id" :class="['flex items-center gap-3 px-4 py-4', i % 2 ? 'border-l border-white/10' : '', i > 1 ? 'border-t sm:border-t-0 border-white/10' : '', i === 2 ? 'sm:border-l' : '']">
                <span class="shrink-0 grid place-items-center w-10 h-10 rounded-xl bg-orange-500/15 text-orange-300">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" :d="c.icon" /></svg>
                </span>
                <div class="min-w-0">
                  <p class="text-[1.35rem] font-extrabold text-white leading-none tabular-nums" style="font-family: var(--font-display)">{{ c.value }}</p>
                  <p class="mt-1 text-[12px] leading-tight text-white/65">{{ c.label }}</p>
                </div>
              </li>
            </ul>
          </div>

          <div v-if="hero.show_quote_form" class="card p-6 sm:p-7" style="box-shadow: var(--s-shadow-lg)">
            <div class="flex items-center justify-between mb-5">
              <div>
                <h2 class="h-card !text-[1.15rem]">Get a free quote</h2>
                <p v-if="texts.quote_intro" class="text-[13px] s-subtle mt-0.5">{{ texts.quote_intro }}</p>
              </div>
              <span class="icon-box !w-10 !h-10">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6M7 3h7l5 5v12a1 1 0 01-1 1H7a2 2 0 01-2-2V5a2 2 0 012-2z" /></svg>
              </span>
            </div>
            <QuoteForm :services="allServices" subject="Quote request · Homepage" id-prefix="hero" />
          </div>
        </div>
      </div>
    </section>

    <!-- ============ Trust strip ============ -->
    <section v-if="hero.badges?.length" class="s-bg border-b s-border">
      <div class="container-app py-5 flex flex-wrap items-center justify-center gap-x-8 gap-y-3 text-[13.5px] font-medium s-muted">
        <span v-for="b in hero.badges" :key="b" class="inline-flex items-center gap-2">
          <svg class="w-4 h-4 s-accent" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
          {{ b }}
        </span>
      </div>
    </section>

    <!-- ============ Services ============ -->
    <section v-if="services.length" class="section-y s-bg-alt">
      <div class="container-app">
        <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-6 mb-10">
          <div class="max-w-2xl">
            <p class="eyebrow">What we do</p>
            <h2 class="h-section mt-3">{{ homeStatic?.h_s_title || 'Services for Singapore homes and businesses' }}</h2>
            <p class="lead mt-3">{{ homeStatic?.h_s_subtitle || 'One accountable team for repairs, renovation and engineering work, with prices you see before we start.' }}</p>
          </div>
          <Link href="/services" class="btn btn-secondary shrink-0">All {{ allServicesCount }} services</Link>
        </div>

        <div v-if="categories.length" class="flex flex-wrap gap-2 mb-6">
          <Link v-for="c in categories.filter(c => c.services_count)" :key="c.id" :href="`/services/${c.slug}`" class="chip">{{ c.name }} <span class="s-subtle">{{ c.services_count }}</span></Link>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
          <ServiceCard v-for="s in services" :key="s.id" :service="s" />
        </div>
      </div>
    </section>

    <!-- ============ How it works ============ -->
    <section class="section-y s-bg">
      <div class="container-app">
        <div class="max-w-2xl">
          <p class="eyebrow">How it works</p>
          <h2 class="h-section mt-3">{{ texts.steps_title }}</h2>
        </div>
        <ol class="mt-10 grid grid-cols-1 md:grid-cols-3 gap-5">
          <li v-for="(step, i) in steps" :key="step.title" class="card p-6">
            <span class="text-[13px] font-bold s-accent">Step {{ i + 1 }}</span>
            <h3 class="h-card mt-2">{{ step.title }}</h3>
            <p class="mt-2 text-[14px] s-muted leading-relaxed">{{ step.text }}</p>
          </li>
        </ol>
      </div>
    </section>

    <!-- ============ Projects ============ -->
    <section v-if="projects.length" class="section-y s-bg-alt">
      <div class="container-app">
        <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-6 mb-10">
          <div class="max-w-2xl">
            <p class="eyebrow">Recent work</p>
            <h2 class="h-section mt-3">{{ homeStatic?.h_p_title || 'Recent projects' }}</h2>
            <p v-if="homeStatic?.h_p_subtitle" class="lead mt-3">{{ homeStatic.h_p_subtitle }}</p>
          </div>
          <Link href="/projects" class="btn btn-secondary shrink-0">See all projects</Link>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
          <ProjectCard v-for="p in projects" :key="p.id" :project="p" />
        </div>
      </div>
    </section>

    <!-- ============ Reviews ============ -->
    <section v-if="reviews.items.length" class="section-y s-bg">
      <div class="container-app">
        <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-6 mb-10">
          <div class="max-w-2xl">
            <p class="eyebrow">Reviews</p>
            <h2 class="h-section mt-3">{{ homeStatic?.h_test_title || 'What our customers say' }}</h2>
            <p v-if="reviews.google" class="lead mt-3">Rated <strong class="s-heading">{{ reviews.google.rating?.toFixed(1) }} out of 5</strong> from {{ reviews.google.total }} Google reviews.</p>
          </div>
          <Link href="/reviews" class="btn btn-secondary shrink-0">Read all reviews</Link>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
          <ReviewCard v-for="(r, i) in reviews.items" :key="i" :review="r" />
        </div>
      </div>
    </section>

    <!-- ============ Partners ============ -->
    <section v-if="partners.length" class="py-12 s-bg-alt border-y s-border">
      <div class="container-app">
        <p class="text-center text-[11px] font-bold uppercase tracking-[0.14em] s-subtle mb-7">Trusted by</p>
        <div class="flex flex-wrap items-center justify-center gap-x-12 gap-y-6">
          <div v-for="p in partners" :key="p.id" class="h-10 w-28 flex items-center justify-center opacity-60 hover:opacity-100 transition dark:invert dark:hue-rotate-180">
            <img :src="'/' + p.image" alt="" class="max-h-full max-w-full object-contain grayscale" loading="lazy" />
          </div>
        </div>
      </div>
    </section>

    <!-- ============ Areas ============ -->
    <section v-if="topLocations.length" class="section-y s-bg">
      <div class="container-app grid lg:grid-cols-[1fr_1.4fr] gap-10 items-start">
        <div>
          <p class="eyebrow">Areas we serve</p>
          <h2 class="h-section mt-3">{{ texts.areas_title }}</h2>
          <p v-if="texts.areas_lead" class="lead mt-3">{{ texts.areas_lead }}</p>
          <Link href="/locations" class="btn btn-secondary mt-6">All areas</Link>
        </div>
        <div class="flex flex-wrap gap-2">
          <Link v-for="l in topLocations" :key="l.href" :href="l.href" class="chip !py-2 !px-4 !text-[14px]">
            <svg class="w-3.5 h-3.5 s-accent" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21s-7-6.2-7-11.5A7 7 0 0112 2.5a7 7 0 017 7C19 14.8 12 21 12 21z" /></svg>
            {{ l.name }}
          </Link>
        </div>
      </div>
    </section>

    <!-- ============ Articles ============ -->
    <section v-if="latestBlogs.length" class="section-y s-bg-alt">
      <div class="container-app">
        <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-6 mb-10">
          <div class="max-w-2xl">
            <p class="eyebrow">Guides & cost advice</p>
            <h2 class="h-section mt-3">{{ homeStatic?.h_b_title || 'Know the price before you call' }}</h2>
          </div>
          <Link href="/blogs" class="btn btn-secondary shrink-0">All articles</Link>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
          <ArticleCard v-for="b in latestBlogs" :key="b.id" :article="b" />
        </div>
      </div>
    </section>

    <CtaBand />
  </FrontendLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import FrontendLayout from '@/Layouts/FrontendLayout.vue';
import ServiceCard from '@/Components/Site/ServiceCard.vue';
import ProjectCard from '@/Components/Site/ProjectCard.vue';
import ReviewCard from '@/Components/Site/ReviewCard.vue';
import ArticleCard from '@/Components/Site/ArticleCard.vue';
import QuoteForm from '@/Components/Site/QuoteForm.vue';
import CtaBand from '@/Components/Site/CtaBand.vue';
import Stars from '@/Components/Site/Stars.vue';
import WhatsAppButton from '@/Components/Site/WhatsAppButton.vue';
import { img, srcset } from '@/utils/img';
import { statsFrom } from '@/utils/stats';

const props = defineProps({
  hero: { type: Object, default: () => ({}) },
  homeStatic: Object,
  services: { type: Array, default: () => [] },
  allServicesCount: Number,
  categories: { type: Array, default: () => [] },
  projects: { type: Array, default: () => [] },
  counters: { type: Array, default: () => [] },
  reviews: { type: Object, default: () => ({ items: [], google: null }) },
  latestBlogs: { type: Array, default: () => [] },
  partners: { type: Array, default: () => [] },
});

const stats = computed(() => statsFrom(props.counters));

const page = usePage();
const company = computed(() => page.props.company || {});
const tel = computed(() => company.value.tel || '');
const topLocations = computed(() => page.props.topLocations || []);
const allServices = computed(() => (page.props.footerTopServices || []).map((s, i) => ({ id: i, name: s.name })));
const isInternal = href => !href || href.startsWith('/');

const texts = computed(() => page.props.company?.texts || {});
// Admin → Website text → Homepage: How it works (a step without a title is hidden).
const steps = computed(() => [1, 2, 3].map((n) => ({ title: texts.value[`step${n}_title`], text: texts.value[`step${n}_text`] })).filter((s) => s.title));
</script>
