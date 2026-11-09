<?php

namespace App\Http\Controllers;

use App\Services\ExperienceLab\ResponsiveValidationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExperienceLabController extends Controller
{
    protected ResponsiveValidationService $validationService;

    public function __construct(ResponsiveValidationService $validationService)
    {
        $this->validationService = $validationService;
    }

    /**
     * Display the Responsive Experience Lab interactive studio.
     */
    public function index(Request $request)
    {
        $presets = $this->validationService->getViewportPresets();
        $routes = $this->validationService->getAuditableRoutes();

        $selectedRoute = $request->query('route', '/overview');
        $selectedPresetId = $request->query('preset', 'mobile_std');
        $mode = $request->query('mode', 'single'); // single or dual

        // Find active preset or default to mobile standard (390x844)
        $activePreset = collect($presets)->firstWhere('id', $selectedPresetId) ?? $presets[1];

        // Secondary preset for dual comparison mode (defaults to desktop high-res 1440x900)
        $secondaryPreset = collect($presets)->firstWhere('id', 'desktop_fhd') ?? $presets[6];

        return view('experience_lab.index', compact(
            'presets',
            'routes',
            'selectedRoute',
            'activePreset',
            'secondaryPreset',
            'mode'
        ));
    }

    /**
     * Run real-time responsive validation checks for an interactive viewport.
     */
    public function validateViewport(Request $request): JsonResponse
    {
        $request->validate([
            'path' => 'required|string',
            'width' => 'required|integer|min:320|max:3840',
            'height' => 'required|integer|min:480|max:2160',
            'orientation' => 'nullable|string|in:portrait,landscape',
        ]);

        $path = $request->input('path');
        $width = (int) $request->input('width');
        $height = (int) $request->input('height');
        $orientation = $request->input('orientation', 'portrait');

        $result = $this->validationService->validateViewport($path, $width, $height, $orientation);

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * Run a multi-scenario responsive quality sweep across flagships routes.
     */
    public function runSweep(Request $request): JsonResponse
    {
        $report = $this->validationService->generateComprehensiveAuditReport();

        return response()->json([
            'success' => true,
            'report' => $report,
        ]);
    }

    /**
     * Export responsive audit scorecard as CSV or JSON.
     */
    public function exportReport(Request $request)
    {
        $format = strtolower($request->query('format', 'csv'));
        $report = $this->validationService->generateComprehensiveAuditReport();
        $filename = 'clearstay_responsive_audit_' . date('Ymd_His');

        if ($format === 'json') {
            return response()->json($report, 200, [
                'Content-Disposition' => "attachment; filename=\"{$filename}.json\"",
                'Content-Type' => 'application/json',
            ]);
        }

        // CSV Export
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}.csv\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return new StreamedResponse(function () use ($report) {
            $handle = fopen('php://output', 'w');
            
            // Header row
            fputcsv($handle, [
                'Report ID',
                'Route Name',
                'Route Path',
                'Device Preset',
                'Viewport Width (px)',
                'Viewport Height (px)',
                'Compliance Score (%)',
                'Validation Status',
                'Warning Count',
                'Evaluated At',
            ]);

            foreach ($report['scenarios'] as $row) {
                fputcsv($handle, [
                    $report['report_id'],
                    $row['route_name'],
                    $row['path'],
                    $row['preset_name'],
                    $row['width'],
                    $row['height'],
                    $row['score'],
                    $row['status'],
                    $row['warning_count'],
                    $report['timestamp'],
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }
}
