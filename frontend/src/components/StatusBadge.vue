<script setup>
import { computed } from 'vue'

/**
 * @typedef {Object} Props
 * @property {string} status
 */

/** @type {import('vue').PropType<import('@/types').PdpStatus>} */
const props = defineProps({
  status: {
    type: String,
    required: true,
  },
})

const classes = computed(() => {
  switch (props.status) {
    case 'Active':
      return 'bg-green-100 text-green-800'
    case 'Expiring':
      return 'bg-yellow-100 text-yellow-800'
    case 'Missing':
      return 'bg-red-100 text-red-800'
    default:
      return 'bg-gray-100 text-gray-800'
  }
})
</script>

<template>
  <span
    class="px-2 py-1 rounded-full text-xs font-medium"
    :class="classes"
  >
    {{ status }}
  </span>
</template>