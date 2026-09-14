# Billing & Entitlements

> This document owns the **values**: which operations are metered, what each plan includes, and the concrete rules for upgrade, downgrade, payment failure, and cancellation. Behavior is normative in `billing/plans-and-entitlements`. No quota number appears anywhere else in the pack.
>
> Prices are a **hypothesis** (`01-product-vision.md`), revisable without changing any spec. The quota *mechanism* is not.

## 1. Principles

1. **Meter what costs money.** AI provider calls are the variable cost. Nothing else is metered.
2. **Check before spending.** Entitlement is verified before the provider is called, never after (`billing/plans-and-entitlements`).
3. **Free must be genuinely usable.** Profile import, confirmation, manual job analysis, and manual publishing always work. The free tier demonstrates value; it is not a demo.
4. **Never destroy data on downgrade.** Entitlements shrink; user data does not.
5. **Failure is not billable.** An operation producing no usable result consumes no quota.

## 2. Metered Operations

| Entitlement key | Triggered by | Cost tier | Notes |
|---|---|---|---|
| `profile_research` | Profile import, profile refresh | **High** | Most expensive operation; cache hits do not consume |
| `profile_analysis` | Analysis generation, monthly health check | Medium | |
| `weekly_plan` | Plan generation | Medium | |
| `content_strategy` | Strategy generation and refresh | Medium | |
| `content_ideas` | Idea generation | Low | Metered per batch, not per idea |
| `post_generation` | Draft generation **and** each AI edit action | Medium | An edit costs the same as a draft — see §4 |
| `job_analysis` | Manual analysis, automated matching | Low | De-duplicated analyses do not consume |
| `performance_optimization` | Weekly optimization pass | Low | |

Not metered: scheduling, manual publishing, the application tracker, notifications, digests, exports, and all reads.

## 3. Plan Matrix

Per billing period (calendar month).

| Entitlement | Free | Pro | Pro+ |
|---|---|---|---|
| **Price** | $0 | $12 / mo | $24 / mo |
| `profile_research` | 2 | 6 | 15 |
| `profile_analysis` | 1 | 10 | Unlimited* |
| `weekly_plan` | 2 | Unlimited* | Unlimited* |
| `content_strategy` | 1 | 8 | Unlimited* |
| `content_ideas` (batches) | 2 | 20 | Unlimited* |
| `post_generation` | 5 | 60 | 200 |
| `job_analysis` | 10 | 150 | 500 |
| `performance_optimization` | — | 4 | Unlimited* |
| **Feature gates** | | | |
| Manual publishing | ✓ | ✓ | ✓ |
| Content scheduling | — | ✓ | ✓ |
| Automated job discovery | — | ✓ | ✓ |
| Job digests | — | ✓ | ✓ |
| Autopilot level | — | — | ✓ |
| Profile refresh automation | — | ✓ | ✓ |
| Automation history retention | 30 days | 12 months | 12 months |

\* "Unlimited" is subject to the fair-use ceiling in §7. It is not literally unbounded.

**Why these shapes.** Free gets one full pass through the product — import, confirm, analyze, plan, generate a few posts, analyze a handful of jobs — which is exactly the loop the MVP is validating. Scheduling and automated discovery are the first paid gates because they are where the "agent works while I don't" promise starts, and they carry ongoing background cost.

## 4. Metering Rules

**A metered operation consumes exactly one unit on success.** Batches consume one unit per batch, not per item.

| Situation | Consumes? |
|---|---|
| Operation succeeds | Yes |
| Operation fails with no usable result | **No** |
| Schema validation fails | **No** |
| Cache hit (research within window, de-duplicated job) | **No** |
| User forces a refresh, bypassing cache | **Yes** |
| AI edit action on an existing draft | **Yes** — it is a provider call |
| Restoring a previous post version | No — no provider call |
| Retry of a failed operation | Yes, on the attempt that succeeds |
| Partial batch result | Yes, for items actually produced |

**AI edit actions are metered.** Regenerate, shorten, expand, change tone, and improve hook each cost a provider call. The interface should make remaining `post_generation` budget visible in the post editor so this is never a surprise.

## 5. Enforcement

### User-initiated, no quota remaining

Refuse **before** calling the provider. `402 QUOTA_EXCEEDED` with the limit, current usage, and reset date. The interface names the limit and offers an upgrade path — and, where one exists, the unmetered alternative (manual entry instead of research; manual publishing instead of scheduling).

### Automation, no quota remaining

Skip the run. Record it as **skipped with a quota reason — never as failed**, since nothing malfunctioned. Notify at most **once per entitlement per period**. Automation resumes automatically at period reset (`billing/plans-and-entitlements`).

### Approaching the limit

At **80% usage** of any entitlement, notify once per entitlement per period.

### Quota exhausted mid-batch

Persist everything already produced, stop cleanly, return operation status `partial` (`08` §6). Completed job matches are kept and surfaced. **Never discard finished work to make an accounting boundary tidy.**

## 6. Subscription Lifecycle

| Event | Behavior |
|---|---|
| **Upgrade** | Immediate. Quotas raised to the new plan within the current period; usage carries over, it does not reset. Charged prorated by the provider |
| **Downgrade** | Takes effect at **period end**, so nothing the user paid for is removed early. At transition: usage above the new quota blocks further metered work until the next reset; excluded automations are disabled with an explanation; **no data is deleted** |
| **Downgrade with scheduled content** | Scheduled posts are retained as **Approved drafts** with manual publishing available. The user is told exactly what changed and which posts are affected |
| **Payment failure** | Notify immediately. **7-day grace period** at full entitlements. Provider retries. After grace: downgrade to Free entitlements, data intact, automations disabled with an explanation |
| **Cancellation** | Paid entitlements persist to the end of the paid period. Then Free entitlements, data intact. No content is deleted |
| **Reactivation** | Entitlements restored immediately; disabled automations require explicit re-enable — the system never silently resumes acting publicly on the user's behalf |
| **Billing provider unreachable** | **Never revoke entitlements on this basis.** Serve the last known state and reconcile when reachable (`billing/plans-and-entitlements`) |

### Period boundaries

- The period follows the subscription anchor date, not the calendar month, except on Free where it follows the registration anniversary.
- At reset: usage counters zero, quota-suspended automations resume, warning flags clear.
- Unused quota does **not** roll over.

## 7. Fair Use

"Unlimited" entitlements carry a ceiling at **10× the Pro allowance** for the same entitlement, to bound abuse and runaway cost. Crossing it throttles rather than blocks, and alerts operators. A user is contacted before any hard action.

This exists for T10 in `10-security-privacy-compliance.md` and for the aggregate ceiling in `09` §6 — not as a hidden limit to enforce quietly.

## 8. Visibility

The user can always see current usage against each quota and the reset date (`GET /billing/usage`). Specifically:

- The account screen shows every entitlement with usage and reset date.
- The post editor shows remaining `post_generation` budget, since AI edits consume it.
- A blocked action names the specific entitlement, not a generic "upgrade required".
- A quota-skipped automation shows its skip reason in automation history.

## 9. Open Pricing Questions

Owned by product; none block implementation:

1. Whether Pro+ is worth launching at MVP, or whether Free and Pro alone suffice to validate willingness to pay.
2. Whether `profile_research` should be sold as a top-up rather than capped, given it is both the most expensive operation and the strongest first-value moment.
3. Annual pricing.
4. Regional pricing.
