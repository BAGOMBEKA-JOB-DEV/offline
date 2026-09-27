/**
 * useOfflineQueue — manages a device's offline submission queue in localStorage.
 *
 * Field devices that lose connectivity stash records here. When the network
 * returns, the queue is flushed to the Laravel sync endpoint.
 */
import { ref, computed } from 'vue'

const QUEUE_KEY = 'offline_sync_queue_v1'
const LAST_RESULT_KEY = 'offline_sync_last_result_v1'

/**
 * @typedef {Object} QueuedRecord
 * @property {string} submission_uuid
 * @property {string} urban_council
 * @property {string} pdp_status
 * @property {number|null} expiry_year
 * @property {string} field_officer_timestamp
 * @property {string} queued_at
 */

/**
 * @typedef {Object} Disposition
 * @property {string} submission_uuid
 * @property {boolean} committed
 * @property {string} reason
 */

/**
 * @returns {{
 *   queue: import('vue').Ref<QueuedRecord[]>,
 *   flushing: import('vue').Ref<boolean>,
 *   lastResult: import('vue').Ref<Disposition[] | null>,
 *   lastError: import('vue').Ref<string | null>,
 *   count: import('vue').ComputedRef<number>,
 *   isEmpty: import('vue').ComputedRef<boolean>,
 *   enqueue: (record: Omit<QueuedRecord, 'queued_at'>) => void,
 *   enqueueBatch: (records: Omit<QueuedRecord, 'queued_at'>[]) => void,
 *   clear: () => void,
 *   removeByUuid: (uuid: string) => void,
 *   flush: (batchId?: string) => Promise<Disposition[] | null>
 * }}
 */
export function useOfflineQueue() {
  const queue = ref(/** @type {QueuedRecord[]} */ (loadQueue()))
  const flushing = ref(false)
  const lastResult = ref(/** @type {Disposition[] | null} */ (loadLastResult()))
  const lastError = ref(/** @type {string | null} */ (null))

  const count = computed(() => queue.value.length)
  const isEmpty = computed(() => queue.value.length === 0)

  /** @returns {QueuedRecord[]} */
  function loadQueue() {
    try {
      const raw = localStorage.getItem(QUEUE_KEY)
      return raw ? (/** @type {QueuedRecord[]} */ (JSON.parse(raw))) : []
    } catch {
      return []
    }
  }

  /** @returns {Disposition[] | null} */
  function loadLastResult() {
    try {
      const raw = localStorage.getItem(LAST_RESULT_KEY)
      return raw ? (/** @type {Disposition[]} */ (JSON.parse(raw))) : null
    } catch {
      return null
    }
  }

  function persist() {
    localStorage.setItem(QUEUE_KEY, JSON.stringify(queue.value))
  }

  function persistLastResult() {
    if (lastResult.value === null) {
      localStorage.removeItem(LAST_RESULT_KEY)
      return
    }
    localStorage.setItem(LAST_RESULT_KEY, JSON.stringify(lastResult.value))
  }

  /** @param {Omit<QueuedRecord, 'queued_at'>} record */
  function enqueue(record) {
    const entry = {
      ...record,
      queued_at: new Date().toISOString(),
    }
    queue.value.push(entry)
    persist()
  }

  /** @param {Omit<QueuedRecord, 'queued_at'>[]} records */
  function enqueueBatch(records) {
    for (const r of records) enqueue(r)
  }

  function clear() {
    queue.value = []
    lastResult.value = null
    persist()
    persistLastResult()
  }

  /** @param {string} uuid */
  function removeByUuid(uuid) {
    queue.value = queue.value.filter((r) => r.submission_uuid !== uuid)
    persist()
  }

  /** @param {string} [batchId] */
  async function flush(batchId) {
    flushing.value = true
    lastError.value = null
    lastResult.value = null
    persistLastResult()

    try {
      const body = {
        client_batch_id: batchId ?? crypto.randomUUID(),
        records: queue.value.map((r) => ({
          submission_uuid: r.submission_uuid,
          urban_council: r.urban_council,
          pdp_status: r.pdp_status,
          expiry_year: r.expiry_year,
          field_officer_timestamp: r.field_officer_timestamp,
        })),
      }

      const res = await fetch('/api/v1/sync', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
        },
        body: JSON.stringify(body),
      })

      if (!res.ok) {
        throw new Error(`HTTP ${res.status}`)
      }

      const json = await res.json()
      const dispositions = json?.data?.dispositions ?? []

      lastResult.value = dispositions
      persistLastResult()

      const processed = new Set(dispositions.map((d) => d.submission_uuid))
      queue.value = queue.value.filter((r) => !processed.has(r.submission_uuid))
      persist()

      return dispositions
    } catch (err) {
      lastResult.value = null
      persistLastResult()
      lastError.value = err instanceof Error ? err.message : 'Unknown error'
      return null
    } finally {
      flushing.value = false
    }
  }

  return {
    queue,
    flushing,
    lastResult,
    lastError,
    count,
    isEmpty,
    enqueue,
    enqueueBatch,
    clear,
    removeByUuid,
    flush,
  }
}