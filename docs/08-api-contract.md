# API Contract

> The Next.js client and the Laravel backend are separate deployables (`ADR-001`). This document is the only interface between them. Behavior is governed by the capability specs; this document defines the transport shape.

## 1. Conventions

- Base path `/api/v1`. Breaking changes bump the version segment.
- JSON request and response bodies; UTF-8; `Content-Type: application/json`.
- Resource paths are plural nouns. Non-CRUD operations are sub-resources expressing an action (`POST /posts/{id}/approve`).
- Timestamps are RFC 3339 with offset. User-chosen times additionally carry `local_time` and `timezone` (`docs/06` §5).
- Identifiers are opaque strings. Clients must not parse them.

## 2. Authentication

| Aspect | Rule |
|---|---|
| Scheme | Session cookie, `HttpOnly` + `Secure` + `SameSite=Lax` |
| CSRF | Required on all state-changing requests for cookie-authenticated routes |
| Unauthenticated | `401` with code `AUTH_REQUIRED` |
| Unverified email | `403` with code `EMAIL_VERIFICATION_REQUIRED` — every authenticated route enforces this (`identity/account-access`) |
| Scoping | Every request is scoped to the authenticated account. A record belonging to another account returns `404`, never `403` — existence is not disclosed |

## 3. Error Envelope

Every non-2xx response uses one shape. Codes come from `16-error-catalog.md`; that catalog is the single source and this envelope never invents a code.

```json
{
  "error": {
    "code": "QUOTA_EXCEEDED",
    "message": "You have used all 5 profile research runs for this period.",
    "details": { "entitlement": "profile_research", "limit": 5, "resets_at": "2026-10-01T00:00:00Z" },
    "retry_after": null,
    "request_id": "req_01J..."
  }
}
```

- `message` is user-presentable. The client may show it directly.
- `details` is machine-readable and code-specific.
- `retry_after` is seconds, present on `429` and on retryable `503`.
- Field validation errors use `VALIDATION_FAILED` with `details.fields` mapping field path to message.

## 4. Pagination

Cursor-based. Offset pagination is not used.

```
GET /api/v1/jobs/matches?limit=25&cursor=eyJ...
```

```json
{ "data": [ ... ], "page": { "next_cursor": "eyJ...", "has_more": true } }
```

`limit` defaults to 25, maximum 100.

## 5. Idempotency

State-changing requests that may be retried accept `Idempotency-Key`. The key is required on publish actions and on billing mutations.

- Same key + same payload → the original response is replayed.
- Same key + different payload → `409` `IDEMPOTENCY_KEY_REUSED`.
- Keys are retained for 24 hours.

## 6. Long-Running Operations

**Every AI-backed operation is asynchronous** (`ADR-002`). All of them use one pattern, so the client has a single mechanism to implement.

Starting one returns `202` with an operation resource:

```json
{
  "operation": {
    "id": "op_01J...",
    "kind": "profile_research",
    "status": "queued",
    "created_at": "2026-09-14T10:00:00Z",
    "stages": [
      { "key": "searching",   "label": "Finding professional information", "status": "active" },
      { "key": "structuring", "label": "Structuring experience and skills", "status": "pending" },
      { "key": "checking",    "label": "Checking profile completeness",     "status": "pending" }
    ],
    "result": null
  }
}
```

Poll `GET /api/v1/operations/{id}`.

| `status` | Meaning |
|---|---|
| `queued` | Accepted, not started |
| `running` | In progress; `stages` reflects real progress |
| `succeeded` | `result` holds the resource or a link to it |
| `partial` | Some work completed; `result` holds it, `error` explains what did not (e.g. quota exhausted mid-batch) |
| `failed` | `error` holds the envelope; the client offers the recovery action named there |

**`stages` reflect measurable progress only.** The client must not render a synthetic percentage (`docs/05`).

Operations are scoped to the requesting account and expire from polling after 24 hours.

## 7. Endpoints by Capability

Every capability is listed. Where a capability is not directly API-exposed, that is stated explicitly.

### `identity/account-access`

| Method | Path | Notes |
|---|---|---|
| `POST` | `/auth/register` | Generic success regardless of whether the email exists |
| `POST` | `/auth/verify-email` | Consumes a single-use token |
| `POST` | `/auth/verify-email/resend` | |
| `POST` | `/auth/login` | Single generic failure message |
| `POST` | `/auth/logout` | Revokes the session server-side |
| `POST` | `/auth/password/forgot` | Identical response whether or not the account exists |
| `POST` | `/auth/password/reset` | Invalidates all sessions on success |
| `GET` | `/me` | Includes `onboarding_status` for resume routing |
| `PATCH` | `/me` | Name, timezone, locale |
| `POST` | `/me/exports` | `202` → operation; result is a signed, expiring link |
| `GET` | `/me/exports/{id}` | |
| `POST` | `/me/deletion` | Immediately halts automation and revokes sessions |
| `DELETE` | `/me/deletion` | Cancels within the erasure window |

