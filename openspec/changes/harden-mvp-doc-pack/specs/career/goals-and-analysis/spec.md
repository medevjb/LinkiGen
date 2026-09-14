## Purpose

Captures the user's career goals with minimal friction and produces a goal-relative assessment of their profile — an overall score, category scores, and a short list of prioritized, actionable recommendations with stated reasoning.

## ADDED Requirements

### Requirement: Career goal capture
The system SHALL collect a primary goal and minimal supporting context, and SHALL NOT require optional fields during onboarding.

#### Scenario: Primary goal selected
- **WHEN** the user selects "grow LinkedIn presence", "find a better job", or "both"
- **THEN** the system stores the goal and asks only for target role, location or remote preference, and career direction as required follow-ups

#### Scenario: Optional preferences deferred
- **WHEN** the user skips salary, industry, seniority, or employment type
- **THEN** onboarding completes successfully and those preferences remain editable later in settings

#### Scenario: Goal determines emphasis
- **WHEN** the primary goal is "grow LinkedIn presence"
- **THEN** generated plans, recommendations, and dashboard priority SHALL emphasize positioning and content, and job features SHALL remain available but de-emphasized

#### Scenario: Goal changes later
- **WHEN** the user changes their primary goal
- **THEN** the system marks the current analysis, weekly plan, and content strategy as stale and offers regeneration against the new goal

### Requirement: Profile analysis
The system SHALL produce an overall profile score, category scores, and a summary evaluated against the user's stated goals rather than against a generic standard.

#### Scenario: Analysis generated
- **WHEN** analysis runs for a confirmed profile with goals set
- **THEN** it returns an overall score, per-category scores covering professional positioning, headline, experience clarity, skills relevance, projects and evidence, content presence, and career-goal alignment, and a short summary

#### Scenario: Score is explainable
- **WHEN** the user views a category score
- **THEN** the system explains what drove that score in terms of the user's own profile content, not generic advice

#### Scenario: Sparse profile
- **WHEN** the profile has low completeness
- **THEN** the analysis SHALL attribute low scores to missing information rather than to weak experience, and SHALL prioritize filling the gaps

#### Scenario: Analysis fails
- **WHEN** the analysis run fails
- **THEN** the system shows the last successful analysis with its generation date if one exists, reports the failure, and offers retry

### Requirement: Recommendations
The system SHALL produce a bounded list of prioritized recommendations, each with the problem, why it matters, the suggested change, and an effort estimate.

#### Scenario: Recommendation content
- **WHEN** a recommendation is generated
- **THEN** it states the specific problem found in this user's profile, the reason it matters for their stated goal, a concrete recommended change, and an estimated effort in minutes

#### Scenario: High-priority recommendations are bounded
- **WHEN** analysis produces recommendations
- **THEN** the number marked high priority SHALL NOT exceed the configured maximum, so the user is given a manageable set

#### Scenario: Suggested replacement text
- **WHEN** a recommendation concerns rewritable profile text such as a headline or summary
- **THEN** the system SHALL offer suggested replacement text the user can copy or apply, and applying it SHALL record the value as user-authored

#### Scenario: User acts on a recommendation
- **WHEN** the user marks a recommendation complete or dismisses it
- **THEN** the system records the outcome as feedback and SHALL NOT regenerate a materially identical recommendation while the underlying profile data is unchanged
