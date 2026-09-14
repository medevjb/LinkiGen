<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Services\WeeklyPlannerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * planning/weekly-plan
 */
class WeeklyPlanController extends Controller
{
    public function __construct(private WeeklyPlannerService $planner) {}

    public function current(Request $request): JsonResponse
    {
        $plan = $this->planner->currentPlanFor($request->user());

        return response()->json(['weekly_plan' => $plan->load('tasks')]);
    }

    public function regenerate(Request $request): JsonResponse
    {
        $plan = $this->planner->regenerate($request->user());

        return response()->json(['weekly_plan' => $plan->load('tasks')], 202);
    }

    public function updateTask(Request $request, int $id): JsonResponse
    {
        $task = Task::where('user_id', $request->user()->id)->find($id);

        if (! $task) {
            return response()->json(['error' => ['code' => 'RESOURCE_NOT_FOUND', 'message' => "We couldn't find that."]], 404);
        }

        $data = $request->validate([
            'status' => ['sometimes', 'in:to_do,in_progress,completed,skipped'],
            'scheduled_for' => ['sometimes', 'date'],
        ]);

        $task->update($data);

        return response()->json(['task' => $task]);
    }
}
