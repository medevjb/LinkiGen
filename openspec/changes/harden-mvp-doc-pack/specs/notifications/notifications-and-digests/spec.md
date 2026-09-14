## Purpose

Tells the user what needs their attention without requiring them to open the product — in-app and by email — while protecting them from duplicate, excessive, or misleading messages.

## ADDED Requirements

### Requirement: Notification events
The system SHALL raise notifications for events that require user action or report a material outcome, and SHALL NOT notify for routine internal processing.

#### Scenario: Action-required events
- **WHEN** a post needs approval, an approval request is pending, an automation is suspended, or a proposed profile change awaits review
- **THEN** the system raises an action-required notification linking directly to the item

#### Scenario: Outcome events
- **WHEN** the weekly plan is ready, a strong job match is found, a post is published, a publish fails, or the weekly summary is available
- **THEN** the system raises a notification of the corresponding type

#### Scenario: No notification for routine work
- **WHEN** internal analysis, idea generation, draft generation, or job filtering completes normally
- **THEN** the system SHALL NOT raise a notification for it

### Requirement: Delivery channels and preferences
The system SHALL deliver notifications in-app and SHALL deliver a configurable subset by email, honouring the user's preferences.

#### Scenario: In-app delivery
- **WHEN** any notification is raised
- **THEN** it appears in the in-app notification list with its type, related item, and time

#### Scenario: Email preference respected
- **WHEN** the user has disabled email for a notification type
- **THEN** the system SHALL deliver it in-app only

#### Scenario: Transactional email always sent
- **WHEN** the notification concerns account security, such as a password reset or a new-password confirmation
- **THEN** it SHALL be sent by email regardless of marketing or digest preferences

#### Scenario: Notification read
- **WHEN** the user opens or dismisses a notification
- **THEN** the system marks it read and stops counting it as outstanding

### Requirement: Digests
The system SHALL batch recurring low-urgency information into digests at the user's configured frequency instead of sending individual messages.

#### Scenario: Job digest
- **WHEN** the job digest schedule fires
- **THEN** the system sends only matches above the user's threshold found since the previous digest, ranked highest first

#### Scenario: Empty digest suppressed
- **WHEN** a digest would contain no items
- **THEN** the system SHALL NOT send it

#### Scenario: Weekly summary
- **WHEN** the weekly summary schedule fires
- **THEN** the system sends a summary of tasks, content, publishing outcomes, job activity, and the coming week's focus

#### Scenario: Digest timing
- **WHEN** a digest is scheduled
- **THEN** it SHALL be sent at the configured local time in the user's timezone

### Requirement: Duplicate and volume control
The system SHALL prevent duplicate and excessive notifications.

#### Scenario: Duplicate suppressed
- **WHEN** the same event for the same item would raise a notification already outstanding and unread
- **THEN** the system SHALL NOT create a second notification

#### Scenario: Volume ceiling
- **WHEN** the number of notifications for a user in a period exceeds the configured ceiling
- **THEN** the system SHALL batch the remainder into a single summary rather than sending each individually

#### Scenario: Repeated automation failure
- **WHEN** the same automation fails repeatedly
- **THEN** the system SHALL notify on the first failure and on suspension, and SHALL NOT notify on every intermediate retry

### Requirement: Delivery failure handling
Email delivery failures SHALL be detected and SHALL not silently lose action-required information.

#### Scenario: Email bounces
- **WHEN** an email hard-bounces
- **THEN** the system records the bounce, retains the in-app notification, and flags the address as undeliverable after the configured number of bounces

#### Scenario: Address undeliverable
- **WHEN** an address is flagged undeliverable
- **THEN** the system SHALL suspend email delivery to it, SHALL continue in-app delivery, and SHALL prompt the user to update their address at next sign-in

#### Scenario: Unsubscribe
- **WHEN** the user unsubscribes from non-essential email
- **THEN** the system honours it for digests and product notifications and continues sending account-security email only
