<?php

namespace App\Http\Controllers;

use App\Services\Analytics\AnalyticsReport;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AnalyticsController extends Controller
{
    public function index(Request $request, AnalyticsReport $report): Response
    {
        $period = (string) $request->query('period', '30d');

        if (! in_array($period, ['today', '7d', '30d', '90d', 'custom'], true)) {
            $period = '30d';
        }

        return Inertia::render('Analytics/Index', $report->forRequest(
            $period,
            $request->query('from'),
            $request->query('to'),
        ));
    }
}
