<?php

namespace App\Http\Controllers;

use App\Http\Requests\Dashboard\FilterDashboardRequest;
use App\Services\DashboardService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(FilterDashboardRequest $request, DashboardService $dashboardService): View
    {
        $validated = $request->validated();

        $data = $dashboardService->getDashboardData(
            auth()->user(),
            $validated['location_id'] ?? null,
            $validated['stand_id'] ?? null
        );

        return view('dashboard', $data);
    }
}
