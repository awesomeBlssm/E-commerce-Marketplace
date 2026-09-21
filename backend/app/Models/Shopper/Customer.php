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
        'full_name_updated_at',
        'email_updated_at',
        'phone_updated_at',
    ];

    protected $appends = [
        'full_name_locked_until',
        'email_locked_until',
        'phone_locked_until',
    ];

    protected function casts(): array
    {
        return [
            'accepts_marketing' => 'boolean',
            'created_at' => 'datetime',
            'full_name_updated_at' => 'datetime',
            'email_updated_at' => 'datetime',
            'phone_updated_at' => 'datetime',
        ];
    }

    public function getFullNameLockedUntilAttribute(): ?string
    {
        if (! $this->full_name_updated_at) {
            return null;
        }
        $unlockDate = $this->full_name_updated_at->copy()->addDays(30);
        return $unlockDate->isFuture() ? $unlockDate->toISOString() : null;
    }

    public function getEmailLockedUntilAttribute(): ?string
    {
        if (! $this->email_updated_at) {
            return null;
        }
        $unlockDate = $this->email_updated_at->copy()->addDays(30);
        return $unlockDate->isFuture() ? $unlockDate->toISOString() : null;
    }

    public function getPhoneLockedUntilAttribute(): ?string
    {
        if (! $this->phone_updated_at) {
            return null;
        }
        $unlockDate = $this->phone_updated_at->copy()->addDays(30);
        return $unlockDate->isFuture() ? $unlockDate->toISOString() : null;
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
