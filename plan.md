
Technical Shortlist & Mini-Challenge: Offline Systems Engineering
Inbox
Summarise this email

doug@kamlasmuwalab.com <doug@kamlasmuwalab.com>
09:49 (2 hours ago)
to me

Dear Job,
Thank you for your application. Following a review of your enterprise system design experience and portfolio, we are pleased to shortlist you for our technical interview round.
We are building resilient web dashboards and field synchronization architectures from scratch. To ground our technical conversation in real-world constraints without taking up too much of your time, please review the 2–3 hour mini-challenge below:
Task: Offline-First Sync Simulator
Field tools frequently operate in low-connectivity areas. Using your preferred stack (e.g., Vue,Laravel), build a lightweight script or component that handles this queued payload:
Cache/queue the records.
Intercept and drop the duplicate submission for Mukono Municipality (identical submission_uuid).
Commit only the 3 unique, validated records.
[
  {
    "submission_uuid": "f81d4fae-7dec-11d0-a765-00a0c91e6bf6",
    "urban_council": "Mukono Municipality",
    "pdp_status": "Active",
    "expiry_year": 2032,
    "field_officer_timestamp": "2026-06-03T09:15:00Z"
  },
  {
    "submission_uuid": "6ec0bd7f-11c0-43da-975e-2a8ad9ebae0b",
    "urban_council": "Entebbe Municipal Council",
    "pdp_status": "Expiring",
    "expiry_year": 2026,
    "field_officer_timestamp": "2026-06-03T10:22:11Z"
  },
  {
    "submission_uuid": "f81d4fae-7dec-11d0-a765-00a0c91e6bf6",
    "urban_council": "Mukono Municipality",
    "pdp_status": "Active",
    "expiry_year": 2032,
    "field_officer_timestamp": "2026-06-03T09:15:00Z"
  },
  {
    "submission_uuid": "bc29e1a8-89c0-4fb1-b12e-1b32d20912ab",
    "urban_council": "Gulu City Council",
    "pdp_status": "Missing",
    "expiry_year": null,
    "field_officer_timestamp": "2026-06-03T11:05:45Z"
  }
]
Submission: Reply directly to this email with your public GitHub repository link and a brief README.md by Wednesday, September 30, 2026, at 5:00 PM EAT.
Next Steps: Specific interview times and panel details will follow receipt of your repository.

Best regards,

--------------------------------------------------------------------------------------------------------
Douglas 'Muwanguzi' Kamoga

Physical Planner

Kamlas Muwalab / Urban Scape Design Associates LTD
Department of Physical Planning & Land Use Regulation

Plot 13 – 15 Parliament Avenue | P.O.Box 23096 Kampala