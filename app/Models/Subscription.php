<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'bt_customer_id',
        'bt_plan_id',
        'bt_plan_type',
        'bt_payment_method_token',
        'bt_subscription_id',
        'status',
        'created_at',
        'updated_at',
    ];

    const CUSTOMER_CREATED = 'customer_created';
    const ACTIVE = 'active';
    const DEACTIVATED = 'deactivated';

    const STATUS = [
        Subscription::CUSTOMER_CREATED,
        Subscription::ACTIVE,
        Subscription::DEACTIVATED,
    ];

    const PLAN_TYPE_MONTHLY = 'monthly';
    const PLAN_TYPE_YEARLY = 'yearly';
}
