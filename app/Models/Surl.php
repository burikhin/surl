<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\App;

class Surl extends Model
{
    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'url',
        'token',
    ];

    /**
     * Adding redirect url to be present in collections and objects.
     */
    protected $appends = ['redirect_url'];

    /**
     * Get the full redirect url.
     */
    public function getRedirectUrlAttribute()
    {
        return App::make('url')->to('/r/'.$this->token);
    }

    /**
     * Get the visits statistic for an url.
     */
    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class);
    }
}
