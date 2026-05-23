<script setup>
defineProps({
  type: String,
  label: String,
  emoji: String,
})

const classes = {
  conservative: 'bg-red-500/20 text-red-400 border-red-500/30',
  moderate: 'bg-violet-500/20 text-violet-400 border-violet-500/30',
  progressive: 'bg-blue-500/20 text-blue-400 border-blue-500/30',
}
</script>

<template>
  <span
    :class="[
      'inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full border font-medium',
      classes[type] ?? 'bg-slate-700 text-slate-400 border-slate-600'
    ]"
  >
    <span v-if="emoji">{{ emoji }}</span>
    <span v-if="label">{{ label }}</span>
  </span>
</template>
