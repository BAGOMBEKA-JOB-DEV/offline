import { describe, it, expect, vi, beforeEach } from 'vitest'
import { useOfflineQueue } from '@/composables/useOfflineQueue'
import { fetchMock, localStorageMock } from '../setup'

const MUKONO = {
  submission_uuid: 'f81d4fae-7dec-11d0-a765-00a0c91e6bf6',
  urban_council: 'Mukono Municipality',
  pdp_status: 'Active',
  expiry_year: 2032,
  field_officer_timestamp: '2026-06-03T09:15:00Z',
}

const ENTBBE = {
  submission_uuid: '6ec0bd7f-11c0-43da-975e-2a8ad9ebae0b',
  urban_council: 'Entebbe Municipal Council',
  pdp_status: 'Expiring',
  expiry_year: 2026,
  field_officer_timestamp: '2026-06-03T10:22:11Z',
}

const GULU = {
  submission_uuid: 'bc29e1a8-89c0-4fb1-b12e-1b32d20912ab',
  urban_council: 'Gulu City Council',
  pdp_status: 'Missing',
  expiry_year: null,
  field_officer_timestamp: '2026-06-03T11:05:45Z',
}

describe('useOfflineQueue', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    localStorageMock.getItem.mockReturnValue(null)
  })

  it('starts with an empty queue', () => {
    const q = useOfflineQueue()
    expect(q.isEmpty.value).toBe(true)
    expect(q.count.value).toBe(0)
  })

  it('enqueues a record and persists it', () => {
    const q = useOfflineQueue()
    q.enqueue(MUKONO)
    expect(q.count.value).toBe(1)
    expect(localStorageMock.setItem).toHaveBeenCalled()
  })

  it('enqueues a batch', () => {
    const q = useOfflineQueue()
    q.enqueueBatch([MUKONO, ENTBBE, GULU])
    expect(q.count.value).toBe(3)
  })

  it('clears the queue', () => {
    const q = useOfflineQueue()
    q.enqueueBatch([MUKONO, ENTBBE, GULU])
    q.clear()
    expect(q.count.value).toBe(0)
  })

  it('removes a record by uuid', () => {
    const q = useOfflineQueue()
    q.enqueueBatch([MUKONO, ENTBBE, GULU])
    q.removeByUuid(MUKONO.submission_uuid)
    expect(q.count.value).toBe(2)
  })

  it('flushes the queue and returns dispositions', async () => {
    const q = useOfflineQueue()
    q.enqueueBatch([MUKONO, ENTBBE, MUKONO, GULU])

    fetchMock.mockResolvedValueOnce({
      ok: true,
      json: async () => ({
        data: {
          dispositions: [
            { submission_uuid: MUKONO.submission_uuid, committed: true, reason: 'committed' },
            { submission_uuid: ENTBBE.submission_uuid, committed: true, reason: 'committed' },
            { submission_uuid: MUKONO.submission_uuid, committed: false, reason: 'duplicate_within_batch' },
            { submission_uuid: GULU.submission_uuid, committed: true, reason: 'committed' },
          ],
        },
      }),
    })

    const result = await q.flush()
    expect(result).not.toBeNull()
    expect(result).toHaveLength(4)
    expect(q.count.value).toBe(0)
  })

  it('handles flush errors gracefully', async () => {
    const q = useOfflineQueue()
    q.enqueue(MUKONO)

    fetchMock.mockResolvedValueOnce({
      ok: false,
      status: 500,
    })

    const result = await q.flush()
    expect(result).toBeNull()
    expect(q.lastError.value).toContain('500')
  })
})