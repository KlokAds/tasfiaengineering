<template>
  <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-6">
    <div class="min-w-0">
      <h2 class="text-xl sm:text-2xl font-bold tracking-tight">{{ title }}</h2>
      <p v-if="description" class="mt-1 text-sm a-muted max-w-3xl">{{ description }}</p>
      <slot name="meta" />
    </div>
    <div v-if="$slots.default" class="flex flex-wrap items-center gap-2 shrink-0">
      <slot />
    </div>
  </div>
</template>

<script setup>
defineProps({ title: String, description: String });
</script>
