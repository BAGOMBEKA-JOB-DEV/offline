/**
 * useOfflineQueue — manages a device's offline submission queue in localStorage.
 *
 * Field devices that lose connectivity stash records here. When the network
 * returns, the queue is flushed to the Laravel sync endpoint.
 */
import { ref, computed } from 'vue'

const QUEUE_KEY = 'offline_sync_queue_v1'

export interface QueuedRecord {
  submission_uuid: string
  urban_council: string
  pdp_status: string
  expiry_year: number | null
  field_officer_timestamp: string
  queued_at: string
}

export interface Disposition {
  submission_uuid: string
  committed: boolean
  reason: string
}

export function useOfflineQueue() {
  const queue = ref<QueuedRecord[]>(loadQueue())
  const flushing = ref(false)
  const lastResult = ref<Disposition[] | null>(null)
  const lastError = ref<string | null>(null)

  const count = computed(() => queue.value.length)
  const isEmpty = computed(() => queue.value.length === 0)

  function loadQueue(): QueuedRecord[] {
    try {
      const raw = localStorage.getItem(QUEUE_KEY)
      return raw ? (JSON.parse(raw) as QueuedRecord[]) : []
    } catch {
      return []
    }
  }

  function persist(): void {
    localStorage.setItem(QUEUE_KEY, JSON.stringify(queue.value))
  }

  function enqueue(record: Omit<QueuedRecord, 'queued_at'>): void {
    const entry: QueuedRecord = {
      ...record,
      queued_at: new Date().toISOString(),
    }
    queue.value.push(entry)
    persist()
  }

  function enqueueBatch(records: Omit<QueuedRecord, 'queued_at'>[]): void {
    for (const r of records) enqueue(r)
  }

  function clear(): void {
    queue.value = []
    persist()
  }

  function removeByUuid(uuid: string): void {
    queue.value = queue.value.filter((r) => r.submission_uuid !== uuid)
    persist()
  }

  async function flush(batchId?: string): Promise<Disposition[] | null> {
    flushing.value = true
    lastError.value = null
    lastResult.value = null

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
      const dispositions: Disposition[] = json?.data?.dispositions ?? []

      lastResult.value = dispositions

      const processed = new Set(dispositions.map((d) => d.submission_uuid))
      queue.value = queue.value.filter((r) => !processed.has(r.submission_uuid))
      persist()

      return dispositions
    } catch (err) {
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