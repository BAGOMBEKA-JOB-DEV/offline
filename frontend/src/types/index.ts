export type PdpStatus = 'Active' | 'Expiring' | 'Missing'

export interface Submission {
  submission_uuid: string
  urban_council: string
  pdp_status: PdpStatus
  expiry_year: number | null
  field_officer_timestamp: string
}

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

export interface SyncRun {
  id: number
  client_batch_id: string
  device_id: string | null
  outcome: string
  received_count: number
  committed_count: number
  duplicate_count: number
  rejected_count: number
  started_at: string
  finished_at: string | null
}

export interface SyncResponse {
  run: SyncRun
  summary: {
    received: number
    committed: number
    dropped_as_duplicate: number
    replayed: boolean
    outcome: string
  }
  dispositions: Disposition[]
  committed: Submission[]
}