# Security, Privacy & Compliance

> Normative behavior is in `platform/data-privacy-and-retention` and `identity/account-access`. This document adds the threat model, the classification and retention tables, and the operational procedures a spec should not carry.
>
> **Baseline assumption:** GDPR-grade obligations are applied as the strictest baseline. If launch is confirmed US-only, §6 response times and §5 windows may be relaxed — no requirement in any spec changes. (`docs/00-README.md`, Assumptions.)

## 1. What Makes This Product Sensitive

Three properties drive the whole document:

1. **The data is a person's professional identity** — career history, employer names, job-search activity. A job-search leak can cost someone their current job.
2. **The product acts publicly as the user.** A compromised account can publish to a real professional audience under a real name.
3. **Personal data is sent to an AI provider** on every generation, so prompt content is a disclosure surface, not just an implementation detail.

## 2. Threat Model

| # | Threat | Impact | Controls |
|---|---|---|---|
| T1 | Credential stuffing / brute force | Account takeover → public posting as the user | Password strength policy, per-account and per-IP throttling, notification on repeated failures, session revocation on reset |
| T2 | Session hijacking | Same as T1 | `HttpOnly`+`Secure`+`SameSite` cookies, server-side revocation, expiry, reset invalidates all sessions |
| T3 | **Horizontal privilege escalation** — reaching another account's records by identifier manipulation | Exposure of career history and job-search activity | Every query scoped to the authenticated account; cross-account identifiers return `404`; enforced at the data-access layer, not per controller |
| T4 | **Profile identity theft** — claiming another person's LinkedIn URL | Impersonation; import of a third party's professional data | Confirmed URLs cannot be re-claimed; blocked with a verification path (`career/profile-import`) |
| T5 | Prompt injection via researched web content or a pasted job description | Model induced to emit false profile data or unsafe content | Model output is data, never instructions; schema validation before persistence; research output requires user confirmation; job parsing returns `is_job_posting` |
| T6 | **Unauthorized publishing** | Reputational harm, unrecoverable | Approval required by default in Copilot; approval expiry means the action does not happen; full audit with approval reference; idempotency prevents duplicates |
| T7 | Malicious CV upload | Server compromise, stored XSS | Type, size, and content validation; private bucket; never served from the app origin; signed expiring URLs |
| T8 | Secret leakage into logs, exports, or prompts | Credential compromise | Secrets excluded by classification rule (§3); export excludes them; prompts exclude them |
| T9 | Billing webhook forgery | Entitlement fraud | Signature verification, idempotent processing |
| T10 | AI cost exhaustion (accidental or deliberate) | Financial damage | Pre-spend quota checks, token ceilings, aggregate thresholds (`09` §6) |
| T11 | Insider or operator over-access | Exposure of user data | Least privilege, authenticated operator access, audit log |
| T12 | Data exfiltration through the export feature | Bulk personal data leak | Export requires an authenticated session; delivered by short-lived signed link; export events audited |

## 3. Data Classification

Every class in `platform/data-privacy-and-retention` with its handling rule.

| Class | Examples | At rest | In export | In AI prompts | In logs |
|---|---|---|---|---|---|
| **Account credentials** | Password, reset and verification tokens, session tokens, third-party OAuth tokens | Passwords salted-hashed; tokens hashed or encrypted | **Never** | **Never** | **Never** |
| **Personal identity data** | Name, email, timezone, locale | Standard | Yes | Only when the task needs it | Identifiers only |
| **Professional profile data** | Experience, education, skills, projects, certifications, LinkedIn URL | Standard | Yes | Minimized per task | **Never** — identifiers only |
| **User-generated content** | Post drafts and bodies, notes, corrections | Standard | Yes | Yes, when the task needs it | **Never** |
| **Derived AI output** | Analyses, recommendations, plans, matches, `AIRun` inputs and outputs | Standard | Yes | As prior-context input | **Never** |
| **Operational telemetry** | Request logs, job records, metrics, audit log | Standard | Audit entries concerning the user | **Never** | Yes, by design |
| **Payment data** | Card details | **Not stored** — provider-held | Subscription status only | **Never** | Provider references only |

**The logging rule that matters:** logs identify records by identifier and never embed profile content, post bodies, or personal identifiers beyond what diagnosis requires. A log line naming an employer is a defect.

## 4. Third-Party Disclosure

| Recipient | Receives | Basis |
|---|---|---|
| AI provider | Task-minimized career context; researched public content; pasted job descriptions | Service provision; disclosed before the user submits their URL (`ADR-005`) |
| Email provider | Email address, notification content | Service provision |
| Payment provider | Email, payment details entered directly with them | Contract |
| Object storage | Uploaded CVs | Service provision; private, signed access only |
| Job source adapters | Search criteria — target roles, locations, remote preference | Service provision; **never** the user's identity |

**Disclosure timing.** The profile-import screen explains, before submission, that public sources will be searched and an AI provider will process the result. This is a precondition of the screen, not fine print (`career/profile-import`).

## 5. Retention Schedule

Enforced by scheduled sweeps against `RetentionPolicy` (`docs/06` §7.11). Periods run from record creation unless stated.

