<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Dashboard\BuildDashboardSummaryAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request, BuildDashboardSummaryAction $buildSummary): JsonResponse
    {
        return response()->json(['data' => $buildSummary->execute($request->user())]);
    }
}
