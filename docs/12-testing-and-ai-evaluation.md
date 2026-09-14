# Testing & AI Evaluation

> Two distinct problems. Deterministic code is **tested** — same input, same output, pass or fail. AI capabilities are **evaluated** — output varies, so quality is measured as a rate against thresholds. Conflating them produces either flaky test suites or unmeasured AI quality.

## 1. Test Pyramid

| Layer | Scope | Target | Runs |
|---|---|---|---|
| **Unit** | Pure logic: scoring, fingerprints, timezone resolution, quota arithmetic, filtering | Fast, isolated, no I/O | Every commit |
| **Integration** | Domain services against a real database and queue; AI provider stubbed with recorded schema-valid fixtures | The bulk of meaningful coverage | Every commit |
| **Contract** | Every `08-api-contract.md` endpoint: status codes, error envelope, pagination, auth, scoping | One per endpoint minimum | Every commit |
| **End-to-end** | The critical journeys in §3 through the real client | Few, high-value, stable | Every PR to main |
| **Evaluation** | AI capability quality (§5) | Rate-based, not pass/fail per run | Nightly + before any AI change ships |

**Scenario-to-test mapping.** Every `#### Scenario:` in the 16 capability specs is a test case. A scenario with no test is untested behavior — this is the completeness measure, not a line-coverage percentage.

## 2. Non-Negotiable Test Cases

These encode the rules most expensive to get wrong. Each must have an explicit test.

| Area | Test |
|---|---|
| **Account scoping** | A valid identifier belonging to another account returns `404`, on every resource type |
| **Deletion safety** | A job executing when deletion is confirmed discards its output instead of persisting |
| **Publish idempotency** | The same publish job executed twice publishes once |
| **Expired publish authorization** | Does not blind-retry; marks reconnection needed; falls back to manual |
| **Approval expiry** | An expired approval means the action **did not happen** |
| **Approval revocation** | Editing an Approved post returns it to Needs Approval |
| **Quota before spend** | An exhausted quota refuses before any provider call is made |
| **Partial batch** | Quota exhausted mid-batch persists completed items and returns `partial` |
| **Schema rejection** | An out-of-enum recommendation value is rejected, never coerced |
| **Unconfirmed profile** | Content and match generation are blocked before confirmation |
| **Correction authority** | A refresh never overwrites a user-corrected field |
| **Rejected proposal** | Does not resurface while its evidence hash is unchanged |
| **DST — skipped hour** | Post publishes exactly once, at the defined adjacent instant |
| **DST — repeated hour** | Post publishes exactly once, not twice |
| **Timezone change** | Scheduled items keep intended local wall-clock time |
| **Empty digest** | Not sent |
| **Duplicate notification** | An outstanding unread notification is not duplicated |
| **Secret exclusion** | Export, logs, and assembled AI context contain no credentials |
| **No-provider state** | Product fully usable with no `PublishingProvider` and no `JobSource` |

The last row is worth its own e2e run: it is the central promise of `ADR-006`.

## 3. Critical End-to-End Journeys

1. Register → verify → import profile → review and correct → confirm → set goals → reach dashboard with analysis and first plan.
2. Idea → draft → AI edit → acknowledge claim flag → approve → schedule → manual publish → mark published.
3. Paste job description → analyze → review breakdown → save → mark applied → advance status.
4. Change automation preset → hit an approval request → approve → verify the audited action.
5. Exhaust a quota → verify the refusal names the limit → upgrade → verify immediate access.
6. Request export → download → request deletion → verify automation stops and in-flight work is discarded.

## 4. Test Data

Fixture profiles mirror the evaluation cases in §5 so testing and evaluation share one corpus. All fixtures are **synthetic** — no real person's professional data in any test environment. Provider responses are recorded once as schema-valid fixtures and replayed; integration tests never call a live AI provider.

## 5. AI Evaluation

### 5.1 The ten cases

Required before any capability drives user-visible output (`platform/ai-runtime`).

