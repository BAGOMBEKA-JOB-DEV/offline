# Offline-First Sync Simulator

Resilient web dashboard and field synchronization architecture for low-connectivity environments.

Built with **Laravel 13** (backend API) and **Vue 3 + TypeScript** (frontend SPA).

## Architecture

```
offline/
├── backend/          # Laravel 13 API
├── frontend/         # Vue 3 + Vite + TypeScript SPA
├── plan.md           # The challenge brief
└── README.md         # This file
```

### Data Flow

1. **Online**: Records are sent directly to the Laravel API at `/api/v1/sync`.
2. **Offline**: Records are queued in `localStorage` via the Vue composable `useOfflineQueue`.
3. **Reconnection**: The queue is flushed to the Laravel API.
4. **Deduplication**: Laravel checks `submission_uuid` uniqueness (primary key) and `client_batch_id` idempotency, returning only committed records.

### Deduplication Layers

| Layer | Mechanism | Purpose |
|-------|-----------|---------|
| Batch-level | `sync_runs.client_batch_id` unique | Absorbs retried flushes |
| In-batch | `seenInBatch` set | Drops Mukono duplicate before DB |
| Record-level | `pdp_submissions.submission_uuid` PK | Guarantees no duplicate rows |

## Prerequisites

- **PHP 8.3+** (Laravel 13 requires PHP 8.3+)
- **Composer**
- **Node.js 20.19+ or 22.12+**
- **SQLite** (default, no extra service needed)

## Getting Started

### Backend

```bash
cd backend

# Install dependencies
composer install

# Set up environment
cp .env.example .env
php artisan key:generate

# Run migrations
php artisan migrate

# Start the server
php artisan serve --port=8000
```

The API will be available at `http://localhost:8000`.

### Frontend

```bash
cd frontend

# Install dependencies
npm install

# Start the dev server
npm run dev
```

The SPA will be available at `http://localhost:5173`.

The Vite dev server proxies `/api/*` requests to the Laravel backend at `http://localhost:8000`.

## API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/v1/sync` | Flush a device's offline queue |
| GET | `/api/v1/submissions` | List committed records |
| GET | `/api/v1/sync-runs` | Flush history |

## Development

### 1. Install dependencies

```bash
# Backend
cd backend
composer install

# Frontend
cd frontend
npm install
```

### 2. Set up the database

```bash
cd backend
cp .env.example .env
php artisan key:generate
php artisan migrate
```

### 3. Start the servers (run in separate terminals)

```bash
# Terminal 1 — Laravel API
cd backend
php artisan serve --port=8000
# API available at http://localhost:8000

# Terminal 2 — Vite dev server
cd frontend
npm run dev
# SPA available at http://localhost:5173
# /api/* is proxied to http://localhost:8000
```

### 4. Run the app

Open `http://localhost:5173/sync` and click **Load Challenge Payload**. Expected result: **3 Committed / 1 Dropped / 4 Received** (the duplicate Mukono record is dropped as `duplicate_within_batch`).

### 5. Build for production

```bash
cd frontend
npm run build
# Output: dist/
# Serve with: npm run preview  (http://localhost:4173)
```

### 6. Run the tests

```bash
# Backend — all tests (PHPUnit)
cd backend
php vendor/phpunit/phpunit/phpunit

# Backend — sync endpoint tests only
php vendor/phpunit/phpunit/phpunit tests/Feature/Api/V1/SyncEndpointTest.php

# Backend — validation tests only
php vendor/phpunit/phpunit/phpunit tests/Feature/Api/V1/SyncValidationTest.php

# Backend — submissions index tests only
php vendor/phpunit/phpunit/phpunit tests/Feature/Api/V1/SubmissionsIndexTest.php

# Backend — concurrency tests only
php vendor/phpunit/phpunit/phpunit tests/Feature/Api/V1/ConcurrencyTest.php

# Backend — unit tests only
php vendor/phpunit/phpunit/phpunit --testsuite Unit

# Backend — feature tests only
php vendor/phpunit/phpunit/phpunit --testsuite Feature

# Frontend — all tests (Vitest)
cd frontend
npm run test:run

# Frontend — composable tests only
npm run test:run src/tests/composables/useOfflineQueue.test.js

# Frontend — component tests only
npm run test:run src/tests/components/StatusBadge.test.js
```

### 7. Lint and typecheck

```bash
# Frontend — ESLint
cd frontend
npm run lint

# Frontend — TypeScript typecheck
npx vue-tsc --noEmit

# Backend — Laravel Pint (code style)
cd backend
vendor/bin/pint --test
```

