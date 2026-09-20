<?php

namespace App\Models\Shipping;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/** TaxRate stores integer basis points so tax calculation never relies on floating point values. */
class TaxRate extends Model
{
    use HasUuids;

    protected $table = 'tax_rates';

    public $timestamps = false;

    protected $fillable = [
        'country_code',
        'region',
        'name',
        'rate_basis_points',
    ];

    protected function casts(): array
    {
        return ['rate_basis_points' => 'integer'];
    }
}
