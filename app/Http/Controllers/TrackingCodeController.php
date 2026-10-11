<?php

namespace App\Http\Controllers;

use App\Models\TrackingCode;
use App\Support\Settings;
use App\Support\TrackingCodes;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TrackingCodeController extends Controller
{
    public function index(Settings $settings): Response
    {
        return Inertia::render('Tracking/Index', [
            'codes' => TrackingCode::query()->orderBy('sort')->orderBy('id')->get(),
            'pixels' => [
                'meta_pixel_id' => (string) $settings->get('meta_pixel_id', ''),
                'ga4_measurement_id' => (string) $settings->get('ga4_measurement_id', ''),
            ],
        ]);
    }

    public function store(Request $request, TrackingCodes $tracking): RedirectResponse
    {
        TrackingCode::query()->create($this->validated($request));
        $tracking->flush();

        return back();
    }

    public function update(Request $request, TrackingCode $trackingCode, TrackingCodes $tracking): RedirectResponse
    {
        $trackingCode->update($this->validated($request, partial: true));
        $tracking->flush();

        return back();
    }

    public function destroy(TrackingCode $trackingCode, TrackingCodes $tracking): RedirectResponse
    {
        $trackingCode->delete();
        $tracking->flush();

        return back();
    }

    public function updatePixels(Request $request, Settings $settings, TrackingCodes $tracking): RedirectResponse
    {
        $data = $request->validate([
            'meta_pixel_id' => ['nullable', 'regex:/^\d{10,20}$/'],
            'ga4_measurement_id' => ['nullable', 'regex:/^G-[A-Z0-9]{4,12}$/'],
        ]);

        $settings->set('meta_pixel_id', $data['meta_pixel_id'] ?? null);
        $settings->set('ga4_measurement_id', $data['ga4_measurement_id'] ?? null);
        $tracking->flush();

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, bool $partial = false): array
    {
        $presence = $partial ? 'sometimes' : 'required';

        return $request->validate([
            'name' => [$presence, 'string', 'max:120'],
            'placement' => [$presence, 'in:head,body_start,body_end'],
            'code' => [$presence, 'string', 'max:20000'],
            'is_active' => ['sometimes', 'boolean'],
            'sort' => ['sometimes', 'integer', 'min:0', 'max:65535'],
        ]);
    }
}
