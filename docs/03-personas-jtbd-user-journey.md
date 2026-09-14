# Personas, JTBD & User Journey

## 1. Design Principle

The MVP is designed for people who value time more than control over every small step.

The product should:
- Ask for high-value decisions.
- Automate repetitive preparation.
- Avoid repeated configuration.
- Surface exceptions rather than routine operations.

## 2. Persona A — Busy Professional

### Profile
- Age: typically 27–45
- Full-time professional
- Has meaningful expertise
- Uses LinkedIn inconsistently
- Wants stronger professional visibility
- Has limited time for personal branding

### Behaviors
- Posts occasionally
- Saves ideas but does not execute consistently
- May ask ChatGPT to write posts manually
- Rarely analyzes past post performance
- Often postpones profile updates

### Pain Points
- "I don't know what to post."
- "I don't have time to write."
- "I don't want to sound AI-generated."
- "I don't know whether LinkedIn is helping my career."
- "I forget to maintain my profile."

### Goals
- Build professional authority
- Stay visible
- Attract recruiters/clients
- Show real expertise
- Spend minimal time managing LinkedIn

### JTBD
> When I am busy with my main work, I want my professional presence to keep improving with minimal effort so I can create future career opportunities without becoming a full-time content creator.

### Success
- 5–15 minutes of weekly management
- Relevant posts prepared automatically
- Clear priorities
- Consistency without mental overhead

## 3. Persona B — Active Job Seeker

### Profile
- May be employed or unemployed
- Has a defined target role
- Searches jobs repeatedly
- Experiences job-search fatigue
- Wants better prioritization

### Behaviors
- Searches LinkedIn/jobs frequently
- Applies to roles with uncertain fit
- Repeats the same comparison manually
- Tracks applications inconsistently
- Tailors materials only for some jobs

### Pain Points
- Too many irrelevant roles
- Hard to judge fit quickly
- Repeated searching
- Unclear missing skills
- No integrated professional-brand strategy

### Goals
- Find strong opportunities faster
- Apply selectively
- Improve interview probability
- Strengthen profile for target roles
- Avoid spending hours every day searching

### JTBD
> When I am looking for a better role, I want the system to continuously identify and rank the strongest opportunities so I can focus my limited time on applications with the highest potential.

### Success
- Fewer irrelevant jobs
- Higher-quality applications
- Clear match explanation
- Less manual searching
- Better tracking

## 4. Persona C — Career Growth Professional

This persona can use both major product paths.

### JTBD
> When I am planning my next career step, I want an AI system that continuously tells me the highest-value actions to improve my positioning, visibility, and opportunities.

This persona may later become the broadest product positioning.

## 5. Shared User Needs

All primary personas need:
- Fast onboarding
- High-quality personalization
- Trustworthy AI
- Low manual workload
- Clear action priority
- Control over public actions
- Explanations for important recommendations
- Easy correction of wrong data
- Automation that can be paused
- Evidence that the product is saving time or improving outcomes

## 6. Primary Journey

### Stage 1 — Discovery

User thought:
> I do not have enough time to maintain LinkedIn or manage my career properly.

Product promise:
> Let your AI Career Agent handle the repetitive work.

Primary CTA:
**Build My Career Profile**

### Stage 2 — Registration

User:
- Creates account
- Verifies email

Goal:
Move immediately toward first value.

Avoid:
- Long setup forms
- Payment before value demonstration
- Unnecessary preferences

### Stage 3 — Professional Profile Import

User:
- Pastes LinkedIn URL

System:
- Validates URL
- Runs professional web research
- Generates structured Career Profile
- Shows progress

User expectation:
> The product should understand me without making me type everything.

### Stage 4 — Profile Review

System shows:
- Found information
- Missing information
- Confidence/completeness
- Editable fields

User:
- Corrects errors
- Adds essential missing information
- Confirms profile

Trust moment:
This stage strongly affects whether users trust later AI recommendations.

### Stage 5 — Goal Setup

User selects:
- Grow LinkedIn
- Find a job
- Both

Then provides minimal goal context:
- Target role
- Location
- Career direction
- Optional salary/preferences

### Stage 6 — Automation Setup

Recommended preset:
**Copilot**

User can choose:
- Assistant
- Copilot
- Autopilot where available

System may ask only high-impact preferences:
- Posting cadence
- Job digest frequency (if a job source is configured; otherwise this preference is hidden, not offered against a feature that will not run)

**Publishing is manual in MVP 1** (`ADR-006`): the user confirms each post before it goes to LinkedIn. This is not a configurable preference at onboarding — it is how the product works at launch. Automated publishing, once available, still requires approval by default in Copilot mode; that default is not weakened during onboarding.

### Stage 7 — First Value

System generates:
- Career/Profile Score
- Top profile improvements
- First weekly plan
- Initial content pillars
- Initial post ideas
- Initial job matches, or — if no job source is configured — the manual job-analysis capability, presented as available and useful on its own rather than as a lesser fallback

The user receives useful output immediately after onboarding, whether or not automated job discovery is active for their account.

### Stage 8 — Weekly Use

User opens dashboard.

The dashboard shows:
- Today's actions
- What AI already completed
- What requires approval
- Strong job matches
- Upcoming content

The user should not need to explore the product to find urgent work.

### Stage 9 — Outcomes

The system records:
- Completed tasks
- Approved posts
- Published posts
- Engagement/performance when available
- Job saves/applications
- User feedback
- Skipped recommendations

### Stage 10 — Learning Loop

The AI updates:
- Content strategy
- Weekly priorities
- Job filters
- Recommendation quality
- Automation behavior within user permissions

## 7. Journey Friction Risks

### Risk: Profile research is incomplete
Response:
- Show completeness
- Ask only for essential missing fields
- Offer CV upload

### Risk: User distrusts AI data
Response:
- Field editing
- Confidence
- Explain uncertainty
- Require confirmation

### Risk: Too many tasks
Response:
- Prioritize
- Limit weekly task count
- Estimate effort
- Respect available time

### Risk: Content sounds generic
Response:
- Use real projects/skills/history
- Store user tone preferences
- Learn from approvals/rejections
- Avoid unsupported personal stories

### Risk: Too many job matches
Response:
- Filter aggressively
- Rank
- Show top matches first
- Let users set minimum match threshold

### Risk: Automation anxiety
Response:
- Default Copilot
- Activity history
- Clear approval labels
- Pause controls
- No hidden public actions

## 8. Experience Principles

1. **Time saved is the primary UX metric.**
2. **Default to prepared actions, not blank input boxes.**
3. **Ask for confirmation only when it adds value.**
4. **Make AI reasoning understandable, not verbose.**
5. **Keep users in control of reputation-sensitive actions.**
6. **Use progressive disclosure.**
7. **Prefer one recommended action over ten equal choices.**
8. **Design for weekly habit, not constant app usage.**
