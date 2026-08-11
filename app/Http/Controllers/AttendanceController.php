<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Attendance;
use App\Models\AttendanceRevision;
use App\Services\AttendanceService;
use App\Services\AttendanceSummaryService;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Symfony\Component\HttpFoundation\StreamedResponse;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class AttendanceController extends Controller
{
public function index()
{
    return view('attendances.index');
}

    public function datatable(Request $request, AttendanceSummaryService $summaryService) 
{
    $month = $request->input('month', now()->month);
    $year  = $request->input('year', now()->year);

    $summaries = $summaryService->summaries($month, $year);

    $employees = Employee::with('user.roles');

    return DataTables::eloquent($employees)

        ->addIndexColumn()
        ->addColumn('fullname', function ($employee) {
            $name = Str::title($employee->user?->fullname ?? '-');

            return '
                <a href="javascript:void(0)"
                class="btn-history"
                data-id="'.$employee->id.'">
                    '.e($name).'
                </a>
            ';
        })
        ->addColumn('h', fn($e) => $summaries[$e->id]['H'] ?? 0)
        ->addColumn('tla', fn($e) => $summaries[$e->id]['TL A'] ?? 0)
        ->addColumn('tlb', fn($e) => $summaries[$e->id]['TL B'] ?? 0)
        ->addColumn('tlc', fn($e) => $summaries[$e->id]['TL C'] ?? 0)
        ->addColumn('dl', fn($e) => $summaries[$e->id]['DL'] ?? 0)
        ->addColumn('izin', fn($e) => $summaries[$e->id]['I'] ?? 0)
        ->addColumn('sakit', fn($e) => $summaries[$e->id]['S'] ?? 0)
        ->addColumn('cuti', fn($e) => $summaries[$e->id]['C'] ?? 0)
        ->addColumn('alpha', fn($e) => $summaries[$e->id]['A'] ?? 0)
        ->addColumn('total_hari_kerja', fn($e) => $summaries[$e->id]['total_hari_kerja'] ?? 0)
        ->addColumn('total_hari_kehadiran', fn($e) => $summaries[$e->id]['total_hari_kehadiran'] ?? 0)
        ->addColumn('kehadiran', fn($e) => ($summaries[$e->id]['kehadiran'] ?? 0).' %')
        ->addColumn('ketepatan_waktu', fn($e) => ($summaries[$e->id]['ketepatan_waktu'] ?? 0).' %')
        ->addColumn('lembur', fn($e) => round(($summaries[$e->id]['total_jam_lembur'] ?? 0)/60,2))
        ->addColumn('keterangan', fn($e) => $summaries[$e->id]['keterangan'] ?? '')
        ->addColumn('roles', function ($row) {
            return $row->user?->roles?->pluck('name')->implode(', ') ?: '-';
        })
        ->rawColumns(['fullname'])
        ->toJson();
}
public function create()
{
    $employees = Employee::with('user')
        ->get();
    return view(
        'attendances.partials.create',
        compact('employees')
    );
}

public function store(Request $request)
{
    $validated = $request->validate([
        'employee_id' => [
            'required',
            'exists:employees,id',
        ],
        'attendance_date' => [
            'required',
            'date',
        ],
        'status' => [
            'required',
            Rule::in([
                'present',
                'permission',
                'sick',
                'leave',
                'business_trip',
                'alpha',
            ]),
        ],
        'check_in' => [
            Rule::requiredIf($request->status === 'present'),
            'nullable',
            'date_format:H:i',
        ],
        'check_out' => [
            'nullable',
            'date_format:H:i',
            'after:check_in',
        ],
        'notes' => [
            'nullable',
            'string',
            'max:1000',
        ],
    ]);

    // Tidak boleh ada absensi ganda
    $exists = Attendance::where('employee_id', $validated['employee_id'])
        ->whereDate('attendance_date', $validated['attendance_date'])
        ->exists();

    if ($exists) {
        return back()
            ->withErrors([
                'attendance_date' => 'Karyawan sudah memiliki data absensi pada tanggal tersebut.',
            ])
            ->withInput();
    }

    $attendance = Attendance::create([
        'license_id'      => session('license_id'), // sesuaikan dengan projectmu
        'employee_id'     => $validated['employee_id'],
        'attendance_date' => $validated['attendance_date'],
        'status'          => $validated['status'],
        'check_in' => $validated['check_in']
            ? $validated['attendance_date'].' '.$validated['check_in']
            : null,
        'check_out' => $validated['check_out']
            ? $validated['attendance_date'].' '.$validated['check_out']
            : null,
        'notes' => $validated['notes'],
    ]);

    app(AttendanceService::class)->calculate($attendance);
    $attendance->save();

    return redirect()
        ->route('attendances.index')
        ->with('success', 'Data absensi berhasil ditambahkan.');
}
public function history(Request $request, Employee $employee)
{
$attendances = Attendance::query()
    ->select([
        'id',
        'employee_id',
        'attendance_date',
        'check_in',
        'check_out',
        'attendance_code',
        'work_minutes',
    ])
    ->with([
        'overtime:id,attendance_id,work_minutes,status'
    ])
    ->where('employee_id', $employee->id)
    ->when($request->start_date, fn ($q) =>
        $q->whereDate('attendance_date', '>=', $request->start_date))
    ->when($request->end_date, fn ($q) =>
        $q->whereDate('attendance_date', '<=', $request->end_date))
    ->when($request->attendance_code, fn ($q) =>
        $q->where('attendance_code', $request->attendance_code))
    ->latest('attendance_date')
    ->get();

    return view(
        'attendances.partials.history',
        [
            'employee' => $employee,
            'attendances' => $attendances,
            'filters' => [
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'attendance_code' => $request->attendance_code,
            ],
        ]
    );
}
public function detail(Attendance $attendance)
{
    $attendance->load([
        'employee.user',
        'overtime',
        'revisions.editor',
    ]);

    return view(
        'attendances.partials.detail',
        compact('attendance')
    );
}
public function edit(Attendance $attendance)
{
    return view(
        'attendances.partials.edit',
        compact('attendance')
    );
}
    public function update(Request $request, Attendance $attendance, AttendanceService $attendanceService)
{
    $request->validate([
        'attendance_date' => ['required', 'date'],
        'check_in'        => ['nullable'],
        'check_out'       => ['nullable'],
        'notes'           => ['nullable', 'string'],
        'edit_reason'     => ['required', 'string', 'max:500'],
    ]);

    DB::transaction(function () use ($request, $attendance, $attendanceService) {

        // $oldData = [
        //     'attendance_date'   => $attendance->attendance_date,
        //     'check_in'          => optional($attendance->check_in)->toDateTimeString(),
        //     'check_out'         => optional($attendance->check_out)->toDateTimeString(),
        //     'attendance_code'   => $attendance->attendance_code,
        //     'work_minutes'      => $attendance->work_minutes,
        //     'overtime_minutes'  => $attendance->overtime_minutes,
        //     'notes'             => $attendance->notes,
        // ];
        $oldData = $attendance->getAttributes();
        unset($oldData['created_at'], $oldData['updated_at']);
        $attendance->attendance_date = $request->attendance_date;

        $attendance->check_in = $request->filled('check_in')
            ? Carbon::parse($request->attendance_date.' '.$request->check_in)
            : null;

        $attendance->check_out = $request->filled('check_out')
            ? Carbon::parse($request->attendance_date.' '.$request->check_out)
            : null;

        $attendance->notes = $request->notes;

        $attendanceService->calculate($attendance);

        $attendance->save();
        // $newData = [
        //     'attendance_date'   => $attendance->attendance_date,
        //     'check_in'          => optional($attendance->check_in)->toDateTimeString(),
        //     'check_out'         => optional($attendance->check_out)->toDateTimeString(),
        //     'attendance_code'   => $attendance->attendance_code,
        //     'work_minutes'      => $attendance->work_minutes,
        //     'overtime_minutes'  => $attendance->overtime_minutes,
        //     'notes'             => $attendance->notes,
        // ];
        $newData = $attendance->fresh()->getAttributes();
        unset($newData['created_at'], $newData['updated_at']);
        AttendanceRevision::create([
            'attendance_id' => $attendance->id,
            'edited_by'     => auth()->id(),
            'edited_at'     => now(),
            'edit_reason'   => $request->edit_reason,
            'old_data'      => $oldData,
            'new_data'      => $newData,
        ]);
    });

    return response()->json([
        'success' => true,
        'message' => 'Absensi berhasil diperbarui.'
    ]);
}
public function delete(Attendance $attendance)
{
    $attendance->load('employee.user');

    return view(
        'attendances.partials.delete',
        compact('attendance')
    );
}
public function destroy(Request $request, Attendance $attendance)
{
    $request->validate([
        'reason' => ['required', 'string', 'max:500'],
    ]);

    DB::transaction(function () use ($attendance, $request) {

        AttendanceRevision::create([
            'attendance_id' => $attendance->id,
            'edited_by'     => auth()->id(),
            'edited_at'     => now(),
            'action'        => 'delete',
            'edit_reason'   => $request->reason,
            'old_data'      => $attendance->getAttributes(),
            'new_data' => [],
        ]);

        $attendance->delete();
    });

    return response()->json([
        'success' => true,
        'message' => 'Absensi berhasil dihapus.'
    ]);
}
public function restore($id)
{
    $attendance = Attendance::onlyTrashed()->findOrFail($id);

    AttendanceRevision::create([
        'attendance_id' => $attendance->id,
        'edited_by'     => auth()->id(),
        'edited_at'     => now(),
        'action'        => 'restore',
        'edit_reason'   => 'Restore data absensi',
        'old_data'      => null,
        'new_data'      => $attendance->getAttributes(),
    ]);

    $attendance->restore();

    return response()->json([
        'success' => true,
    ]);
}
    public function checkIn(Request $request)
    {
        $request->validate([
            'photo' => 'required|string',
            'check_in_lat' => 'required|numeric',
            'check_in_lng' => 'required|numeric',
        ]);
        $employee = auth()->user()->employee;

        if (!$employee) {
            return back()->with('error', 'Data karyawan tidak ditemukan.');
        }

        $today = Carbon::today();

        $attendance = Attendance::where('employee_id', $employee->id)
            ->whereDate('attendance_date', $today)
            ->first();

        if ($attendance) {
            return back()->with('warning', 'Anda sudah melakukan absensi hari ini.');
        }

        $photoPath = null;

        if ($request->filled('photo')) {

            $image = $request->photo;

            $image = preg_replace('/^data:image\/\w+;base64,/', '', $image);
            $image = str_replace(' ', '+', $image);

            $filename = 'attendance/checkin/' . Str::uuid() . '.jpg';

            Storage::disk('public')->put(
                $filename,
                base64_decode($image)
            );

            $photoPath = $filename;
        }

        Attendance::create([
            'employee_id'      => $employee->id,
            'attendance_date'  => $today,
            'check_in'         => now(),
            'status'           => 'present',
            'check_in_photo'   => $photoPath,
            'check_in_lat'     => $request->check_in_lat,
            'check_in_lng'     => $request->check_in_lng,
        ]);

        return back()->with('success', 'Berhasil melakukan absensi masuk.');
    }

    public function checkOut(Request $request)
    {
        $request->validate([
            'photo' => 'required|string',
            'check_out_lat' => 'required|numeric',
            'check_out_lng' => 'required|numeric',
        ]);

        $employee = auth()->user()->employee;

        if (!$employee) {
            return back()->with('error', 'Data karyawan tidak ditemukan.');
        }

        $attendance = Attendance::where('employee_id', $employee->id)
            ->whereDate('attendance_date', today())
            ->first();

        if (!$attendance) {
            return back()->with('error', 'Anda belum melakukan absensi masuk.');
        }

        if ($attendance->check_out) {
            return back()->with('warning', 'Anda sudah melakukan absensi pulang.');
        }

        // Simpan foto
        $photoPath = null;

        if ($request->filled('photo')) {

            $image = $request->photo;

            $image = preg_replace('/^data:image\/\w+;base64,/', '', $image);
            $image = str_replace(' ', '+', $image);

            $filename = 'attendance/checkout/' . Str::uuid() . '.jpg';

            Storage::disk('public')->put(
                $filename,
                base64_decode($image)
            );

            $photoPath = $filename;
        }

        $attendance->update([
            'check_out'        => now(),
            'check_out_photo'  => $photoPath,
            'check_out_lat'    => $request->check_out_lat,
            'check_out_lng'    => $request->check_out_lng,
        ]);

        app(AttendanceService::class)->calculate($attendance);
        $attendance->save();
        return back()->with('success', 'Berhasil melakukan absensi pulang.');
    }

        public function permission(Request $request)
    {
        $employee = auth()->user()->employee;

        $validated = $request->validate([
            'start_date' => [
                'required',
                'date',
                'after_or_equal:today',
            ],
            'end_date' => [
                'required',
                'date',
                'after_or_equal:start_date',
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
        ]);
        $period = CarbonPeriod::create(
            $validated['start_date'],
            $validated['end_date']
        );

        foreach ($period as $date) {

            $attendance = Attendance::where('employee_id', $employee->id)
                ->whereDate('attendance_date', $date)
                ->first();

            if ($attendance) {
                return back()->withErrors([
                    'attendance_date' => 'Pada tanggal '.$date->format('d-m-Y').' sudah terdapat data absensi.'
                ])
                ->withInput();
            }

            $attendance = Attendance::create([
                'employee_id'     => $employee->id,
                'attendance_date' => $date->toDateString(),
                'status'          => $validated['request_type'],
                'notes'           => $validated['reason'],
            ]);
            app(AttendanceService::class)->calculate($attendance);
            $attendance->save();
        }
        return redirect()
            ->back()
            ->with('success', 'Data izin berhasil dikirim dan disimpan.');
    }
    public function exportPdf(Request $request, AttendanceSummaryService $summaryService)
    {
        $month = $request->input('month', now()->month);
        $year  = $request->input('year', now()->year);

        $summaries = $summaryService->summaries((int) $month, (int) $year);

        $employees = Employee::with('user.roles')->get();

        $pdf = Pdf::loadView('attendances.export-pdf', [
            'employees' => $employees,
            'summaries' => $summaries,
            'month'     => $month,
            'year'      => $year,
        ])->setPaper('f4', 'landscape');

        return $pdf->stream("Rekap Absensi {$month}-{$year}.pdf");
    }

public function historyPdf(Request $request, Employee $employee)
{
    $query = Attendance::where('employee_id', $employee->id);

    if ($request->filled('start_date')) {
        $query->whereDate('attendance_date','>=',$request->start_date);
    }

    if ($request->filled('end_date')) {
        $query->whereDate('attendance_date','<=',$request->end_date);
    }

    if ($request->filled('attendance_code')) {
        $query->where('attendance_code',$request->attendance_code);
    }

    $attendances = $query
        ->orderByDesc('attendance_date')
        ->get();

    $pdf = Pdf::loadView(
        'attendances.history-pdf',
        compact('employee','attendances')
    )->setPaper('a4','portrait');

    $filename = 'Riwayat Absensi';

    if ($request->filled('start_date') && $request->filled('end_date')) {
        $filename .= " {$request->start_date} s.d {$request->end_date}";
    }

    return $pdf->stream($filename.'.pdf');
}

public function exportExcel(Request $request, AttendanceSummaryService $summaryService)
{
    $month = (int) $request->input('month', now()->month);
    $year  = (int) $request->input('year', now()->year);

    $summaries = $summaryService->summaries($month, $year);

    $employees = Employee::with('user.roles')
        ->get();

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    $headers = [
        'No',
        'Nama',
        'Jabatan',
        'H',
        'TL A',
        'TL B',
        'TL C',
        'DL',
        'I',
        'S',
        'C',
        'A',
        'Total Hari Kerja',
        'Total Kehadiran',
        'Kehadiran',
        'Ketepatan Waktu',
        'Lembur',
        'Keterangan'
    ];

    foreach ($headers as $index => $title) {
        $column = Coordinate::stringFromColumnIndex($index + 1);
        $sheet->setCellValue($column . '1', $title);
    }

    $row = 2;

    foreach ($employees as $i => $employee) {

        $summary = $summaries[$employee->id] ?? [];

        $sheet->setCellValue("A{$row}", $i + 1);
        $sheet->setCellValue("B{$row}", $employee->user?->fullname);
        $sheet->setCellValue("C{$row}", $employee->user?->roles->pluck('name')->implode(', '));

        $sheet->setCellValue("D{$row}", $summary['H'] ?? 0);
        $sheet->setCellValue("E{$row}", $summary['TL A'] ?? 0);
        $sheet->setCellValue("F{$row}", $summary['TL B'] ?? 0);
        $sheet->setCellValue("G{$row}", $summary['TL C'] ?? 0);
        $sheet->setCellValue("H{$row}", $summary['DL'] ?? 0);
        $sheet->setCellValue("I{$row}", $summary['I'] ?? 0);
        $sheet->setCellValue("J{$row}", $summary['S'] ?? 0);
        $sheet->setCellValue("K{$row}", $summary['C'] ?? 0);
        $sheet->setCellValue("L{$row}", $summary['A'] ?? 0);

        $sheet->setCellValue("M{$row}", $summary['total_hari_kerja'] ?? 0);
        $sheet->setCellValue("N{$row}", $summary['total_hari_kehadiran'] ?? 0);
        $sheet->setCellValue("O{$row}", ($summary['kehadiran'] ?? 0) . '%');
        $sheet->setCellValue("P{$row}", ($summary['ketepatan_waktu'] ?? 0) . '%');
        $sheet->setCellValue("Q{$row}", round(($summary['total_jam_lembur'] ?? 0) / 60, 2));
        $sheet->setCellValue("R{$row}", $summary['keterangan'] ?? '');

        $row++;
    }

    $writer = new Xlsx($spreadsheet);

    return new StreamedResponse(function () use ($writer) {
        $writer->save('php://output');
    }, 200, [
        'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'Content-Disposition' => 'attachment;filename="Rekap Absensi.xlsx"',
        'Cache-Control' => 'max-age=0',
    ]);
}
}