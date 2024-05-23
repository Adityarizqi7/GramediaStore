<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Product extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $hidden = ['book_id'];

    public function book() : BelongsTo {
        return $this->belongsTo(Book::class);
    }

    public function trolleys() : BelongsToMany {
        return $this->belongsToMany(Trolley::class, 'product_trolley', 'product_id', 'trolley_id')
        ->as('product_trolley_relation')
        ->withTimestamps();
    }
}
