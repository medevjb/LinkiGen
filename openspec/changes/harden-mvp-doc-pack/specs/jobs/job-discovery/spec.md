## Purpose

Collects jobs from permitted, pluggable sources on a schedule, normalizes and de-duplicates them, filters them against the user's stated preferences, and surfaces only the matches worth the user's limited time — degrading gracefully when no source is configured.

## ADDED Requirements

### Requirement: Pluggable job sources
Job collection SHALL be implemented behind a source adapter interface, and the product SHALL remain functional when no source is configured.

#### Scenario: No source configured
- **WHEN** no job source is configured or the configured source is unavailable
- **THEN** automated discovery SHALL be presented as unavailable with an explanation, manual job analysis SHALL remain fully functional, and the system SHALL NOT show an unexplained empty match list

#### Scenario: Source adapter contract
- **WHEN** a source adapter is added
- **THEN** it SHALL return jobs in the normalized job shape, and no downstream filtering, matching, or ranking behavior SHALL depend on which source produced a job

#### Scenario: Prohibited collection methods
- **WHEN** any source adapter is implemented
- **THEN** it SHALL NOT scrape LinkedIn job listings, circumvent access controls, or violate a source's terms of use, and sources SHALL be permitted public, licensed, or officially provided feeds

### Requirement: Scheduled discovery
The system SHALL collect jobs on a configurable schedule for users who have enabled discovery.

#### Scenario: Discovery runs
- **WHEN** the discovery schedule fires for an eligible user
- **THEN** the system queries configured sources using the user's target roles, locations, and remote preference, and records the run outcome

#### Scenario: Source failure
- **WHEN** a source errors or times out
- **THEN** the system records the failure, retries within its bounded policy, and SHALL NOT fail the entire discovery run when other sources succeeded

#### Scenario: Discovery disabled
- **WHEN** the user has disabled job discovery or their plan does not include it
- **THEN** no collection runs for that user and no discovery cost is incurred

### Requirement: Normalization and de-duplication
Collected jobs SHALL be normalized to a common shape and de-duplicated so the same role is not presented more than once.

#### Scenario: Duplicate posting across sources
- **WHEN** the same role from the same company appears in more than one source, or is re-posted
- **THEN** the system SHALL collapse them into a single job record and retain the source URLs

#### Scenario: Stale posting
- **WHEN** a job's posting date is older than the configured freshness window
- **THEN** it SHALL be excluded from user-facing matches

### Requirement: Filtering before matching
The system SHALL filter collected jobs against the user's preferences before spending AI budget on match analysis.

#### Scenario: Hard preference violated
- **WHEN** a job violates a hard preference such as location, remote requirement, or employment type
- **THEN** it SHALL be discarded before match analysis

#### Scenario: Filters produce nothing
- **WHEN** filtering removes every collected job
- **THEN** the run completes successfully with zero matches and SHALL NOT be recorded as a failure

### Requirement: Match generation and ranking
Surviving jobs SHALL be scored against the user's career context, ranked, and surfaced selectively.

#### Scenario: Matches generated
- **WHEN** a filtered job is matched
- **THEN** it receives the same scores, recommendation enum, and explanation structure as a manually analyzed job

#### Scenario: Minimum match threshold
- **WHEN** a match scores below the user's configured minimum threshold
- **THEN** it SHALL NOT appear in the user's match list and SHALL NOT trigger a notification

#### Scenario: Only useful matches surface
- **WHEN** the user views their matches
- **THEN** the list SHALL present ranked matches above the threshold, highest first, and SHALL NOT expose the full set of collected jobs

#### Scenario: No strong matches yet
- **WHEN** no match meets the threshold
- **THEN** the system explains that the agent is still searching and when it will look again, rather than showing a bare empty list

#### Scenario: Match cost bounded
- **WHEN** the number of filtered jobs exceeds the per-run analysis cap
- **THEN** the system SHALL analyze the highest-priority candidates up to the cap and defer the remainder to the next run

### Requirement: Match actions and feedback
The user SHALL be able to act on a match, and those actions SHALL inform future discovery.

#### Scenario: Match saved or skipped
- **WHEN** the user saves or skips a match
- **THEN** the system records the action, removes skipped matches from the active list, and uses the signal to adjust future filtering and ranking

#### Scenario: Repeated skips of a pattern
- **WHEN** the user repeatedly skips matches sharing an attribute such as a company, seniority, or role type
- **THEN** the system SHALL down-rank that pattern in future matching
