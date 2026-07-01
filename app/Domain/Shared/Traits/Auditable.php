<?php

namespace App\Domain\Shared\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

trait Auditable
{
    /**
     * Boot the trait
     */
    protected static function bootAuditable(): void
    {
        static::creating(function (Model $model) {
            if (Auth::check() && $model->isFillable('created_by')) {
                $model->created_by = Auth::id();
            }
        });

        static::updating(function (Model $model) {
            if (Auth::check() && $model->isFillable('updated_by')) {
                $model->updated_by = Auth::id();
            }
        });

        static::deleting(function (Model $model) {
            if (Auth::check() && $model->isFillable('deleted_by')) {
                $model->deleted_by = Auth::id();
            }
        });
    }

    /**
     * Get the user who created this record
     */
    public function creator()
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'created_by');
    }

    /**
     * Get the user who last updated this record
     */
    public function updater()
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'updated_by');
    }
}
