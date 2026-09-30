<template>
  <div class="admin-ui min-h-screen grid lg:grid-cols-[1.05fr_1fr]">
    <!-- Brand side -->
    <aside class="hidden lg:flex relative overflow-hidden flex-col justify-between p-12 text-white" style="background: #0c0f14">
      <div class="absolute inset-0 opacity-[0.07]" style="background-image: linear-gradient(#fff 1px, transparent 1px), linear-gradient(90deg, #fff 1px, transparent 1px); background-size: 44px 44px"></div>
      <div class="absolute -right-24 -bottom-24 w-[28rem] h-[28rem] rounded-full blur-3xl opacity-30" style="background: radial-gradient(circle, #f97316, transparent 65%)"></div>

      <div class="relative flex items-center gap-3">
        <span class="w-11 h-11 rounded-xl bg-white flex items-center justify-center"><img src="/logo.png" alt="" class="w-8 h-8 object-contain" /></span>
        <span class="text-lg font-bold tracking-tight">Tasfia Engineering</span>
      </div>

      <div class="relative max-w-md">
        <h1 class="text-4xl font-bold tracking-tight leading-tight">Run the website from one place.</h1>
        <p class="mt-4 text-[15px] text-white/65 leading-relaxed">Articles, services, prices, reviews and enquiries, with SEO checks built into every page you write.</p>
        <ul class="mt-8 space-y-3 text-sm text-white/80">
          <li class="flex items-center gap-3"><span class="w-6 h-6 rounded-full bg-white/10 flex items-center justify-center text-[#fdae72]">✓</span>Autosave, so nothing is lost</li>
          <li class="flex items-center gap-3"><span class="w-6 h-6 rounded-full bg-white/10 flex items-center justify-center text-[#fdae72]">✓</span>Approval before anything goes live</li>
          <li class="flex items-center gap-3"><span class="w-6 h-6 rounded-full bg-white/10 flex items-center justify-center text-[#fdae72]">✓</span>SEO, AI search and trust score on every page</li>
        </ul>
      </div>

      <p class="relative text-xs text-white/40">© {{ new Date().getFullYear() }} Tasfia Engineering · Singapore</p>
    </aside>

    <!-- Form side -->
    <main class="flex flex-col justify-center px-6 py-12 sm:px-12">
      <div class="w-full max-w-sm mx-auto">
        <div class="lg:hidden flex items-center gap-3 mb-10">
          <span class="w-10 h-10 rounded-xl bg-white border a-border flex items-center justify-center"><img src="/logo.png" alt="" class="w-7 h-7 object-contain" /></span>
          <span class="font-bold tracking-tight">Tasfia Engineering</span>
        </div>

        <slot />

        <div class="mt-10 flex items-center justify-between text-xs a-subtle">
          <Link href="/" class="a-hover-text">← Back to website</Link>
          <div class="a-seg">
            <button v-for="t in ['light', 'dark']" :key="t" type="button" @click="setTheme(t)" :class="['capitalize', theme === t && 'is-on']">{{ t }}</button>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { Link } from '@inertiajs/vue3';

const theme = ref('light');

function setTheme(t) {
  theme.value = t;
  document.documentElement.classList.toggle('dark', t === 'dark');
  try { localStorage.setItem('tasfia_admin_theme', t); } catch (e) { /* ignore */ }
}

onMounted(() => {
  theme.value = document.documentElement.classList.contains('dark') ? 'dark' : 'light';
});
</script>
