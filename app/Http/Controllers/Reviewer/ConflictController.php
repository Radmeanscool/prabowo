<?php

namespace App\Http\Controllers\Reviewer;

use App\Http\Controllers\Controller;
use App\Models\ConflictDeclaration;
use App\Models\ReviewerAssignment;
use App\Services\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ConflictController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        return view('reviewer.conflicts', [
            'declarations' => ConflictDeclaration::query()->where('reviewer_id', $user->id)->with('proposal')->latest()->get(),
            'assignments' => ReviewerAssignment::query()->where('reviewer_id', $user->id)->open()->with('proposal')->get(),
        ]);
    }

    /** Pernyataan konflik umum, mis. terhadap institusi tertentu. */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'institution' => ['required', 'string', 'max:190'],
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        $declaration = ConflictDeclaration::query()->create($data + ['reviewer_id' => $request->user()->id]);
        Audit::log('conflict.declared', $declaration, [], $data, 'Pernyataan konflik kepentingan umum');

        return back()->with('success', 'Pernyataan konflik kepentingan tersimpan dan akan diperhitungkan saat admin menugaskan proposal.');
    }
}