### 8. Reset the database (between test runs)

```bash
cd backend
# Wipe all committed submissions and sync runs
php artisan tinker --execute="use App\Models\PdpSubmission; use App\Models\SyncRun; PdpSubmission::query()->delete(); SyncRun::query()->delete();"

# Or roll back all migrations
php artisan migrate:refresh

# Or roll back and re-run from scratch
php artisan migrate:fresh
```

### 9. Verify the sync flow end-to-end

```bash
# POST the challenge payload directly to the API
curl -X POST http://localhost:8000/api/v1/sync \
  -H "Content-Type: application/json" \
  -d '{
    "client_batch_id": "3f2504e0-4f89-41d3-9a0c-0305e82c3301",
    "device_id": "9c858901-8a57-4791-81fe-4c455b099bc9",
    "records": [
      {"submission_uuid":"f81d4fae-7dec-11d0-a765-00a0c91e6bf6","urban_council":"Mukono Municipality","pdp_status":"Active","expiry_year":2032,"field_officer_timestamp":"2026-06-03T09:15:00Z"},
      {"submission_uuid":"6ec0bd7f-11c0-43da-975e-2a8ad9ebae0b","urban_council":"Entebbe Municipal Council","pdp_status":"Expiring","expiry_year":2026,"field_officer_timestamp":"2026-06-03T10:22:11Z"},
      {"submission_uuid":"f81d4fae-7dec-11d0-a765-00a0c91e6bf6","urban_council":"Mukono Municipality","pdp_status":"Active","expiry_year":2032,"field_officer_timestamp":"2026-06-03T09:15:00Z"},
      {"submission_uuid":"bc29e1a8-89c0-4fb1-b12e-1b32d20912ab","urban_council":"Gulu City Council","pdp_status":"Missing","expiry_year":null,"field_officer_timestamp":"2026-06-03T11:05:45Z"}
    ]
  }'

# Expected: data.summary = { received: 4, committed: 3, dropped_as_duplicate: 1, outcome: "partial" }

# List committed records
curl http://localhost:8000/api/v1/submissions?per_page=100

# List sync runs (flush history)
curl http://localhost:8000/api/v1/sync-runs?per_page=10
```

## Challenge Payload

The challenge sends 4 records with Mukono Municipality appearing twice (same `submission_uuid`). The system must:

1. **Cache/queue** the records.
2. **Intercept and drop** the duplicate Mukono submission.
3. **Commit only the 3 unique, validated records**.

### Expected Output

Only 3 unique records committed:

```json
[
  { "submission_uuid": "f81d4fae-7dec-11d0-a765-00a0c91e6bf6", "urban_council": "Mukono Municipality", "pdp_status": "Active", "expiry_year": 2032 },
  { "submission_uuid": "6ec0bd7f-11c0-43da-975e-2a8ad9ebae0b", "urban_council": "Entebbe Municipal Council", "pdp_status": "Expiring", "expiry_year": 2026 },
  { "submission_uuid": "bc29e1a8-89c0-4fb1-b12e-1b32d20912ab", "urban_council": "Gulu City Council", "pdp_status": "Missing", "expiry_year": null }
]
```

## Project Structure

### Backend (`backend/`)

```
app/
  Http/Controllers/Api/V1/    # SyncController
  Models/                     # PdpSubmission, SyncRun
  Sync/                       # SyncIngestionService, Data DTOs
  Enums/                     # SyncOutcome, PdpStatus
  Rules/                     # ExpiryMatchesPdpStatus
database/
  migrations/                # users, cache, jobs, sync_runs, pdp_submissions
tests/Feature/Api/V1/       # SyncEndpointTest, etc.
```

### Frontend (`frontend/`)

```
src/
  components/                 # StatCard.vue, StatusBadge.vue
  composables/                # useOfflineQueue.js
  views/                      # Dashboard.vue, SyncSimulator.vue
  tests/                      # composable + component tests
```

## Design Decisions

- **Deduplication by `submission_uuid`**: Enforced via primary key at the database level.
- **Offline queue via `localStorage`**: Survives page reloads; persists until flushed.
- **Validation**: Each record must have non-empty `submission_uuid`, `urban_council`, and `pdp_status`.
- **Idempotency**: Unique `client_batch_id` makes repeated flushes safe.
- **TypeScript**: Full type safety on the frontend with JSDoc annotations.
- **Reusable components**: StatCard, StatusBadge are shared across Dashboard and Sync views.
- **Slack-inspired UI**: Clean sidebar navigation, muted grays, purple accent.

## License

MIT