| # | Case | What it stresses |
|---|---|---|
| **E1** | Rich LinkedIn profile | Baseline quality with abundant evidence |
| **E2** | Sparse public profile | Must not fabricate to fill gaps; must attribute low scores to missing data |
| **E3** | Conflicting public data | Must surface the conflict, not silently pick |
| **E4** | Career changer | Must not over-anchor on the prior field |
| **E5** | Senior professional | Seniority calibration; advice must not read as junior |
| **E6** | Junior professional | Must not invent seniority or unsupported scope |
| **E7** | User with no public posts | Content generation with zero prior voice signal |
| **E8** | Job with excellent fit | `STRONG_APPLY` consistency, honest reasoning |
| **E9** | Job with poor fit | Must produce `SKIP` rather than a soft positive |
| **E10** | Content generation with limited evidence | Fewer, safer ideas; tells the user what to add |

### 5.2 The six dimensions and thresholds

| Dimension | Measurement | Threshold | Gate |
|---|---|---|---|
| **Schema validity** | Share of calls producing valid structured output | **≥ 99.5%** | Hard — below this the capability does not ship |
| **Factual grounding** | Share of claims traceable to canonical profile data | **≥ 99%** | Hard |
| **Unsupported claims** | Share of outputs containing ≥1 ungrounded professional claim | **≤ 0.5%**, and **0%** for anything publishable | Hard — the highest-consequence metric |
| **Relevance** | Graded 1–5 against the user's goals | **≥ 4.0** mean, **≤ 5%** below 3 | Soft |
| **Repetition** | Similarity to the user's prior output | **≤ 15%** near-duplicate rate | Soft |
| **Match consistency** | Score variance across repeated runs on the same job; recommendation agreement with score bands | **≤ 10%** variance; **100%** band agreement | Hard for band agreement |

**Hard** gates block release. **Soft** gates are tracked and regressions are investigated, but do not block.

**Zero tolerance on publishable unsupported claims.** A post is the one output that goes to a real professional audience under a real name; a fabricated achievement there is unrecoverable.

### 5.3 Grading

- **Schema validity, repetition, match consistency** — automated, deterministic.
- **Factual grounding and unsupported claims** — automated claim extraction cross-checked against the canonical profile, with **human review of every flagged case**. Automation triages; it does not decide.
- **Relevance** — human-graded on a sampled subset.

An LLM judge may pre-screen relevance, but never decides a hard gate. Grading the model with the model is not evidence for the metric that stops a fabrication reaching the public.

### 5.4 Running evaluation

| When | Scope |
|---|---|
| Any prompt, schema, or model change | Full suite for the affected capability; version increments (`ADR-003`) |
| Nightly | Full suite, all capabilities, trended |
| Before connecting a capability to a public or irreversible action | Full suite; all hard gates met |
| On a production quality incident | Reproduce as a new permanent case |

Results are recorded per `agent_version` so regressions attribute to a specific change.

## 6. CI Gates

| Gate | Blocks merge | Blocks release |
|---|---|---|
| Unit, integration, contract tests pass | ✓ | ✓ |
| `openspec validate --strict` passes | ✓ | ✓ |
| Dependency vulnerability scan (critical) | ✓ | ✓ |
| E2E journeys (§3) | — | ✓ |
| AI hard gates (§5.2) for changed capabilities | — | ✓ |
| Non-negotiable cases (§2) | ✓ | ✓ |

## 7. What Is Not Tested

Stated so the gaps are deliberate, not accidental:

- **Live AI provider output** in CI — non-deterministic and costly. Covered by nightly evaluation instead.
- **Third-party provider behavior** — `JobSource` and `PublishingProvider` are tested through their interfaces with stubs. Live behavior is covered by monitoring (`13`).
- **Load and performance** — no automated load suite in MVP. The budgets in `09` are monitored in production, and §7 of that document defines the revision trigger.
- **Visual regression** — out of scope for MVP.

Each of these is a deliberate deferral with a named compensating control.
