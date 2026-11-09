<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    public function index(Request $request): View
    {
        $logs = ActivityLog::with('user')
            ->when($request->query('user'), fn ($q, $v) => $q->where('user_id', $v))
            ->when($request->query('action'), fn ($q, $v) => $q->where('action', 'like', "$v%"))
            ->when($request->query('q'), fn ($q, $v) => $q->where('description', 'like', "%$v%"))
            ->latest()->paginate(20)->withQueryString();

        return view('activity.index', [
            'logs' => $logs,
            'users' => User::orderBy('name')->get(['id', 'name']),
            'actions' => ['auth' => 'Autentikasi', 'report' => 'Laporan', 'project' => 'Proyek', 'indicator' => 'Indikator', 'user' => 'Pengguna', 'indicators' => 'Impor indikator', 'reports' => 'Impor laporan'],
        ]);
    }
}
