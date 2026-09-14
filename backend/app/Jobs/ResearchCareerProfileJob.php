<?php

namespace App\Jobs;

use App\Models\CareerProfile;
use App\Models\CareerProfileSource;
use App\Models\FieldProvenance;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable as InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * platform/ai-runtime · career/profile-import
 *
 * Agent key: profile_research (docs/07 §4.1 ProfessionalProfileResearchAgent).
 *
 * This is a minimal, honest implementation of the agent contract: if
 * OPENAI_API_KEY is configured it makes a real Responses API call with the
 * web_search tool; if not, it degrades to RESEARCH_FAILED per
 * platform/ai-runtime's "AI provider unavailable" rule rather than faking a
 * result. Either way the CV and manual-entry fallbacks in
 * career/profile-import stay fully usable — this job never blocks them.
 *
 * Structured-output validation (ADR-003) is intentionally minimal here: this
 * is a scaffold, not the production-evaluated agent described in
 * docs/12-testing-and-ai-evaluation.md §5. A real deployment must pass the
 * ten evaluation cases and quality gates there before this output drives
 * user-visible profile data.
 */
class ResearchCareerProfileJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public function __construct(public int $careerProfileId) {}

    public function handle(): void
    {
        $profile = CareerProfile::find($this->careerProfileId);

        if (! $profile) {
            return;
        }

        $profile->update(['research_status' => 'running']);

        $apiKey = config('services.openai.key');

        if (! $apiKey) {
            $profile->update([
                'research_status' => 'failed',
                'research_error_code' => 'AI_PROVIDER_UNAVAILABLE',
                'last_researched_at' => now(),
            ]);

            Log::info('profile_research skipped: no OPENAI_API_KEY configured', ['career_profile_id' => $profile->id]);

            return;
        }

        try {
            $response = Http::withToken($apiKey)
                ->timeout(90)
                ->post('https://api.openai.com/v1/responses', [
                    'model' => 'gpt-4.1',
                    'tools' => [['type' => 'web_search']],
                    'input' => sprintf(
                        "Research the maximum publicly available professional information for the LinkedIn profile at %s. ".
                        'Return only information you can find from public sources. Do not invent data. '.
                        'Summarize: headline, location, current role, current company, and a one-paragraph professional summary.',
                        $profile->linkedin_url
                    ),
                ]);

            if ($response->failed()) {
                $profile->update([
                    'research_status' => 'failed',
                    'research_error_code' => 'RESEARCH_FAILED',
                    'last_researched_at' => now(),
                ]);

                return;
            }

            $text = data_get($response->json(), 'output_text', '');

            if (blank($text)) {
                $profile->update([
                    'research_status' => 'no_data',
                    'research_error_code' => 'RESEARCH_NO_DATA_FOUND',
                    'last_researched_at' => now(),
                ]);

                return;
            }

            $source = CareerProfileSource::create([
                'career_profile_id' => $profile->id,
                'source_type' => 'web_search',
                'source_url' => $profile->linkedin_url,
                'source_label' => 'OpenAI web search research',
                'retrieved_at' => now(),
                'metadata_json' => ['raw_summary' => $text],
            ]);

            // Structured extraction is intentionally out of scope for this scaffold.
            // The raw summary is stored as a source; professional_summary is set
            // directly with research authority so the review screen has something
            // real to show, but every field stays user-editable and unconfirmed.
            $profile->update([
                'professional_summary' => \Illuminate\Support\Str::limit($text, 2000),
                'research_status' => 'success',
                'research_error_code' => null,
                'last_researched_at' => now(),
            ]);

            FieldProvenance::create([
                'entity_type' => 'career_profile',
                'entity_id' => $profile->id,
                'field_name' => 'professional_summary',
                'authority' => 'research',
                'confidence' => 'medium',
                'source_id' => $source->id,
                'set_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('profile_research failed', ['error' => $e->getMessage(), 'career_profile_id' => $profile->id]);

            $profile->update([
                'research_status' => 'failed',
                'research_error_code' => 'RESEARCH_FAILED',
                'last_researched_at' => now(),
            ]);
        }
    }
}
