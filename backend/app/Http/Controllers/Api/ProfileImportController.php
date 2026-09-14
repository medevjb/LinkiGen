<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ResearchCareerProfileJob;
use App\Models\CareerProfile;
use App\Models\CareerProfileSource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * career/profile-import
 */
class ProfileImportController extends Controller
{
    public function import(Request $request): JsonResponse
    {
        $data = $request->validate([
            'linkedin_url' => ['required', 'string', 'regex:/^https?:\/\/(www\.)?linkedin\.com\/in\/[A-Za-z0-9\-_%]+\/?$/i'],
        ], [
            'linkedin_url.regex' => 'Enter a valid LinkedIn profile URL.',
        ]);

        $url = rtrim($data['linkedin_url'], '/');
        $user = $request->user();

        // Duplicate-claim check: a confirmed profile elsewhere blocks the import.
        $claimed = CareerProfile::where('linkedin_url', $url)
            ->where('user_id', '!=', $user->id)
            ->whereNotNull('confirmed_at')
            ->exists();

        if ($claimed) {
            return response()->json([
                'error' => [
                    'code' => 'LINKEDIN_URL_ALREADY_CLAIMED',
                    'message' => 'This profile is already linked to another account.',
                ],
            ], 409);
        }

        // Release any stale unconfirmed claim on this URL from another account.
        CareerProfile::where('linkedin_url', $url)
            ->where('user_id', '!=', $user->id)
            ->whereNull('confirmed_at')
            ->update(['linkedin_url' => null]);

        $profile = CareerProfile::updateOrCreate(
            ['user_id' => $user->id],
            ['linkedin_url' => $url, 'linkedin_url_claim_status' => 'pending', 'research_status' => 'running']
        );

        ResearchCareerProfileJob::dispatch($profile->id);

        $user->update(['onboarding_status' => 'profile_review']);

        return response()->json([
            'operation' => [
                'kind' => 'profile_research',
                'status' => 'queued',
                'career_profile_id' => $profile->id,
            ],
        ], 202);
    }

    public function manual(Request $request): JsonResponse
    {
        $user = $request->user();

        $profile = CareerProfile::updateOrCreate(
            ['user_id' => $user->id],
            ['research_status' => 'idle']
        );

        CareerProfileSource::create([
            'career_profile_id' => $profile->id,
            'source_type' => 'manual',
            'retrieved_at' => now(),
        ]);

        $user->update(['onboarding_status' => 'profile_review']);

        return response()->json(['career_profile' => $profile->fresh()]);
    }

    public function draft(Request $request): JsonResponse
    {
        $profile = $request->user()->careerProfile()->with(['sources', 'experiences', 'educations', 'skills', 'projects', 'certifications'])->first();

        if (! $profile) {
            return response()->json(['career_profile' => null]);
        }

        return response()->json(['career_profile' => $profile]);
    }
}
