<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReserveAccountStatusEntry extends Model
{
    protected $fillable = [
        'reserve_account_id',
        'competency',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    public function reserveAccount(): BelongsTo
    {
        return $this->belongsTo(ReserveAccount::class);
    }
}
