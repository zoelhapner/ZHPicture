<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AttendanceRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AttendanceRequestController extends Controller
{
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $employee = auth()->user()->employee;

        $validated = $request->validate([
            'attendance_date' => [
                'required',
                'date',
            ],
            'request_type' => [
                'required',
                Rule::in([
                    'permission',
                    'sick',
                    'leave',
                    'business_trip',
                ]),
            ],
            'reason' => [
                'required',
                'string',
                'max:1000',
            ],
            'attachment' => [
                'nullable',
                'file',
                'mimes:jpg,jpeg,png,pdf',
                'max:2048',
            ],
        ]);

        // Cek apakah sudah pernah mengajukan di tanggal yang sama
        $exists = AttendanceRequest::where('employee_id', $employee->id)
            ->whereDate('attendance_date', $validated['attendance_date'])
            ->whereIn('status', ['pending', 'approved'])
            ->exists();

        if ($exists) {
            return back()->withErrors([
                'attendance_date' => 'Anda sudah memiliki pengajuan pada tanggal tersebut.'
            ]);
        }
        $hasAttendance = Attendance::where('employee_id', $employee->id)
            ->whereDate('attendance_date', $validated['attendance_date'])
            ->exists();

        if ($hasAttendance) {
            return back()->withErrors([
                'attendance_date' => 'Anda sudah memiliki data absensi pada tanggal tersebut.'
            ]);
        }
        if ($request->hasFile('attachment')) {
            $validated['attachment'] = $request->file('attachment')
                ->store('attendance-requests', 'public');
        }

        AttendanceRequest::create([
            'employee_id'     => $employee->id,
            'attendance_date' => $validated['attendance_date'],
            'request_type'    => $validated['request_type'],
            'reason'          => $validated['reason'],
            'attachment'      => $validated['attachment'] ?? null,
            'status'          => 'pending',
        ]);

        return redirect()
            ->back()
            ->with('success', 'Pengajuan berhasil dikirim dan menunggu persetujuan.');
    }

    /**
     * Display the specified resource.
     */
public function approve(AttendanceRequest $attendanceRequest)
{
    DB::transaction(function () use ($attendanceRequest) {

        $attendanceRequest->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        Attendance::create([
            'employee_id'     => $attendanceRequest->employee_id,
            'attendance_date' => $attendanceRequest->attendance_date,
            'status'          => $attendanceRequest->request_type,
        ]);

    });

    return back()->with('success', 'Pengajuan berhasil disetujui.');
}

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