### `career/profile-import`

| Method | Path | Notes |
|---|---|---|
| `POST` | `/profile/import` | Body `{ linkedin_url }`. `202` → operation. `409 LINKEDIN_URL_ALREADY_CLAIMED` when confirmed by another account |
| `POST` | `/profile/import/documents` | Multipart CV upload; type, size, and content validated |
| `POST` | `/profile/import/manual` | Skip research entirely |
| `GET` | `/profile/draft` | Draft with completeness, missing fields, conflicts |

### `career/career-profile`

| Method | Path | Notes |
|---|---|---|
| `GET` | `/profile` | Canonical profile with per-field provenance and confidence |
| `PATCH` | `/profile` | Edits are recorded as user-authored |
| `POST` | `/profile/confirm` | `422 PROFILE_INCOMPLETE` with `details.missing_fields` |
| `POST`/`PATCH`/`DELETE` | `/profile/{section}/{id}` | `section` ∈ experience, education, skills, projects, certifications |
| `POST` | `/profile/refresh` | `202` → operation producing a change set |
| `GET` | `/profile/change-sets` | Pending proposed changes |
| `POST` | `/profile/change-sets/{id}/changes/{changeId}/accept` | Per-change resolution |
| `POST` | `/profile/change-sets/{id}/changes/{changeId}/reject` | Records the evidence hash so it does not resurface |

### `career/goals-and-analysis`

| Method | Path | Notes |
|---|---|---|
| `GET`/`PUT` | `/goals` | Changing the goal marks dependents stale |
| `GET` | `/analysis` | Includes `is_stale` and `generated_at` |
| `POST` | `/analysis` | `202` → operation |
| `GET` | `/recommendations` | |
| `POST` | `/recommendations/{id}/complete` | |
| `POST` | `/recommendations/{id}/dismiss` | |
| `POST` | `/recommendations/{id}/apply-text` | Applies suggested text as user-authored |

### `planning/weekly-plan`

| Method | Path | Notes |
|---|---|---|
| `GET` | `/plans/current` | Week boundaries in the user's timezone |
| `GET` | `/plans` | Historical plans |
| `POST` | `/plans` | Manual regeneration; `202` → operation |
| `PATCH` | `/tasks/{id}` | Status, scheduled date |
| `POST` | `/tasks/{id}/replace` | `202` → operation; never returns the replaced task |

### `content/content-strategy`

| Method | Path | Notes |
|---|---|---|
| `GET` | `/content/strategy` | Current version |
| `POST` | `/content/strategy` | `202` → operation |
| `PATCH` | `/content/strategy` | User pillar and topic overrides; persist across refreshes |
| `GET` | `/content/ideas` | |
| `POST` | `/content/ideas` | `202` → operation |
| `POST` | `/content/ideas/{id}/dismiss` | |

### `content/post-authoring`

| Method | Path | Notes |
|---|---|---|
| `GET`/`POST` | `/posts` | `POST` from an idea; `202` → operation |
| `GET`/`PATCH`/`DELETE` | `/posts/{id}` | Manual edit becomes the current body |
| `POST` | `/posts/{id}/ai-actions` | `{ action: regenerate\|shorten\|expand\|change_tone\|improve_hook }`; `202` → operation |
| `GET` | `/posts/{id}/versions` | Revert source |
| `POST` | `/posts/{id}/versions/{versionId}/restore` | |
| `POST` | `/posts/{id}/approve` | `422 UNACKNOWLEDGED_CLAIM_FLAGS` if flags are outstanding |
| `POST` | `/posts/{id}/claim-flags/{flagId}/acknowledge` | |

> Editing an Approved post revokes approval and returns it to Needs Approval (`content/post-authoring`).

### `content/scheduling-and-publishing`

| Method | Path | Notes |
|---|---|---|
| `GET` | `/content/calendar?from=&to=` | Times in the user's timezone |
| `POST` | `/posts/{id}/schedule` | `{ local_time, timezone }`. `422 SCHEDULE_IN_PAST` |
| `PATCH` | `/schedules/{id}` | Reschedule; cancels and re-queues the publish job |
| `DELETE` | `/schedules/{id}` | |
| `POST` | `/posts/{id}/publish` | Requires `Idempotency-Key`. `503 PUBLISHING_PROVIDER_UNAVAILABLE` when none connected |
| `POST` | `/posts/{id}/mark-published` | Manual-path confirmation |
| `GET` | `/posts/{id}/publishing-attempts` | Full attempt history including errors |
| `GET` | `/integrations/publishing` | Connection state; `needs_reconnection` on expired authorization |

