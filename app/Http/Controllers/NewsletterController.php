<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Models\NewsletterSubscriber;
use App\Services\MailjetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class NewsletterController extends Controller
{
    public function __construct(protected MailjetService $mailjetService) {}

    public function subscribe(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name'  => 'required|string|max:255',
            'phone' => 'nullable|string|max:30',
            'email' => 'required|email|max:255|unique:newsletter_subscribers,email',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Generate unique coupon code: NEWS-XXXXXX
        do {
            $code = 'NEWS-' . strtoupper(Str::random(6));
        } while (Coupon::where('code', $code)->exists());

        $coupon = Coupon::create([
            'code'        => $code,
            'name'        => 'Newsletter Welcome - ' . $request->name,
            'description' => 'Cupón de bienvenida por suscripción al newsletter',
            'type'        => 'percentage',
            'value'       => 5,
            'usage_limit' => 1,
            'used_count'  => 0,
            'is_active'   => true,
        ]);

        $subscriber = NewsletterSubscriber::create([
            'name'        => $request->name,
            'phone'       => $request->phone,
            'email'       => $request->email,
            'coupon_code' => $code,
        ]);

        try {
            $this->mailjetService->sendNewsletterWelcomeEmail($subscriber, $coupon);
        } catch (\Exception $e) {
            Log::error('Failed to send newsletter welcome email', [
                'subscriber_id' => $subscriber->id,
                'error'         => $e->getMessage(),
            ]);
        }

        return response()->json([
            'message' => '¡Suscripción exitosa! Revisa tu correo para obtener tu cupón de descuento.',
        ], 201);
    }

    public function index(Request $request): JsonResponse
    {
        $query = NewsletterSubscriber::with('coupon:id,code,used_count,usage_limit,is_active');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by coupon usage
        if ($request->filled('coupon_used')) {
            $used = $request->boolean('coupon_used');
            $query->whereHas('coupon', function ($q) use ($used) {
                $used
                    ? $q->where('used_count', '>', 0)
                    : $q->where('used_count', 0);
            });
        }

        $perPage = min($request->input('per_page', 20), 100);
        $subscribers = $query->orderBy('created_at', 'desc')->paginate($perPage);

        // Append coupon_used flag to each item
        $subscribers->getCollection()->transform(function ($subscriber) {
            $subscriber->coupon_used = $subscriber->coupon
                ? $subscriber->coupon->used_count > 0
                : false;
            return $subscriber;
        });

        $total      = NewsletterSubscriber::count();
        $usedCount  = NewsletterSubscriber::whereHas('coupon', fn($q) => $q->where('used_count', '>', 0))->count();

        return response()->json([
            'status' => 'success',
            'stats'  => [
                'total'       => $total,
                'coupon_used' => $usedCount,
                'pending'     => $total - $usedCount,
            ],
            'data' => $subscribers,
        ]);
    }
}
