<?php

namespace App\Http\Controllers;

use App\Services\AttendanceOvertimeService;
use Illuminate\Http\Request;

class AttendanceOvertimeController extends Controller
{
    public function __construct(
        protected AttendanceOvertimeService $service
    ) {}

    public function start(Request $request)
    {
        try {

            $this->service->start($request);

            return back()->with('success', 'Lembur dimulai.');

        } catch (\Exception $e) {

            return back()->with('error', $e->getMessage());

        }
    }

    public function finish(Request $request)
    {
        try {

            $this->service->finish($request);

            return back()->with('success', 'Lembur selesai.');

        } catch (\Exception $e) {

            return back()->with('error', $e->getMessage());

        }
    }
}
