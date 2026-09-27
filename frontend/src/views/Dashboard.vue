<script setup>
import { ref, onMounted, computed } from 'vue'

const submissions = ref([])
const loading = ref(true)
const error = ref(null)
const filter = ref('')

async function load() {
  loading.value = true
  error.value = null
  try {
    const res = await fetch('/api/v1/submissions?per_page=100', {
      headers: { Accept: 'application/json' },
    })
    if (!res.ok) throw new Error('HTTP ' + res.status)
    const json = await res.json()
    submissions.value = json.data || []
  } catch (err) {
    error.value = err.message || 'Failed to load'
  } finally {
    loading.value = false
  }
}

onMounted(load)

const filtered = computed(() => {
  if (!filter.value) return submissions.value
  return submissions.value.filter(s =>
    s.urban_council.toLowerCase().includes(filter.value.toLowerCase())
  )
})

const stats = computed(() => ({
  total: submissions.value.length,
  active: submissions.value.filter(s => s.pdp_status === 'Active').length,
  expiring: submissions.value.filter(s => s.pdp_status === 'Expiring').length,
  missing: submissions.value.filter(s => s.pdp_status === 'Missing').length,
}))

const statusColor = (s) => {
  if (s === 'Active') return 'bg-blue-100 text-blue-700'
  if (s === 'Expiring') return 'bg-orange-100 text-orange-700'
  if (s === 'Missing') return 'bg-red-100 text-red-700'
  return 'bg-gray-100 text-gray-700'
}
</script>

<template>
  <div class="p-8 max-w-7xl">
    <div class="mb-8">
      <h1 class="text-4xl font-semibold tracking-tight text-[#1d1d1f]">Dashboard</h1>
      <p class="text-[15px] text-[#86868b] mt-1">Committed PDP submissions from field sync runs</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-5 mb-8">
      <div class="bg-white rounded-2xl border border-black/5 p-6 shadow-sm">
        <div class="text-3xl font-semibold text-[#1d1d1f]">{{ stats.total }}</div>
        <div class="text-[13px] text-[#86868b] mt-1">Total Records</div>
      </div>
      <div class="bg-white rounded-2xl border border-black/5 p-6 shadow-sm">
        <div class="text-3xl font-semibold text-[#007AFF]">{{ stats.active }}</div>
        <div class="text-[13px] text-[#86868b] mt-1">Active Plans</div>
      </div>
      <div class="bg-white rounded-2xl border border-black/5 p-6 shadow-sm">
        <div class="text-3xl font-semibold text-[#ff9500]">{{ stats.expiring }}</div>
        <div class="text-[13px] text-[#86868b] mt-1">Expiring</div>
      </div>
      <div class="bg-white rounded-2xl border border-black/5 p-6 shadow-sm">
        <div class="text-3xl font-semibold text-[#ff3b30]">{{ stats.missing }}</div>
        <div class="text-[13px] text-[#86868b] mt-1">Missing Data</div>
      </div>
    </div>

    <div v-if="error" class="mb-4 bg-red-50 border border-red-200 rounded-xl p-4 text-sm text-red-700">
      {{ error }}
    </div>

    <div class="bg-white rounded-2xl border border-black/5 overflow-hidden shadow-sm">
      <div class="px-6 py-4 border-b border-black/5 flex items-center justify-between">
        <h2 class="text-[17px] font-semibold text-[#1d1d1f]">Submissions</h2>
        <input
          v-model="filter"
          type="text"
          placeholder="Filter by council name..."
          class="px-3 py-1.5 text-[14px] border border-black/10 rounded-lg w-64 focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500"
        />
      </div>

      <div v-if="loading" class="p-8 text-center text-[#86868b]">Loading...</div>

      <div v-else>
        <table class="min-w-full divide-y divide-black/5">
          <thead class="bg-black/[0.02]">
            <tr>
              <th class="px-6 py-3 text-left text-[12px] font-medium text-[#86868b] uppercase tracking-wider">Urban Council</th>
              <th class="px-6 py-3 text-left text-[12px] font-medium text-[#86868b] uppercase tracking-wider">PDP Status</th>
              <th class="px-6 py-3 text-left text-[12px] font-medium text-[#86868b] uppercase tracking-wider">Expiry</th>
              <th class="px-6 py-3 text-left text-[12px] font-medium text-[#86868b] uppercase tracking-wider">Submitted</th>
              <th class="px-6 py-3 text-left text-[12px] font-medium text-[#86868b] uppercase tracking-wider">UUID</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-black/5">
            <tr v-for="s in filtered" :key="s.submission_uuid" class="hover:bg-black/[0.02]">
              <td class="px-6 py-4 text-[14px] font-medium text-[#1d1d1f]">{{ s.urban_council }}</td>
              <td class="px-6 py-4 text-[14px]">
                <span class="px-2 py-1 rounded-full text-[12px] font-medium" :class="statusColor(s.pdp_status)">
                  {{ s.pdp_status }}
                </span>
              </td>
              <td class="px-6 py-4 text-[14px] text-[#86868b]">{{ s.expiry_year ?? '—' }}</td>
              <td class="px-6 py-4 text-[14px] text-[#86868b]">{{ s.field_officer_timestamp }}</td>
              <td class="px-6 py-4 text-[14px] text-[#86868b] font-mono text-[12px]">{{ s.submission_uuid }}</td>
            </tr>
          </tbody>
        </table>
        <div v-if="filtered.length === 0" class="px-6 py-8 text-center text-[#86868b]">
          No submissions found.
        </div>
      </div>
    </div>
  </div>
</template>