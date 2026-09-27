<script setup>
import { ref, computed } from 'vue'
import { useOfflineQueue } from '@/composables/useOfflineQueue'

const queue = useOfflineQueue()
const { lastResult, lastError, count, isEmpty, queue: queued, flush, clear, enqueueBatch } = queue

const CHALLENGE_PAYLOAD = [
  { submission_uuid: 'f81d4fae-7dec-11d0-a765-00a0c91e6bf6', urban_council: 'Mukono Municipality', pdp_status: 'Active', expiry_year: 2032, field_officer_timestamp: '2026-06-03T09:15:00Z' },
  { submission_uuid: '6ec0bd7f-11c0-43da-975e-2a8ad9ebae0b', urban_council: 'Entebbe Municipal Council', pdp_status: 'Expiring', expiry_year: 2026, field_officer_timestamp: '2026-06-03T10:22:11Z' },
  { submission_uuid: 'f81d4fae-7dec-11d0-a765-00a0c91e6bf6', urban_council: 'Mukono Municipality', pdp_status: 'Active', expiry_year: 2032, field_officer_timestamp: '2026-06-03T09:15:00Z' },
  { submission_uuid: 'bc29e1a8-89c0-4fb1-b12e-1b32d20912ab', urban_council: 'Gulu City Council', pdp_status: 'Missing', expiry_year: null, field_officer_timestamp: '2026-06-03T11:05:45Z' },
]

const loadingChallenge = ref(false)
const statusColor = (s) => {
  if (s === 'committed') return 'bg-blue-100 text-blue-700'
  if (s === 'duplicate_within_batch' || s === 'duplicate') return 'bg-red-100 text-red-700'
  return 'bg-gray-100 text-gray-700'
}

async function loadChallenge() {
  loadingChallenge.value = true
  clear()
  enqueueBatch(CHALLENGE_PAYLOAD)
  await flush()
  loadingChallenge.value = false
}

const committed = computed(() => (lastResult.value || []).filter(d => d.committed))
const dropped = computed(() => (lastResult.value || []).filter(d => !d.committed))
</script>

<template>
  <div class="p-8 max-w-7xl">
    <div class="mb-8">
      <h1 class="text-4xl font-semibold tracking-tight text-[#1d1d1f]">Offline-First Sync Simulator</h1>
      <p class="text-[15px] text-[#86868b] mt-1">
        Field tools in low-connectivity areas cache/queue records, intercept duplicate submissions,
        and commit only unique, validated records on reconnect.
      </p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <div class="bg-white rounded-2xl border border-black/5 p-6 shadow-sm">
        <h2 class="text-[17px] font-semibold text-[#1d1d1f] mb-4">Offline Queue</h2>
        <button @click="loadChallenge" :disabled="loadingChallenge" class="w-full mb-4 bg-[#007AFF] text-white text-[14px] font-medium px-4 py-2.5 rounded-lg hover:bg-[#0066d6] disabled:opacity-50 transition">
          {{ loadingChallenge ? 'Flushing...' : 'Load Challenge Payload' }}
        </button>
        <div class="flex justify-between text-[13px] text-[#86868b] mb-2">
          <span>Queued</span>
          <span class="font-semibold text-[#1d1d1f]">{{ count }}</span>
        </div>
        <div v-if="isEmpty" class="text-[13px] text-[#86868b] py-8 text-center">Queue is empty.</div>
        <ul v-else class="divide-y divide-black/5 max-h-80 overflow-y-auto">
          <li v-for="r in queued" :key="r.submission_uuid" class="py-2 flex items-center justify-between text-[14px]">
            <span class="text-[#1d1d1f] truncate">{{ r.urban_council }}</span>
            <span class="text-[#86868b] font-mono text-[12px]">{{ r.submission_uuid.slice(0, 8) }}</span>
          </li>
        </ul>
        <button v-if="!isEmpty" @click="clear" class="mt-4 w-full text-[13px] text-red-600 hover:text-red-800">Clear queue</button>
      </div>

      <div class="lg:col-span-2 space-y-6">
        <div v-if="lastResult" class="bg-white rounded-2xl border border-black/5 p-6 shadow-sm">
          <h2 class="text-[17px] font-semibold text-[#1d1d1f] mb-4">Sync Result</h2>
          <div class="grid grid-cols-3 gap-4 mb-6">
            <div class="bg-blue-50 rounded-xl p-4"><div class="text-2xl font-semibold text-[#007AFF]">{{ committed.length }}</div><div class="text-[12px] text-[#86868b] mt-1">Committed</div></div>
            <div class="bg-red-50 rounded-xl p-4"><div class="text-2xl font-semibold text-[#ff3b30]">{{ dropped.length }}</div><div class="text-[12px] text-[#86868b] mt-1">Dropped</div></div>
            <div class="bg-gray-50 rounded-xl p-4"><div class="text-2xl font-semibold text-[#1d1d1f]">{{ lastResult.length }}</div><div class="text-[12px] text-[#86868b] mt-1">Received</div></div>
          </div>
          <h3 class="font-medium text-[#1d1d1f] mb-2">Dispositions</h3>
          <ul class="divide-y divide-black/5">
            <li v-for="d in lastResult" :key="d.submission_uuid" class="py-2 flex items-center justify-between text-[14px]">
              <span class="text-[#1d1d1f]">{{ d.submission_uuid.slice(0, 8) }}</span>
              <span class="px-2 py-1 rounded-full text-[12px] font-medium" :class="statusColor(d.reason)">{{ d.reason }}</span>
            </li>
          </ul>
          <div v-if="lastError" class="mt-4 text-[13px] text-red-600">Error: {{ lastError }}</div>
        </div>
        <div v-else class="bg-white rounded-2xl border border-black/5 p-6 text-center text-[#86868b]">
          Run the challenge payload to see sync results.
        </div>
      </div>
    </div>
  </div>
</template>