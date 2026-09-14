# Error Catalog

> The single source of error codes. `08-api-contract.md`'s error envelope, `05-screen-requirements-ux-states.md`'s error states, and `13-observability-and-runbooks.md`'s failure metrics all reference codes from this table — none of them mints a new one. A code appearing in a spec's failure scenario but not here is a gap to close before that scenario ships.

## 1. Format

Every code is `SCREAMING_SNAKE_CASE`, globally unique, and stable — once shipped, a code is never repurposed for a different condition. Retiring a code is a breaking change to the API contract.

| Column | Meaning |
|---|---|
| Code | The stable identifier, returned in `error.code` |
| HTTP | Status the API returns for this condition |
| User message | The `error.message` template shown to the user |
| Recovery | The action the interface offers |
| Governing capability | The spec requirement this code exists to satisfy |

## 2. Authentication & Session

| Code | HTTP | User message | Recovery | Capability |
|---|---|---|---|---|
| `AUTH_REQUIRED` | 401 | "Sign in to continue." | Redirect to sign-in | `identity/account-access` |
| `INVALID_CREDENTIALS` | 401 | "Email or password is incorrect." | Retry; forgot-password link | `identity/account-access` |
| `EMAIL_VERIFICATION_REQUIRED` | 403 | "Verify your email to continue." | Resend verification | `identity/account-access` |
| `OTP_INVALID` | 422 | "That code is incorrect." | Retry; resend after repeated failure | `identity/account-access` |
| `OTP_EXPIRED` | 410 | "This code has expired." | Request a new code | `identity/account-access` |
| `OTP_ATTEMPTS_EXCEEDED` | 429 | "Too many incorrect attempts." | Request a new code | `identity/account-access` |
| `RESET_LINK_INVALID` | 410 | "This reset link is invalid or has expired." | Request a new link | `identity/account-access` |
| `ACCOUNT_THROTTLED` | 429 | "Too many attempts. Try again shortly." | Show `retry_after` | `identity/account-access` |
| `SESSION_EXPIRED` | 401 | "Your session has ended. Sign in again." | Redirect to sign-in | `identity/account-access` |

## 3. Validation & Access

| Code | HTTP | User message | Recovery | Capability |
|---|---|---|---|---|
| `VALIDATION_FAILED` | 422 | Field-specific, from `details.fields` | Correct the field | all |
| `RATE_LIMITED` | 429 | "You're doing that too fast. Try again shortly." | Show `retry_after` | `08` §8 |
| `IDEMPOTENCY_KEY_REUSED` | 409 | — (internal; client should not retry with a stale key) | Generate a new key | `08` §5 |
| `RESOURCE_NOT_FOUND` | 404 | "We couldn't find that." | Return to the list | all — also returned for cross-account access, never `403` |

## 4. Profile Import (`career/profile-import`)

| Code | HTTP | User message | Recovery |
|---|---|---|---|
| `INVALID_LINKEDIN_URL` | 422 | "Enter a valid LinkedIn profile URL." | Correct and resubmit |
| `LINKEDIN_URL_ALREADY_CLAIMED` | 409 | "This profile is already linked to another account." | Support/verification path |
| `RESEARCH_NO_DATA_FOUND` | 200 (partial outcome) | "We couldn't find public information for this profile." | Retry, CV upload, manual entry |
| `RESEARCH_FAILED` | 502 | "We couldn't complete your profile research." | Retry, CV upload, manual entry |
| `RESEARCH_QUOTA_EXCEEDED` | 402 | "You've used all your profile research runs for this period." | Upgrade, or use CV/manual entry |
| `CV_FILE_UNSUPPORTED` | 422 | "This file type isn't supported." | Upload a supported format |
| `CV_FILE_TOO_LARGE` | 422 | "This file is too large." | Upload a smaller file |
| `CV_FILE_INVALID` | 422 | "We couldn't read this file." | Re-upload or use manual entry |

## 5. Career Profile (`career/career-profile`)

