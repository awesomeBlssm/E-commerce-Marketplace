<?php

namespace App\Models\Shopper;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasUuids;

    protected $table = 'customers';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'email',
        'full_name',
        'phone',
        'accepts_marketing',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'accepts_marketing' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function addresses()
    {
        return $this->hasMany(CustomerAddress::class);
    }

    public function wishlists()
    {
        return $this->hasMany(Wishlist::class);
    }

    public function reviews()
    {
        return $this->hasMany(ProductReview::class);
    }
}
