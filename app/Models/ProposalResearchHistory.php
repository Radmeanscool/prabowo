<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProposalResearchHistory extends Model
{
    protected $fillable = ['proposal_id', 'year', 'title', 'researchers', 'funding_amount', 'funding_source', 'sort_order'];

    protected function casts(): array
    {
        return ['funding_amount' => 'decimal:2'];
    }

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }
}