### `jobs/job-analysis`

| Method | Path | Notes |
|---|---|---|
| `POST` | `/jobs/analyze` | `{ job_url?, job_description? }`, at least one. `202` → operation. `422 NOT_A_JOB_POSTING`, `422 JOB_URL_UNREADABLE`, `409 PROFILE_NOT_CONFIRMED` |
| `GET` | `/jobs/{id}` | |
| `GET` | `/jobs/{id}/match` | Scores, recommendation, reasoning, preparation |

### `jobs/job-discovery`

| Method | Path | Notes |
|---|---|---|
| `GET` | `/jobs/matches` | Ranked, above threshold only. Never exposes the full collected set |
| `GET` | `/jobs/discovery/status` | `available: false` with a reason when no source is registered |
| `POST` | `/jobs/matches/{id}/save` | |
| `POST` | `/jobs/matches/{id}/skip` | Feeds ranking feedback |
| `GET`/`PUT` | `/jobs/preferences` | Includes `minimum_match_threshold` |

### `jobs/application-tracker`

| Method | Path | Notes |
|---|---|---|
| `GET`/`POST` | `/applications` | Duplicate save returns the existing record |
| `GET`/`PATCH`/`DELETE` | `/applications/{id}` | Status, notes, `next_action_at` |
| `GET` | `/applications/{id}/history` | Status transitions |

### `automation/automation-control`

| Method | Path | Notes |
|---|---|---|
| `GET` | `/automations` | Each row: description, frequency, approval mode, last run, next run, blocked precondition, plan requirement |
| `PATCH` | `/automations/{key}` | Per-automation override |
| `POST` | `/automations/preset` | `{ preset: assistant\|copilot\|autopilot }`; response names overrides that would be replaced |
| `POST` | `/automations/{key}/resume` | Clears suspension after repeated failure |
| `POST` | `/automations/pause` · `DELETE` | Global pause and resume |
| `GET` | `/automations/runs` | History with status, retries, errors |
| `GET` | `/approvals` | Pending approval requests |
| `POST` | `/approvals/{id}/approve` · `/reject` | |

### `notifications/notifications-and-digests`

| Method | Path | Notes |
|---|---|---|
| `GET` | `/notifications` | Filter by `category`, `unread` |
| `POST` | `/notifications/{id}/read` · `/notifications/read-all` | |
| `GET`/`PUT` | `/notifications/preferences` | Per-type channel choice and digest frequency |
| `POST` | `/notifications/unsubscribe` | Signed-token endpoint; unauthenticated by design |

### `billing/plans-and-entitlements`

| Method | Path | Notes |
|---|---|---|
| `GET` | `/billing/plans` | |
| `GET` | `/billing/subscription` | Status, period, grace period |
| `POST` | `/billing/checkout` · `/billing/portal` | Provider-hosted redirects |
| `GET` | `/billing/usage` | Usage against each quota with reset date |
| `POST` | `/webhooks/billing` | Unauthenticated, signature-verified, idempotent |

### `platform/ai-runtime`

Not directly API-exposed. It backs every `202` operation above. Two read endpoints exist for transparency:

| Method | Path | Notes |
|---|---|---|
| `GET` | `/me/ai-runs` | The user's own run records; no prompts or internal configuration |
| `POST` | `/feedback` | `{ entity_type, entity_id, feedback_type, value, note? }` |

### `platform/data-privacy-and-retention`

Not directly API-exposed as its own surface. Its user-facing operations are the export and deletion endpoints under `identity/account-access`; retention sweeps and audit logging are internal.

## 8. Rate Limiting

| Group | Applies to |
|---|---|
| Authentication | register, login, password reset, verification resend |
| Research | profile import and refresh |
| AI generation | every `202` operation start |
| General | all other authenticated routes |

Exceeding a limit returns `429` `RATE_LIMITED` with `retry_after`. Limits are per account and per IP; exact values are operational configuration, not contract.

## 9. Contract Rules

1. A response never contains another account's data, and a cross-account identifier returns `404`.
2. Quota is checked **before** provider cost is incurred; exhaustion returns `402 QUOTA_EXCEEDED` before the operation starts (`billing/plans-and-entitlements`).
3. An absent provider returns a `503` naming the capability, never a generic failure or an empty success.
4. `partial` is a first-class operation outcome, not an error.
5. Every error code in a response exists in `16-error-catalog.md`.
