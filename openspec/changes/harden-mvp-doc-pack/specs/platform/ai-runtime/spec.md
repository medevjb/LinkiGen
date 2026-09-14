## Purpose

Defines the contract every AI-backed feature obeys — how the user's context is assembled and minimized, how model output is schema-validated before it touches the database, how agents are versioned and evaluated, and how failures and costs are bounded.

## ADDED Requirements

### Requirement: Career context assembly
AI features SHALL draw user context from a single logical career context, and SHALL include only the portion each task requires.

#### Scenario: Context sourced from canonical data
- **WHEN** any AI feature assembles context
- **THEN** it SHALL use the confirmed canonical profile, goals, preferences, and relevant history, and SHALL NOT require the user to restate information the system already holds

#### Scenario: Context is minimized per task
- **WHEN** context is assembled for a specific task
- **THEN** it SHALL include only the fields that task needs, and SHALL NOT send the user's full personal record to every call

#### Scenario: Unconfirmed data excluded
- **WHEN** a profile draft has not been confirmed
- **THEN** it SHALL NOT be used as context for content generation, job matching, or any public-facing output

#### Scenario: Secrets never sent
- **WHEN** context is assembled
- **THEN** it SHALL NOT contain passwords, session tokens, API credentials, payment details, or other secrets

### Requirement: Structured output contracts
Backend-consumed AI output SHALL conform to an explicit schema, and SHALL be validated before it reaches any domain service or the database.

#### Scenario: Output validated before persistence
- **WHEN** an AI call returns output intended for storage
- **THEN** the system SHALL validate it against the declared schema, and SHALL pass it through a domain service rather than writing model output directly to the database

#### Scenario: Invalid output
- **WHEN** output fails schema validation
- **THEN** the system SHALL NOT persist it, SHALL record the validation failure with the offending output, and SHALL retry or fail the operation according to its policy

#### Scenario: Unknown enum value
- **WHEN** output contains a value outside a declared enum, such as a recommendation that is not one of the defined options
- **THEN** the system SHALL reject the output rather than coercing or defaulting the value

#### Scenario: No brittle text parsing
- **WHEN** a feature consumes AI output programmatically
- **THEN** it SHALL use the structured schema and SHALL NOT extract values from free-form text by pattern matching

#### Scenario: Insufficient data is representable
- **WHEN** the model cannot produce a valid result from the given context
- **THEN** the schema SHALL allow it to say so explicitly, and the system SHALL handle that as a defined outcome rather than an error

### Requirement: Factual grounding
AI output that makes claims about the user SHALL be grounded in stored user data.

#### Scenario: No invented professional facts
- **WHEN** any feature generates user-facing professional content or claims
- **THEN** it SHALL NOT invent employers, roles, dates, projects, credentials, metrics, or achievements absent from the canonical profile

#### Scenario: Inference is labelled
- **WHEN** output includes an inference rather than a stored fact
- **THEN** the inference SHALL be distinguishable from confirmed data, and SHALL carry a confidence level stored separately from the value

#### Scenario: Low-confidence third-party claims
- **WHEN** research surfaces a low-confidence third-party claim about the user
- **THEN** it SHALL NOT be treated as confirmed fact and SHALL require user review before it becomes canonical

### Requirement: Agent versioning
Every production AI capability SHALL be versioned so its behavior can be traced and compared over time.

#### Scenario: Version recorded
- **WHEN** an AI capability runs
- **THEN** the system records the agent key, agent version, prompt or configuration version, model identifier, and schema version

#### Scenario: Material behavior change
- **WHEN** a prompt, schema, or model change materially alters output behavior
- **THEN** the version SHALL be incremented, so past output remains attributable to the version that produced it

### Requirement: Run observability
Every AI call SHALL produce an auditable run record sufficient to debug failures.

#### Scenario: Run recorded
- **WHEN** an AI call completes or fails
- **THEN** the system records the user, capability, model, version, status, latency, token usage, and references to its input and output

#### Scenario: Failure diagnosable
- **WHEN** an AI call fails
- **THEN** the recorded run SHALL contain enough detail to identify the cause without re-running the call

#### Scenario: Run records respect privacy controls
- **WHEN** run inputs and outputs are stored
- **THEN** they SHALL be subject to the same retention and erasure rules as other personal data

### Requirement: Failure and fallback behavior
AI failures SHALL be bounded and SHALL degrade to a defined user-facing outcome.

#### Scenario: Transient failure retried
- **WHEN** a call fails for a retryable reason such as a timeout or rate limit
- **THEN** the system retries within a bounded policy with backoff

#### Scenario: Retries exhausted
- **WHEN** retries are exhausted
- **THEN** the feature SHALL report a specific failure to the user with a retry action, and SHALL NOT present partial or empty output as a successful result

#### Scenario: Provider outage
- **WHEN** the AI provider is unavailable
- **THEN** features depending on it SHALL fail cleanly with an explanation, and non-AI features including manual entry, manual publishing, and the application tracker SHALL remain usable

### Requirement: Cost control
The system SHALL bound AI spend per account and in aggregate.

#### Scenario: Result reuse
- **WHEN** an identical request would repeat recent work, such as re-researching a profile or re-analyzing a known job
- **THEN** the system SHALL reuse the cached result within the configured window unless the user explicitly forces a refresh

#### Scenario: Model selection by task
- **WHEN** a task is a simple transformation rather than a reasoning task
- **THEN** it SHALL use the cheaper model tier appropriate to it

#### Scenario: Token ceilings
- **WHEN** a call is issued
- **THEN** it SHALL apply an input and output token ceiling appropriate to the capability

#### Scenario: Aggregate spend threshold
- **WHEN** aggregate AI spend crosses the configured operational threshold
- **THEN** the system SHALL alert operators and SHALL be able to suspend non-essential automated generation without affecting user-initiated work

### Requirement: Pre-production evaluation
AI capabilities SHALL be evaluated against representative cases before they drive user-visible or irreversible output.

#### Scenario: Representative cases covered
- **WHEN** an AI capability is evaluated
- **THEN** the case set SHALL include a rich profile, a sparse profile, conflicting public data, a career changer, a senior professional, a junior professional, a user with no public posts, a strong job fit, a poor job fit, and content generation with limited evidence

#### Scenario: Evaluation dimensions
- **WHEN** evaluation runs
- **THEN** it SHALL measure schema validity, factual grounding, unsupported claims, relevance, repetition, and match-score consistency

#### Scenario: Gate before irreversible actions
- **WHEN** a capability would drive a public or irreversible action
- **THEN** it SHALL NOT be connected to that action until its evaluation meets the published thresholds

### Requirement: Feedback capture
The system SHALL capture user reactions to AI output and SHALL use them to improve subsequent generation.

#### Scenario: Feedback recorded
- **WHEN** the user accepts, dismisses, regenerates, edits, approves, skips, completes, saves, or rejects AI-produced output
- **THEN** the system records the action against the item as feedback

#### Scenario: Feedback influences generation
- **WHEN** a capability generates new output
- **THEN** it SHALL take prior feedback into account so rejected patterns are not repeated

#### Scenario: No false training claims
- **WHEN** the product describes how feedback is used
- **THEN** it SHALL NOT claim that a model is retrained on the user's data unless that is actually implemented
