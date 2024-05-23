<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Book extends Model
{
    use HasFactory;

    protected $table = 'books';
    protected $guarded = [];
    
    protected $hidden = array('book_genre_relation', 'publisher_id');

    public function genres() : BelongsToMany {
        return $this->belongsToMany(Genre::class, 'book_genre', 'book_id', 'genre_id')
        ->as('book_genre_relation')
        ->withTimestamps();
    }

    public function product(): HasMany {
        return $this->hasMany(Product::class);
    }

    public function publisher() : BelongsTo {
        return $this->belongsTo(Publisher::class);
    }
}