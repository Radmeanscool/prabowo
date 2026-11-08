<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\ReviewPhase;
use App\Models\User;
use App\Services\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReviewController extends Controller
{
    public function index(Request $request): View
    {
        $reviews = Review::query()
            ->with(['proposal.theme', 'reviewer', 'phase', 'assignment'])
            ->when($request->query('phase_id'), fn ($q, $v) => $q->where('review_phase_id', $v))
            ->when($request->query('status'), fn ($q, $v) => $q->where('status', $v))
            ->when($request->query('reviewer_id'), fn ($q, $v) => $q->where('reviewer_id', $v))
            ->latest('updated_at')
            ->paginate(30)->withQueryString();

        return view('admin.reviews.index', [
            'reviews' => $reviews,
            'phases' => ReviewPhase::query()->ordered()->pluck('name', 'id')->all(),
            'reviewers' => User::query()->where('role', UserRole::Reviewer->value)->orderBy('name')->pluck('name', 'id')->all(),
        ]);
    }

    public function show(Review $review): View
    {
        $review->load(['proposal.theme', 'reviewer', 'phase', 'assignment', 'scores.criterion', 'reopenedBy']);

        return view('admin.reviews.show', compact('review'));
    }

    /** Buka kembali penilaian final. Nilai lama disimpan utuh di log audit. */
    public function reopen(Request $request, Review $review): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:1000']]);
        abort_unless($review->isSubmitted(), 422, 'Penilaian ini belum dikirim final.');

        DB::transaction(function () use ($review, $request, $data) {
            $review->load('scores');
            $old = [
                'status' => $review->status,
                'weighted_score' => $review->weighted_score,
                'recommendation' => $review->recommendation,
                'submitted_at' => $review->submitted_at?->toDateTimeString(),
                'overall_comment' => $review->overall_comment,
                'scores' => $review->scores->mapWithKeys(fn ($s) => [$s->review_criterion_id => ['score' => $s->score, 'comment' => $s->comment]])->all(),
            ];

            $review->forceFill([
                'status' => 'draft',
                'reopened_at' => now(),
                'reopened_by' => $request->user()->id,
            ])->save();

            if ($review->assignment && $review->assignment->status === 'submitted') {
                $review->assignment->forceFill(['status' => 'in_progress'])->save();
            }

            Audit::log('review.reopened', $review, $old, ['status' => 'draft', 'reason' => $data['reason']], 'Penilaian dibuka kembali oleh admin');
        });

        return back()->with('success', 'Penilaian dibuka kembali. Reviewer dapat memperbaiki lalu mengirim ulang; nilai lama tersimpan di log audit.');
    }
}
