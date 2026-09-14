## Purpose

Derives the user's content pillars, topic distribution, and posting cadence from their real expertise and goals, and generates grounded post ideas so the user never faces an empty topic list.

## ADDED Requirements

### Requirement: Content pillar generation
The system SHALL generate content pillars, a topic distribution, and a recommended cadence derived from the user's canonical profile and goals.

#### Scenario: Strategy generated
- **WHEN** strategy generation runs for a confirmed profile
- **THEN** it returns named pillars, a percentage distribution across them, a recommended posting cadence, and a rationale tying each pillar to specific profile evidence

#### Scenario: Pillars are grounded
- **WHEN** a pillar is proposed
- **THEN** it SHALL be traceable to the user's skills, experience, projects, professional focus, or observed public content themes, and SHALL NOT be a generic industry topic unsupported by the profile

#### Scenario: Cadence respects available time
- **WHEN** the user has stated limited available time
- **THEN** the recommended cadence SHALL stay within that budget rather than defaulting to a maximum posting frequency

#### Scenario: Strategy is versioned
- **WHEN** a new strategy supersedes an earlier one
- **THEN** the previous version SHALL be retained so changes over time can be inspected

### Requirement: Strategy refresh
The system SHALL revise the content strategy on a recurring schedule and when the user's context changes materially, without requiring user approval for internal strategy changes.

#### Scenario: Scheduled refresh
- **WHEN** the weekly strategy trigger fires
- **THEN** the system reviews performance and feedback and updates pillar emphasis, recording what changed and why

#### Scenario: Refresh does not overfit
- **WHEN** the refresh considers performance data
- **THEN** it SHALL NOT restructure the strategy based on a single post or a single week of data

#### Scenario: User overrides a pillar
- **WHEN** the user edits or removes a pillar or sets topic preferences
- **THEN** those preferences SHALL persist across refreshes and SHALL outrank automated emphasis changes

### Requirement: Content idea generation
The system SHALL generate post ideas sufficient for the upcoming cadence, each grounded in the user's real expertise.

#### Scenario: Ideas generated for the schedule
- **WHEN** idea generation runs
- **THEN** it produces at least enough unused ideas to cover the upcoming scheduled slots, each with a title, angle, pillar, and reason it suits this user

#### Scenario: Ideas avoid fabrication
- **WHEN** ideas are generated
- **THEN** they SHALL NOT propose fabricated personal anecdotes, invented achievements, or events that the profile does not support

#### Scenario: Ideas avoid repetition
- **WHEN** ideas are generated
- **THEN** they SHALL NOT materially repeat ideas the user has already used, dismissed, or that are still pending in the idea list

#### Scenario: Idea dismissed
- **WHEN** the user dismisses an idea
- **THEN** the system records the dismissal as feedback and does not resurface that idea

#### Scenario: Thin profile evidence
- **WHEN** the profile provides little usable evidence for content
- **THEN** the system SHALL generate fewer, safer ideas and SHALL tell the user which profile additions would improve idea quality, rather than inventing material
