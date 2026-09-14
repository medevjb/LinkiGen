<?php

namespace App\Services;

use App\Models\Task;
use App\Models\User;
use App\Models\WeeklyPlan;
use Carbon\Carbon;

/**
 * planning/weekly-plan
 *
 * Agent key: weekly_plan (docs/07 §4.4 WeeklyPlannerAgent).
 *
 * This is a deterministic, rule-based stand-in for the real AI planner.
 * It honors the spec's structural requirements — bounded task count,
 * no duplicate tasks against the prior week, goal-appropriate task mix,
 * timezone-correct week boundaries — without an AI provider dependency,
 * so the weekly plan capability is runnable and demonstrable today. It
 * must be replaced by docs/07 §4.4's real agent, evaluated per
 * docs/12-testing-and-ai-evaluation.md, before this drives production use.
 */
class WeeklyPlannerService
{
    private const MAX_TASKS = 5;

    public function currentPlanFor(User $user): WeeklyPlan
    {
        $tz = $user->timezone ?: 'UTC';
        $now = Carbon::now($tz);
        $weekStart = $now->copy()->startOfWeek(Carbon::MONDAY);
        $weekEnd = $now->copy()->endOfWeek(Carbon::SUNDAY);

        $existing = WeeklyPlan::where('user_id', $user->id)
            ->where('week_start', $weekStart->toDateString())
            ->first();

        if ($existing) {
            return $existing;
        }

        return $this->generate($user, $weekStart, $weekEnd, $tz);
    }

    public function regenerate(User $user): WeeklyPlan
    {
        $tz = $user->timezone ?: 'UTC';
        $now = Carbon::now($tz);
        $weekStart = $now->copy()->startOfWeek(Carbon::MONDAY);
        $weekEnd = $now->copy()->endOfWeek(Carbon::SUNDAY);

        WeeklyPlan::where('user_id', $user->id)
            ->where('week_start', $weekStart->toDateString())
            ->delete();

        return $this->generate($user, $weekStart, $weekEnd, $tz);
    }

    private function generate(User $user, Carbon $weekStart, Carbon $weekEnd, string $tz): WeeklyPlan
    {
        $plan = WeeklyPlan::create([
            'user_id' => $user->id,
            'week_start' => $weekStart->toDateString(),
            'week_end' => $weekEnd->toDateString(),
            'timezone' => $tz,
            'status' => 'active',
            'generated_at' => now(),
        ]);

        $recentFingerprints = Task::where('user_id', $user->id)
            ->where('created_at', '>=', now()->subWeeks(4))
            ->pluck('fingerprint')
            ->filter()
            ->all();

        $candidates = $this->buildCandidates($user);
        $chosen = 0;
        $day = $weekStart->copy();

        foreach ($candidates as $candidate) {
            if ($chosen >= self::MAX_TASKS) {
                break;
            }
            if (in_array($candidate['fingerprint'], $recentFingerprints, true)) {
                continue;
            }

            Task::create([
                'weekly_plan_id' => $plan->id,
                'user_id' => $user->id,
                'type' => $candidate['type'],
                'title' => $candidate['title'],
                'description' => $candidate['description'],
                'priority' => $candidate['priority'],
                'scheduled_for' => $day->toDateString(),
                'estimated_minutes' => $candidate['estimated_minutes'],
                'automation_level' => 'assistant',
                'approval_required' => false,
                'status' => 'to_do',
                'fingerprint' => $candidate['fingerprint'],
                'rationale' => $candidate['rationale'],
            ]);

            $day->addDay();
            $chosen++;
        }

        return $plan->fresh('tasks');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildCandidates(User $user): array
    {
        $profile = $user->careerProfile;
        $goal = $user->careerGoal;
        $candidates = [];

        if (! $profile || blank($profile->headline)) {
            $candidates[] = [
                'type' => 'profile', 'priority' => 'high', 'estimated_minutes' => 10,
                'title' => 'Write a headline that states your target role',
                'description' => 'A specific headline outperforms a generic job title for both visibility and job matching.',
                'rationale' => 'Your profile is missing a headline, which is one of the highest-impact fields for positioning.',
                'fingerprint' => 'profile:add_headline',
            ];
        }

        if (! $profile || $profile->experiences()->count() === 0) {
            $candidates[] = [
                'type' => 'profile', 'priority' => 'high', 'estimated_minutes' => 15,
                'title' => 'Add your current role to your Career Profile',
                'description' => 'Recommendations, weekly plans, and job matching all rely on your experience history.',
                'rationale' => 'No experience is recorded yet, so downstream features have little to work with.',
                'fingerprint' => 'profile:add_experience',
            ];
        }

        if (! $profile || $profile->skills()->count() < 3) {
            $candidates[] = [
                'type' => 'profile', 'priority' => 'medium', 'estimated_minutes' => 10,
                'title' => 'List your core skills',
                'description' => 'Skills drive job-match scoring and content topic suggestions.',
                'rationale' => 'Fewer than three skills are recorded.',
                'fingerprint' => 'profile:add_skills',
            ];
        }

        if ($goal && in_array($goal->primary_goal, ['grow_linkedin', 'both'], true)) {
            $candidates[] = [
                'type' => 'content', 'priority' => 'medium', 'estimated_minutes' => 20,
                'title' => 'Review your first content ideas',
                'description' => 'Once your content strategy is generated, review the suggested pillars and pick one idea to draft.',
                'rationale' => 'Your goal includes growing your LinkedIn presence, and consistent posting is the highest-leverage action for that.',
                'fingerprint' => 'content:review_ideas',
            ];
        }

        if ($goal && in_array($goal->primary_goal, ['find_job', 'both'], true)) {
            $candidates[] = [
                'type' => 'jobs', 'priority' => 'medium', 'estimated_minutes' => 15,
                'title' => 'Analyze one job you are considering',
                'description' => 'Paste a job URL or description to see your match score and what to improve before applying.',
                'rationale' => 'Your goal includes finding a better role. A concrete match analysis beats browsing listings.',
                'fingerprint' => 'jobs:analyze_one',
            ];
        }

        $candidates[] = [
            'type' => 'review', 'priority' => 'low', 'estimated_minutes' => 5,
            'title' => 'Check your Career Score and top recommendation',
            'description' => 'A five-minute review keeps your plan aligned with what actually moves your score.',
            'rationale' => 'Regular review compounds — this is a standing weekly action.',
            'fingerprint' => 'review:career_score',
        ];

        return $candidates;
    }
}
