## Purpose

Governs how a person creates, accesses, secures, and terminates an account, including email verification, session handling, and the data-export and erasure obligations attached to leaving the product.

## ADDED Requirements

### Requirement: Account registration
The system SHALL allow a person to register with an email address and password, and SHALL NOT grant access to product features until the email address is verified.

#### Scenario: Successful registration
- **WHEN** a visitor submits a unique, well-formed email address and a password meeting the published strength policy, and acknowledges the terms and privacy notice
- **THEN** the system creates an unverified account, sends a verification email, and shows a "check your email" state naming the address it sent to

#### Scenario: Email already registered
- **WHEN** a visitor submits an email address that already has an account
- **THEN** the system responds with the same generic success state and sends a "you already have an account" email to that address, and SHALL NOT reveal account existence in the HTTP response

#### Scenario: Weak or invalid input
- **WHEN** the email is malformed or the password fails the strength policy
- **THEN** the system rejects the submission with a field-level error and does not create an account

#### Scenario: Registration rate limit
- **WHEN** registration attempts from one IP address exceed the configured threshold
- **THEN** the system rejects further attempts with a retry-after indication and records the event for abuse monitoring

### Requirement: Email verification
The system SHALL verify email ownership through a single-use, time-limited link before the account may access any authenticated feature.

#### Scenario: Valid verification link
- **WHEN** the user opens a verification link that has not expired and has not been used
- **THEN** the system marks the email verified, establishes a session, and routes the user to profile import

#### Scenario: Expired or already-used link
- **WHEN** the user opens a verification link that has expired or was already consumed
- **THEN** the system explains which condition applies and offers to send a new link

#### Scenario: Unverified user attempts to use the product
- **WHEN** an unverified account attempts to reach any authenticated route or API endpoint
- **THEN** the system denies the request with a verification-required error and offers to resend the link

### Requirement: Sign-in and session handling
The system SHALL authenticate returning users and maintain sessions that expire, can be revoked, and do not leak across devices.

#### Scenario: Successful sign-in
- **WHEN** a verified user submits correct credentials
- **THEN** the system establishes a session and routes the user to the dashboard, or to the next incomplete onboarding step if onboarding is unfinished

#### Scenario: Incorrect credentials
- **WHEN** credentials do not match
- **THEN** the system returns a single generic "email or password is incorrect" error that does not distinguish which field was wrong

#### Scenario: Repeated failed sign-in
- **WHEN** failed attempts for one account exceed the configured threshold
- **THEN** the system temporarily throttles further attempts for that account and notifies the account's email address

#### Scenario: Sign-out
- **WHEN** the user signs out
- **THEN** the system invalidates that session server-side so a replayed session token is rejected

### Requirement: Password reset
The system SHALL allow a user to reset a forgotten password through a single-use, time-limited link sent to the verified email address.

#### Scenario: Reset requested for any address
- **WHEN** a reset is requested for any address, whether or not an account exists
- **THEN** the system shows an identical confirmation state, and sends a reset link only if an account exists

#### Scenario: Password successfully reset
- **WHEN** the user sets a new password through a valid reset link
- **THEN** the system updates the password, invalidates every existing session for that account, consumes the link, and notifies the user by email

### Requirement: Onboarding state is resumable
The system SHALL record the user's onboarding stage and SHALL resume an interrupted onboarding at the furthest completed step rather than restarting it.

#### Scenario: User returns mid-onboarding
- **WHEN** a user who completed profile import but not goal setup signs in again
- **THEN** the system routes them to goal setup with their generated profile intact

### Requirement: Account data export
The system SHALL allow a user to request a machine-readable export of their personal data and SHALL deliver it through a time-limited signed link.

#### Scenario: Export requested
- **WHEN** the user requests a data export
- **THEN** the system queues the export, notifies the user when it is ready, and provides a signed download link that expires after the configured window

#### Scenario: Export content
- **WHEN** an export is produced
- **THEN** it includes the career profile, goals, weekly plans and tasks, posts, job matches, applications, automation settings, and notification history, and excludes password hashes and third-party secrets

### Requirement: Account deletion
The system SHALL allow a user to delete their account, SHALL stop all automation for that account immediately, and SHALL complete erasure within the published retention window.

#### Scenario: Deletion requested
- **WHEN** the user confirms account deletion
- **THEN** the system immediately disables all automations, cancels scheduled publishing and queued jobs for that user, revokes sessions, and marks the account for erasure

#### Scenario: In-flight background work at deletion time
- **WHEN** a background job for that user is already executing when deletion is confirmed
- **THEN** the job SHALL detect the deletion marker before writing any result and SHALL discard its output instead of persisting it

#### Scenario: Erasure completion
- **WHEN** the erasure window elapses
- **THEN** the system removes or irreversibly anonymizes the user's personal data including stored AI run inputs and outputs, retaining only records required for legal or financial obligations
