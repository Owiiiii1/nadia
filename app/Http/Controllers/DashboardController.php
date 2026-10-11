<?php

namespace App\Http\Controllers;

use App\Services\Analytics\AnalyticsReport;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(AnalyticsReport $report): Response
    {
        return Inertia::render('Dashboard', $report->dashboard());
    }
}
