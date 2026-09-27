<script setup>
import { computed } from 'vue'

const props = defineProps({
  label: { type: String, required: true },
  value: { type: [Number, String], required: true },
  color: { type: String, default: 'gray' },
})

const colorMap = {
  green: 'bg-green-50 text-green-700',
  red: 'bg-red-50 text-red-700',
  gray: 'bg-gray-50 text-gray-700',
  blue: 'bg-blue-50 text-blue-700',
  yellow: 'bg-yellow-50 text-yellow-700',
}

const bgClass = computed(() => colorMap[props.color] || colorMap.gray)
</script>

<template>
  <div :class="['rounded p-3', bgClass]">
    <div class="text-2xl font-bold">{{ value }}</div>
    <div class="text-xs opacity-70">{{ label }}</div>
  </div>
</template>