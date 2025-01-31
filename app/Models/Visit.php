<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Visit extends Model
{
    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'url_id',
        'useragent',
    ];

    /**
     * Get the surl that owns the visit.
     */
    public function surl(): BelongsTo
    {
        return $this->belongsTo(Surl::class);
    }
}
