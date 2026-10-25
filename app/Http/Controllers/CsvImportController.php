<?php

namespace App\Http\Controllers;

use App\Models\Hotel;
use App\Services\CsvImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CsvImportController extends Controller
{
    protected CsvImportService $csvImportService;

    public function __construct(CsvImportService $csvImportService)
    {
        $this->csvImportService = $csvImportService;
    }

    public function index()
    {
        $hotelId = session('current_hotel_id') ?? Auth::user()->hotel_id;
        $hotel = Hotel::find($hotelId);

        return view('csv.index', compact('hotel'));
    }

    public function import(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:5120', // max 5MB
        ]);

        $hotelId = session('current_hotel_id') ?? Auth::user()->hotel_id;

        $path = $request->file('csv_file')->getRealPath();

        $result = $this->csvImportService->importReservationsFromCsv($path, $hotelId, Auth::id());

        return view('csv.index', [
            'hotel' => Hotel::find($hotelId),
            'result' => $result,
        ]);
    }

    public function downloadTemplate(): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="clearstay_reservations_template.csv"',
        ];

        return response()->stream(function () {
            echo CsvImportService::getSampleCsv();
        }, 200, $headers);
    }
}
