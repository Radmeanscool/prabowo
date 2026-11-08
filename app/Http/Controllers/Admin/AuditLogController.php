<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $logs = AuditLog::query()
            ->with('user')
            ->when($request->query('action'), fn ($q, $v) => $q->where('action', 'like', $v.'%'))
            ->when($request->query('user'), fn ($q, $v) => $q->whereHas('user', fn ($u) => $u->where('email', 'like', "%{$v}%")->orWhere('name', 'like', "%{$v}%")))
            ->when($request->query('subject'), fn ($q, $v) => $q->where('auditable_type', 'like', '%'.$v)->when($request->query('subject_id'), fn ($qq, $id) => $qq->where('auditable_id', $id)))
            ->when($request->query('from'), fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($request->query('to'), fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->latest('id')
            ->paginate(50)->withQueryString();

        $groups = ['proposal' => 'Proposal', 'product' => 'Publikasi', 'review' => 'Penilaian', 'assignment' => 'Penugasan', 'file' => 'Berkas', 'user' => 'Pengguna', 'auth' => 'Autentikasi', 'team' => 'Tim', 'setting' => 'Pengaturan', 'export' => 'Ekspor'];

        return view('admin.audit.index', compact('logs', 'groups'));
    }
}