| Data | Retention | Strategy |
|---|---|---|
| `AIRun` inputs and outputs | 90 days | Delete payloads; keep anonymized metrics |
| `AIRun` metadata (model, version, tokens, cost, latency) | 24 months | Anonymize — needed for cost and quality trends |
| Notification content | 12 months | Delete |
| Notification delivery records | 90 days | Delete |
| Automation run history | 12 months | Delete — must cover the window `automation/automation-control` promises the user |
| Uploaded CVs | 12 months after last use, or immediately on request | Delete |
| Superseded profile versions and `FieldProvenance.original_value_json` | 24 months | Delete |
| Resolved profile change sets | 12 months | Delete |
| Operational logs | 30 days | Delete |
| Audit log | 24 months | Retain — it is the record of what the system did as the user |
| Billing records | 7 years | Retain (legal obligation) |
| Soft-deleted account data | Erased within 30 days of request | Delete or anonymize |

**Retention must not break the product.** A feature referencing an expired record — an old automation run, a purged AI run — handles its absence gracefully rather than erroring (`platform/data-privacy-and-retention`).

## 6. Data Subject Requests

| Request | Route | Response time | Procedure |
|---|---|---|---|
| Access / export | Self-service, `POST /me/exports` | Immediate; delivered within 30 days worst case | Queued export → signed link, expiring per §7 |
| Correction | Self-service profile editing | Immediate | Correction becomes canonical and enters future generation (`ADR-004`) |
| Erasure | Self-service, `POST /me/deletion` | Effective immediately; complete within 30 days | §6.1 |
| Restriction | Support | 30 days | Global automation pause + account suspension |
| Portability | Same as export | 30 days | Machine-readable JSON |
| Objection to automated processing | Self-service | Immediate | Global pause; Assistant level; no automated public action |

### 6.1 Erasure procedure

1. **Immediately on request:** disable all automations, cancel scheduled publishing, cancel queued jobs, revoke all sessions, set `deletion_requested_at`.
2. **In-flight jobs:** every job checks the deletion marker before persisting. Output produced after the marker is discarded, not written (`identity/account-access`).
3. **Within the window:** delete or irreversibly anonymize across the database, object storage, `AIRun` records, notification history, and queues.
4. **Retained obligations:** billing records reduced to the minimum legally required fields, documented in §5.
5. **Downstream:** request deletion from the AI provider per its retention policy; record that the request was made.
6. **Confirm** completion to the user's address before it is purged.

**Cancellation:** deletion may be cancelled within the window via `DELETE /me/deletion`; automation stays off until the user re-enables it.

## 7. Security Controls Baseline

Required before production.

**Authentication and session:** salted password hashing with a modern KDF; strength policy; per-account and per-IP throttling; single-use time-limited tokens; `HttpOnly`+`Secure`+`SameSite=Lax` cookies with server-side revocation; all sessions invalidated on password reset.

**Authorization:** account scoping enforced at the data-access layer; cross-account identifiers return `404`; operator access separately authenticated and audited.

**Transport and storage:** TLS everywhere; HSTS; encryption at rest for third-party tokens; private buckets with signed URLs expiring in ≤ 15 minutes; secrets in a secret manager, never in the repository.

**Input handling:** validation on every endpoint; parameterized queries; output encoding; CSRF on cookie-authenticated state changes; upload type, size, and content validation; signature verification on webhooks.

**Rate limiting:** per the groups in `08` §8 — authentication, research, AI generation, and general.

**Dependencies:** automated vulnerability scanning in CI; a documented patch window for critical advisories.

**Headers:** `Content-Security-Policy`, `X-Content-Type-Options`, `Referrer-Policy`, `Permissions-Policy`.

## 8. Incident Response

| Phase | Action |
|---|---|
| **Detect** | Alerting per `13-observability-and-runbooks.md`; any report reaching support is treated as credible until assessed |
| **Assess** | Scope from audit and access logs: which accounts, which classes, what window |
| **Contain** | Revoke sessions, rotate secrets, disable the affected path, pause automation if public actions are implicated |
| **Notify** | Affected users without undue delay; within **72 hours** of becoming aware where a regulator must be notified. Notice states what was exposed, when, and what the user should do |
| **Recover** | Restore from backup if integrity is affected; force password reset on credential compromise |
| **Review** | Written post-incident review; remediation tracked to closure |

**Credential compromise** has a standing response: invalidate all sessions for affected accounts and force a password reset (`platform/data-privacy-and-retention`).

## 9. Pre-Launch Checklist

- [ ] Threat-model controls in §2 implemented and tested
- [ ] Account scoping verified by test — a cross-account identifier returns `404`
- [ ] Classification rules enforced: no secrets in logs, exports, or prompts
- [ ] Retention sweeps running and verified against §5
- [ ] Export and erasure exercised end to end on a real account
- [ ] Erasure verified against in-flight background jobs
- [ ] Privacy policy and terms published, matching §4 and §5
- [ ] Disclosure copy present on the profile-import screen before submission
- [ ] Backup restore rehearsed
- [ ] Incident contacts and escalation path documented
- [ ] Dependency scanning active in CI
