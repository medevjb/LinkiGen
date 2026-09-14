## Purpose

Schedules approved posts onto a calendar in the user's own timezone and gets them published — through a guaranteed manual path for MVP, behind a provider abstraction that an approved LinkedIn API integration can later fill without changing user-facing behavior.

## ADDED Requirements

### Requirement: Content calendar
The system SHALL present scheduled and draft content on a calendar or scheduled list showing each item's status and intended publish time.

#### Scenario: Calendar displays status
- **WHEN** the user opens the calendar
- **THEN** each item shows its topic, publish time in the user's timezone, and status among Draft, Needs Approval, Approved, Scheduled, Published, and Failed

#### Scenario: Item rescheduled
- **WHEN** the user moves an item to a new date and time
- **THEN** the system updates the scheduled time and cancels and re-queues any pending publish job for that item

#### Scenario: Scheduling in the past
- **WHEN** the user attempts to schedule an item at a time already elapsed in their timezone
- **THEN** the system rejects the time with an inline error

#### Scenario: Unapproved item cannot be scheduled for publication
- **WHEN** an item that is not Approved is placed on the calendar
- **THEN** it SHALL be held as a planned slot and SHALL NOT be eligible for publishing until approved

### Requirement: Timezone and DST correctness
Scheduled publish times SHALL be interpreted in the user's stored timezone and SHALL remain correct across daylight-saving transitions.

#### Scenario: User changes timezone
- **WHEN** the user changes their timezone setting
- **THEN** existing scheduled items SHALL retain their intended local wall-clock time and the system SHALL show the user what changed in absolute terms

#### Scenario: DST transition
- **WHEN** a scheduled time falls in a skipped or repeated hour caused by a daylight-saving transition
- **THEN** the system SHALL resolve it deterministically to a defined adjacent time and SHALL NOT publish twice or drop the post

### Requirement: Manual publishing path
The system SHALL provide a manual publishing path that works without any LinkedIn integration, and this path SHALL be available on every plan.

#### Scenario: Manual publish
- **WHEN** an approved post reaches its scheduled time and no publishing provider is connected
- **THEN** the system notifies the user that the post is ready, and offers copy-to-clipboard and an "open LinkedIn" action

#### Scenario: User confirms publication
- **WHEN** the user marks a post as published after posting it themselves
- **THEN** the system records the post as Published with the confirmed time and source marked as manual

#### Scenario: Manual post never confirmed
- **WHEN** a post prepared for manual publication is not confirmed within the configured window
- **THEN** the system reminds the user once and then leaves the post in its ready state rather than marking it published or failed

### Requirement: Publishing provider abstraction
Automated publishing SHALL be implemented behind a provider interface so that no user-facing behavior depends on a specific integration being approved or available.

#### Scenario: No provider available
- **WHEN** no publishing provider is configured or approved
- **THEN** the product SHALL function fully through the manual path, and automated-publish controls SHALL be shown as unavailable with an explanation rather than hidden or broken

#### Scenario: Provider publishes successfully
- **WHEN** a connected provider publishes an approved post
- **THEN** the system records the external post identifier, marks the post Published, and records the publishing attempt

#### Scenario: Duplicate publication is prevented
- **WHEN** a publish job is retried or executed more than once for the same post
- **THEN** the system SHALL use an idempotency key so the post is published at most once

### Requirement: Publishing failure handling
The system SHALL record every publishing attempt, bound retries, and never present a failed publication as successful.

#### Scenario: Transient failure
- **WHEN** a publish attempt fails for a retryable reason
- **THEN** the system retries within the bounded policy and records each attempt with its error

#### Scenario: Retries exhausted
- **WHEN** retries are exhausted
- **THEN** the system marks the post Failed, shows "Publishing failed" prominently on the calendar and dashboard, notifies the user, and offers retry plus the manual fallback

#### Scenario: Authorization expired
- **WHEN** a publish attempt fails because the provider authorization has expired or been revoked
- **THEN** the system SHALL NOT retry blindly, SHALL mark the integration as needing reconnection, SHALL notify the user with a reconnect action, and SHALL fall back to the manual path for affected posts

#### Scenario: Rate limited by provider
- **WHEN** the provider reports a rate limit
- **THEN** the system SHALL back off according to the provider's guidance rather than the default retry schedule
