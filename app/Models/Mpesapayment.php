<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MpesaPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_type',
        'trans_id',
        'trans_time',
        'trans_amount',
        'business_short_code',
        'bill_ref_number',
        'invoice_number',
        'org_account_balance',
        'third_party_trans_id',
        'msisdn',
        'first_name',
        'middle_name',
        'last_name',
        'status',
        'raw_payload',
    ];

    protected $casts = [
        'trans_amount'        => 'decimal:2',
        'org_account_balance' => 'decimal:2',
        'raw_payload'         => 'array',
    ];

    /**
     * Full customer name assembled from parts.
     */
    public function getCustomerNameAttribute(): string
    {
        return trim(implode(' ', array_filter([
            $this->first_name,
            $this->middle_name,
            $this->last_name,
        ])) ?: 'Unknown Customer');
    }

    /**
     * Formatted phone number: 254XXXXXXXXX → 07XXXXXXXX
     */
    public function getFormattedPhoneAttribute(): string
    {
        $phone = $this->msisdn;
        if (str_starts_with($phone, '254')) {
            return '0' . substr($phone, 3);
        }
        return $phone;
    }

    /**
     * Parse trans_time (20240115103045) into a Carbon date.
     */
    public function getTransactionDateAttribute(): \Carbon\Carbon
    {
        return \Carbon\Carbon::createFromFormat('YmdHis', $this->trans_time);
    }

    /**
     * Scope: payments today.
     */
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    /**
     * Scope: recent payments ordered by latest first.
     */
    public function scopeRecent($query, int $limit = 20)
    {
        return $query->orderByDesc('created_at')->limit($limit);
    }
}