| Code | HTTP | User message | Recovery |
|---|---|---|---|
| `PROFILE_INCOMPLETE` | 422 | "A few required fields are missing before you can confirm." | Fill `details.missing_fields` |
| `PROFILE_NOT_CONFIRMED` | 409 | "Confirm your profile first." | Redirect to profile review |
| `CHANGE_SET_ALREADY_RESOLVED` | 409 | "This change was already reviewed." | Refresh the change list |

## 6. Goals & Analysis (`career/goals-and-analysis`)

| Code | HTTP | User message | Recovery |
|---|---|---|---|
| `ANALYSIS_UNAVAILABLE` | 502 | "We couldn't generate your analysis right now." | Show last successful analysis; retry |
| `GOAL_REQUIRED` | 422 | "Choose a primary goal to continue." | Complete goal setup |

## 7. Weekly Plan (`planning/weekly-plan`)

| Code | HTTP | User message | Recovery |
|---|---|---|---|
| `PLAN_GENERATION_FAILED` | 502 | "We couldn't prepare this week's plan." | Retry; shown in automation history |
| `PLAN_ALREADY_EXISTS` | 200 (no-op) | — (internal; not user-facing) | — |
| `TASK_NOT_REPLACEABLE` | 409 | "This task can't be replaced right now." | Refresh the plan |

## 8. Content Strategy & Ideas (`content/content-strategy`)

| Code | HTTP | User message | Recovery |
|---|---|---|---|
| `STRATEGY_GENERATION_FAILED` | 502 | "We couldn't update your content strategy." | Previous strategy remains active; retry |
| `IDEA_GENERATION_FAILED` | 502 | "We couldn't generate new ideas right now." | Retry |

## 9. Post Authoring (`content/post-authoring`)

| Code | HTTP | User message | Recovery |
|---|---|---|---|
| `POST_GENERATION_FAILED` | 502 | "We couldn't generate a draft right now." | Idea preserved; retry |
| `AI_EDIT_FAILED` | 502 | "That edit didn't complete." | Draft unchanged; retry |
| `UNACKNOWLEDGED_CLAIM_FLAGS` | 422 | "Review the flagged statements before approving." | Acknowledge each flag |
| `POST_NOT_EDITABLE` | 409 | "This post can't be edited in its current state." | Refresh |

## 10. Scheduling & Publishing (`content/scheduling-and-publishing`)

| Code | HTTP | User message | Recovery |
|---|---|---|---|
| `SCHEDULE_IN_PAST` | 422 | "Choose a time in the future." | Pick a new time |
| `POST_NOT_APPROVED` | 409 | "Approve this post before scheduling it." | Approve first |
| `PUBLISHING_PROVIDER_UNAVAILABLE` | 503 | "Automatic publishing isn't available yet. Use the manual option." | Copy + open LinkedIn |
| `PUBLISH_AUTHORIZATION_EXPIRED` | 502 | "Your LinkedIn connection needs to be renewed." | Reconnect; falls back to manual |
| `PUBLISH_RATE_LIMITED` | 429 | "LinkedIn is temporarily limiting publishing. We'll retry." | Automatic backoff retry |
| `PUBLISH_FAILED` | 502 | "Publishing failed." | Retry; manual fallback |
| `MANUAL_PUBLISH_NOT_CONFIRMED` | 200 (reminder) | "Did you publish this post?" | Confirm or leave pending |

## 11. Job Analysis & Discovery (`jobs/job-analysis`, `jobs/job-discovery`)

| Code | HTTP | User message | Recovery |
|---|---|---|---|
| `JOB_INPUT_REQUIRED` | 422 | "Paste a job URL or description." | Provide one |
| `JOB_URL_UNREADABLE` | 422 | "We couldn't read that job posting. Try pasting the description instead." | Paste description |
| `NOT_A_JOB_POSTING` | 422 | "That doesn't look like a job posting." | Check the input |
| `JOB_ANALYSIS_FAILED` | 502 | "We couldn't analyze this job right now." | Job content preserved; retry |
| `DISCOVERY_UNAVAILABLE` | 200 (status) | "Automatic job discovery isn't set up yet." | Use manual analysis |
| `DISCOVERY_SOURCE_FAILED` | — (internal, per-adapter) | — | Other sources continue; logged |

