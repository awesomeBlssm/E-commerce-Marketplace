<?php

namespace App\Models\Shopper;

use App\Models\Money\PaymentMethod;
use App\Models\Promotion\DiscountRedemption;
use App\Models\Promotion\GiftCard;
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

    public function paymentMethods()
    {
        return $this->hasMany(PaymentMethod::class);
    }

    public function discountRedemptions()
    {
        return $this->hasMany(DiscountRedemption::class);
    }

    public function issuedGiftCards()
    {
        return $this->hasMany(GiftCard::class, 'issued_to_customer_id');
    }
}
