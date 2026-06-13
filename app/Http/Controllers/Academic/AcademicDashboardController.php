<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Services\Academic\AcademicDashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AcademicDashboardController extends Controller
{
    public function __construct(
        private readonly AcademicDashboardService $academicDashboardService,
    ) {
    }

    public function __invoke(Request $request): View
    {
        return view('academic.dashboard', $this->academicDashboardService->build($request->user()));
    }
}
