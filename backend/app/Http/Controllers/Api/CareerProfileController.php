<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FieldProvenance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * career/career-profile
 */
class CareerProfileController extends Controller
{
    private const EDITABLE_FIELDS = [
        'headline', 'location', 'current_role', 'current_company',
        'professional_summary', 'professional_focus',
    ];

    public function show(Request $request): JsonResponse
    {
        $profile = $request->user()->careerProfile()
            ->with(['sources', 'experiences', 'educations', 'skills', 'projects', 'certifications'])
            ->first();

        if (! $profile) {
            return response()->json(['career_profile' => null]);
        }

        $profile->completeness_score = $this->computeCompleteness($profile);
        $profile->save();

        return response()->json(['career_profile' => $profile]);
    }

    public function update(Request $request): JsonResponse
    {
        $profile = $request->user()->careerProfile;

        if (! $profile) {
            return response()->json(['error' => ['code' => 'RESOURCE_NOT_FOUND', 'message' => "We couldn't find that."]], 404);
        }

        $data = $request->validate([
            'headline' => ['sometimes', 'nullable', 'string', 'max:255'],
            'location' => ['sometimes', 'nullable', 'string', 'max:255'],
            'current_role' => ['sometimes', 'nullable', 'string', 'max:255'],
            'current_company' => ['sometimes', 'nullable', 'string', 'max:255'],
            'professional_summary' => ['sometimes', 'nullable', 'string', 'max:4000'],
            'professional_focus' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ]);

        $profile->update($data);

        // A user edit is always highest authority (ADR-004) — overwrite any
        // prior provenance record for these fields regardless of what it was.
        foreach (array_keys($data) as $field) {
            FieldProvenance::updateOrCreate(
                ['entity_type' => 'career_profile', 'entity_id' => $profile->id, 'field_name' => $field],
                ['authority' => 'user', 'confidence' => null, 'set_at' => now()]
            );
        }

        $profile->completeness_score = $this->computeCompleteness($profile->fresh());
        $profile->save();

        return response()->json(['career_profile' => $profile->fresh()]);
    }

    public function confirm(Request $request): JsonResponse
    {
        $profile = $request->user()->careerProfile;

        if (! $profile) {
            return response()->json(['error' => ['code' => 'RESOURCE_NOT_FOUND', 'message' => "We couldn't find that."]], 404);
        }

        $missing = [];
        if (blank($profile->headline)) {
            $missing[] = 'headline';
        }
        if (blank($profile->current_role)) {
            $missing[] = 'current_role';
        }
        if ($profile->experiences()->count() === 0) {
            $missing[] = 'experience';
        }

        if (! empty($missing)) {
            return response()->json([
                'error' => [
                    'code' => 'PROFILE_INCOMPLETE',
                    'message' => 'A few required fields are missing before you can confirm.',
                    'details' => ['missing_fields' => $missing],
                ],
            ], 422);
        }

        $profile->update(['confirmed_at' => now(), 'linkedin_url_claim_status' => $profile->linkedin_url ? 'confirmed' : 'unclaimed']);
        $request->user()->update(['onboarding_status' => 'goals']);

        return response()->json(['career_profile' => $profile->fresh()]);
    }

    // --- Section CRUD -------------------------------------------------

    public function storeExperience(Request $request): JsonResponse
    {
        $profile = $this->requireProfile($request);
        $data = $request->validate([
            'company' => ['required', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'is_current' => ['sometimes', 'boolean'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $experience = $profile->experiences()->create($data);

        return response()->json(['experience' => $experience], 201);
    }

    public function deleteExperience(Request $request, int $id): JsonResponse
    {
        $profile = $this->requireProfile($request);
        $profile->experiences()->where('id', $id)->delete();

        return response()->json(['message' => 'Removed.']);
    }

    public function storeEducation(Request $request): JsonResponse
    {
        $profile = $this->requireProfile($request);
        $data = $request->validate([
            'institution' => ['required', 'string', 'max:255'],
            'degree' => ['nullable', 'string', 'max:255'],
            'field_of_study' => ['nullable', 'string', 'max:255'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
        ]);

        $education = $profile->educations()->create($data);

        return response()->json(['education' => $education], 201);
    }

    public function deleteEducation(Request $request, int $id): JsonResponse
    {
        $profile = $this->requireProfile($request);
        $profile->educations()->where('id', $id)->delete();

        return response()->json(['message' => 'Removed.']);
    }

    public function syncSkills(Request $request): JsonResponse
    {
        $profile = $this->requireProfile($request);
        $data = $request->validate([
            'skills' => ['required', 'array'],
            'skills.*' => ['string', 'max:100'],
        ]);

        $ids = [];
        foreach ($data['skills'] as $name) {
            $skill = \App\Models\Skill::firstOrCreate(
                ['normalized_name' => strtolower(trim($name))],
                ['name' => trim($name)]
            );
            $ids[] = $skill->id;
        }

        $profile->skills()->sync($ids);

        return response()->json(['skills' => $profile->skills()->get()]);
    }

    private function requireProfile(Request $request)
    {
        $profile = $request->user()->careerProfile;
        abort_if(! $profile, 404, 'Career profile not found.');

        return $profile;
    }

    private function computeCompleteness($profile): int
    {
        $checks = [
            filled($profile->headline),
            filled($profile->location),
            filled($profile->current_role),
            filled($profile->current_company),
            filled($profile->professional_summary),
            $profile->experiences()->count() > 0,
            $profile->educations()->count() > 0,
            $profile->skills()->count() > 0,
        ];

        return (int) round((array_sum($checks) / count($checks)) * 100);
    }
}
