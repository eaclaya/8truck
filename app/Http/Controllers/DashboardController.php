<?php

namespace App\Http\Controllers;

use App\Actions\Dashboard\BuildDashboardSummaryAction;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, BuildDashboardSummaryAction $buildSummary): Response
    {
        return Inertia::render('Dashboard', $buildSummary->execute($request->user()));
    }
}
