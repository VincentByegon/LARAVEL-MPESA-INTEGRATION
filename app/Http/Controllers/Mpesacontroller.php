<?php

namespace App\Http\Controllers;

use App\Models\MpesaPayment;
use App\Services\MpesaService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class MpesaController extends Controller
{
    public function __construct(protected MpesaService $mpesa) {}

    // ─────────────────────────────────────────────────────────────
    // DASHBOARD
    // ─────────────────────────────────────────────────────────────

    /**
     * Show the real-time payments dashboard.
     */
    public function dashboard()
    {
        $payments      = MpesaPayment::orderByDesc('created_at')->paginate(20);
        $todayTotal    = MpesaPayment::today()->sum('trans_amount');
        $todayCount    = MpesaPayment::today()->count();
        $allTimeTotal  = MpesaPayment::sum('trans_amount');
        $allTimeCount  = MpesaPayment::count();
        $recentPayment = MpesaPayment::latest()->first();

        return view('payments.dashboard', compact(
            'payments',
            'todayTotal',
            'todayCount',
            'allTimeTotal',
            'allTimeCount',
            'recentPayment'
        ));
    }

    /**
     * Return latest payments as JSON (for polling / live refresh).
     */
    public function latestPayments(): JsonResponse
    {
        $payments = MpesaPayment::orderByDesc('created_at')
            ->limit(20)
            ->get()
            ->map(fn($p) => [
                'id'            => $p->id,
                'trans_id'      => $p->trans_id,
                'customer_name' => $p->customer_name,
                'phone'         => $p->formatted_phone,
                'amount'        => number_format($p->trans_amount, 2),
                'time'          => $p->created_at->diffForHumans(),
                'created_at'    => $p->created_at->toDateTimeString(),
            ]);

        $stats = [
            'today_total'   => number_format(MpesaPayment::today()->sum('trans_amount'), 2),
            'today_count'   => MpesaPayment::today()->count(),
            'all_time'      => number_format(MpesaPayment::sum('trans_amount'), 2),
        ];

        return response()->json(['payments' => $payments, 'stats' => $stats]);
    }

    // ─────────────────────────────────────────────────────────────
    // SAFARICOM CALLBACKS (called by Safaricom servers, not browser)
    // ─────────────────────────────────────────────────────────────

    /**
     * Validation URL — Safaricom asks permission before completing payment.
     * Return "0" to ACCEPT, "1" to REJECT.
     * Set ResponseType=Completed during registration to skip this entirely.
     */
    public function validation(Request $request): JsonResponse
    {
        Log::info('M-Pesa C2B Validation', $request->all());

        // You can add custom validation logic here (e.g. check account numbers)
        // Returning 0 = Accept the payment
        return response()->json([
            'ResultCode' => 0,
            'ResultDesc' => 'Accepted',
        ]);
    }

    /**
     * Confirmation URL — Safaricom notifies you that payment is DONE.
     * This is where you save the payment to your database.
     */
    public function confirmation(Request $request): JsonResponse
    {
        $data = $request->all();

        Log::info('M-Pesa C2B Confirmation received', $data);

        try {
            // Prevent duplicate saves (Safaricom may retry)
            $existing = MpesaPayment::where('trans_id', $data['TransID'] ?? '')->first();
            if ($existing) {
                Log::warning('Duplicate M-Pesa transaction ignored', ['trans_id' => $data['TransID']]);
                return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
            }

            MpesaPayment::create([
                'transaction_type'    => $data['TransactionType']    ?? 'Buy Goods',
                'trans_id'            => $data['TransID']            ?? '',
                'trans_time'          => $data['TransTime']          ?? '',
                'trans_amount'        => $data['TransAmount']        ?? 0,
                'business_short_code' => $data['BusinessShortCode']  ?? '',
                'bill_ref_number'     => $data['BillRefNumber']      ?? null,
                'invoice_number'      => $data['InvoiceNumber']      ?? null,
                'org_account_balance' => $data['OrgAccountBalance']  ?? null,
                'third_party_trans_id'=> $data['ThirdPartyTransID']  ?? null,
                'msisdn'              => $data['MSISDN']             ?? '',
                'first_name'          => $data['FirstName']          ?? null,
                'middle_name'         => $data['MiddleName']         ?? null,
                'last_name'           => $data['LastName']           ?? null,
                'status'              => 'completed',
                'raw_payload'         => $data,
            ]);

            Log::info('M-Pesa payment saved', [
                'trans_id' => $data['TransID'] ?? '',
                'amount'   => $data['TransAmount'] ?? 0,
                'phone'    => $data['MSISDN'] ?? '',
            ]);

        } catch (\Exception $e) {
            Log::error('M-Pesa confirmation save failed', [
                'error' => $e->getMessage(),
                'data'  => $data,
            ]);
        }

        // Always respond with 0 — even on error — so Safaricom doesn't retry forever
        return response()->json([
            'ResultCode' => 0,
            'ResultDesc' => 'Accepted',
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // ADMIN ACTIONS
    // ─────────────────────────────────────────────────────────────

    /**
     * Register C2B URLs with Safaricom.
     * Call once on deployment: POST /api/mpesa/register
     */
    public function register(): JsonResponse
    {
        try {
            $result = $this->mpesa->registerC2BUrls();
            return response()->json(['success' => true, 'result' => $result]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Simulate a payment (sandbox only).
     * POST /api/mpesa/simulate
     * Body: { "phone": "254712345678", "amount": 100 }
     */
    public function simulate(Request $request): JsonResponse
    {
        $request->validate([
            'phone'  => 'required|string',
            'amount' => 'required|numeric|min:1',
        ]);

        try {
            $result = $this->mpesa->simulateC2BPayment(
                $request->phone,
                $request->amount,
                $request->get('bill_ref', 'TEST')
            );
            return response()->json(['success' => true, 'result' => $result]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * List all payments (JSON API).
     * GET /api/mpesa/payments
     */
    public function payments(Request $request): JsonResponse
    {
        $payments = MpesaPayment::orderByDesc('created_at')
            ->when($request->date, fn($q) => $q->whereDate('created_at', $request->date))
            ->paginate(20);

        return response()->json($payments);
    }
}