## 12. Application Tracker (`jobs/application-tracker`)

| Code | HTTP | User message | Recovery |
|---|---|---|---|
| `APPLICATION_ALREADY_SAVED` | 200 (returns existing) | "This job is already saved." | View existing record |

## 13. Automation (`automation/automation-control`)

| Code | HTTP | User message | Recovery |
|---|---|---|---|
| `AUTOMATION_PRECONDITION_UNMET` | 409 | Names the specific missing precondition | Link to resolve it |
| `AUTOMATION_NOT_IN_PLAN` | 402 | "This automation isn't included in your plan." | Upgrade |
| `AUTOMATION_SUSPENDED` | 409 | "This automation was paused after repeated failures." | Explicit re-enable |
| `APPROVAL_EXPIRED` | 410 | "This approval request expired. The action was not taken." | — |
| `APPROVAL_ALREADY_RESOLVED` | 409 | "This request was already handled." | Refresh |

## 14. Notifications (`notifications/notifications-and-digests`)

| Code | HTTP | User message | Recovery |
|---|---|---|---|
| `EMAIL_UNDELIVERABLE` | — (internal) | "We couldn't deliver email to this address." (shown at sign-in) | Update email address |

## 15. Billing (`billing/plans-and-entitlements`)

| Code | HTTP | User message | Recovery |
|---|---|---|---|
| `QUOTA_EXCEEDED` | 402 | "You've used all {limit} {entitlement} for this period." | Show reset date; upgrade path |
| `AUTOMATION_QUOTA_SKIPPED` | — (internal, `AutomationRun.skip_reason`) | — | Resumes at period reset |
| `FEATURE_NOT_IN_PLAN` | 402 | "This feature isn't included in your plan." | Upgrade |
| `PAYMENT_FAILED` | — (internal, webhook-driven) | "We couldn't process your payment." (email) | Update payment method |
| `SUBSCRIPTION_PAST_GRACE_PERIOD` | — (internal) | "Your subscription has ended and your plan reverted to Free." | Resubscribe |
| `BILLING_PROVIDER_UNAVAILABLE` | — (internal; never revokes entitlements) | — | Reconciled automatically |

## 16. Platform / AI Runtime (`platform/ai-runtime`)

| Code | HTTP | User message | Recovery |
|---|---|---|---|
| `AI_PROVIDER_UNAVAILABLE` | 503 | "This feature is temporarily unavailable." | Retry later; unaffected features still work |
| `AI_OUTPUT_INVALID` | 502 | "We couldn't generate a usable result." | Retry |
| `AI_INSUFFICIENT_CONTEXT` | 200 (defined outcome) | Feature-specific ("Not enough information to generate this yet.") | Add the missing profile information |
| `OPERATION_TIMED_OUT` | 504 | "This is taking longer than expected." | Retry; check status later |

## 17. Data & Privacy (`platform/data-privacy-and-retention`)

| Code | HTTP | User message | Recovery |
|---|---|---|---|
| `EXPORT_FAILED` | 502 | "We couldn't prepare your export." | Retry |
| `EXPORT_LINK_EXPIRED` | 410 | "This download link has expired." | Request a new export |
| `DELETION_ALREADY_PENDING` | 409 | "Account deletion is already in progress." | Cancel if within the window |

## 18. Generic Fallback

| Code | HTTP | User message | Recovery |
|---|---|---|---|
| `INTERNAL_ERROR` | 500 | "Something went wrong on our end." | Retry; contact support if it persists |
| `SERVICE_UNAVAILABLE` | 503 | "This part of the service is temporarily unavailable." | Retry with backoff |

**`INTERNAL_ERROR` is a last resort, not a default.** Every failure scenario in a capability spec should resolve to a specific code above before this one is used. A rising `INTERNAL_ERROR` rate in `13`'s dashboards means this catalog has a gap.

## 19. Coverage Rule

Before a capability spec's failure or error scenario ships, it must map to a code in this table. When a new failure mode is discovered during implementation, it is added here first — with HTTP status, user message, recovery, and governing capability — before the code is referenced anywhere else.
