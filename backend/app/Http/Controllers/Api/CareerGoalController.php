<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * career/goals-and-analysis
 */
class CareerGoalController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return response()->json(['career_goal' => $request->user()->careerGoal]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'primary_goal' => ['required', 'in:grow_linkedin,find_job,both'],
            'target_roles' => ['sometimes', 'array'],
            'target_roles.*' => ['string', 'max:100'],
            'locations' => ['sometimes', 'array'],
            'locations.*' => ['string', 'max:100'],
            'remote_preference' => ['sometimes', 'nullable', 'string', 'max:50'],
            'career_direction' => ['sometimes', 'nullable', 'in:ic,leadership,freelance,not_sure'],
            'salary_min' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'salary_currency' => ['sometimes', 'nullable', 'string', 'size:3'],
            'industries' => ['sometimes', 'array'],
            'employment_types' => ['sometimes', 'array'],
        ]);

        $goal = $request->user()->careerGoal()->updateOrCreate(
            ['user_id' => $request->user()->id],
            [
                'primary_goal' => $data['primary_goal'],
                'target_roles_json' => $data['target_roles'] ?? [],
                'locations_json' => $data['locations'] ?? [],
                'remote_preference' => $data['remote_preference'] ?? null,
                'career_direction' => $data['career_direction'] ?? null,
                'salary_min' => $data['salary_min'] ?? null,
                'salary_currency' => $data['salary_currency'] ?? null,
                'industries_json' => $data['industries'] ?? [],
                'employment_types_json' => $data['employment_types'] ?? [],
            ]
        );

        $request->user()->update(['onboarding_status' => 'dashboard']);

        return response()->json(['career_goal' => $goal]);
    }
}
