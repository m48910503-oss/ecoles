<?php

namespace App\Http\Controllers\People;

use App\Http\Controllers\Controller;
use App\Services\People\PeopleDashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PeopleDashboardController extends Controller
{
    public function __construct(
        private readonly PeopleDashboardService $peopleDashboardService,
    ) {
    }

    public function __invoke(Request $request): View
    {
        return view('people.dashboard', [
            'dashboard' => $this->peopleDashboardService->build($request->user()),
        ]);
    }
}
