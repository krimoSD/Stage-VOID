<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $table = 'customers';

    protected $fillable = [
        'customer_id',
        'first_name',
        'last_name',
        'company',
        'city',
        'country',
        'phone_1',
        'phone_2',
        'email',
        'subscription_date',
        'website'
    ];

    public $timestamps = false;
}
