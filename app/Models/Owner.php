<?php

namespace App\Models;

use Database\Factories\OwnerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['document_type', 'document_number', 'full_name', 'phone', 'email', 'address'])]
class Owner extends Model
{
    /** @use HasFactory<OwnerFactory> */
    use HasFactory, SoftDeletes;

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }
}
