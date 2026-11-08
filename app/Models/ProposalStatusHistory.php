<?php

namespace App\Models;

use App\Enums\ProposalStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProposalStatusHistory extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['proposal_id', 'from_status', 'to_status', 'note', 'changed_by'];

    protected function casts(): array
    {
        return [
            'from_status' => ProposalStatus::class,
            'to_status' => ProposalStatus::class,
        ];
    }

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }

    public function changer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
