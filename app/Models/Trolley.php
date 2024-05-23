<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Trolley extends Model
{
    use HasFactory;

    protected $guarded= [];
    protected $table = 'trolleys';

    public function products() : BelongsToMany {
        return $this->belongsToMany(Product::class, 'product_trolley', 'trolley_id', 'product_id')
        ->as('product_trolley_relation')
        ->withTimestamps();
    }
}
