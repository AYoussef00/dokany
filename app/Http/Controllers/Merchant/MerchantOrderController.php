<?php

declare(strict_types=1);

namespace App\Http\Controllers\Merchant;

use App\Actions\Merchant\IssueMerchantInvoiceAndPaymentForOrder;
use App\Http\Controllers\Controller;
use App\Models\StorefrontOrder;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class MerchantOrderController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var User $seller */
        $seller = $request->user();

        $orders = $seller->storefrontOrders()
            ->latest()
            ->get();

        return Inertia::render('merchant/Orders', [
            'orders' => $orders->map(fn (StorefrontOrder $o) => $this->orderPayload($o))->values(),
        ]);
    }

    public function accept(Request $request, StorefrontOrder $order): RedirectResponse
    {
        $this->authorizeSellerOrder($request, $order);

        if ($order->status !== StorefrontOrder::STATUS_PAYMENT_SUBMITTED) {
            return redirect()
                ->route('merchant.orders')
                ->with('error', 'لا يمكن قبول هذا الطلب في حالته الحالية.');
        }

        DB::transaction(function () use ($order): void {
            $order->status = StorefrontOrder::STATUS_ACCEPTED;
            $order->save();
            app(IssueMerchantInvoiceAndPaymentForOrder::class)->handle($order);
        });

        return redirect()
            ->route('merchant.orders')
            ->with('success', 'تم قبول الطلب.');
    }

    public function reject(Request $request, StorefrontOrder $order): RedirectResponse
    {
        $this->authorizeSellerOrder($request, $order);

        if ($order->status !== StorefrontOrder::STATUS_PAYMENT_SUBMITTED) {
            return redirect()
                ->route('merchant.orders')
                ->with('error', 'لا يمكن رفض هذا الطلب في حالته الحالية.');
        }

        $order->status = StorefrontOrder::STATUS_REJECTED;
        $order->save();

        return redirect()
            ->route('merchant.orders')
            ->with('success', 'تم رفض الطلب.');
    }

    public function destroy(Request $request, StorefrontOrder $order): RedirectResponse
    {
        $this->authorizeSellerOrder($request, $order);

        if ($order->status === StorefrontOrder::STATUS_ACCEPTED) {
            return redirect()
                ->route('merchant.orders')
                ->with('error', 'لا يمكن حذف طلب تم قبوله لأنه مرتبط بسجلات الفواتير والمدفوعات.');
        }

        if ($order->payment_receipt_path !== null) {
            Storage::disk('public')->delete($order->payment_receipt_path);
        }

        $order->delete();

        return redirect()
            ->route('merchant.orders')
            ->with('success', 'تم حذف الطلب.');
    }

    /**
     * @return array<string, mixed>
     */
    private function orderPayload(StorefrontOrder $order): array
    {
        return [
            'uuid' => $order->uuid,
            'buyer_name' => $order->buyer_name,
            'buyer_phone' => $order->buyer_phone,
            'buyer_address' => $order->buyer_address,
            'buyer_maps_url' => $order->buyer_maps_url,
            'lines' => $order->lines,
            'subtotal' => (string) $order->subtotal,
            'currency_label_ar' => $order->currency_label_ar,
            'status' => $order->status,
            'payment_receipt_url' => $order->payment_receipt_path !== null
                ? Storage::disk('public')->url($order->payment_receipt_path)
                : null,
            'created_at' => $order->created_at?->toIso8601String(),
            'from_payment_link' => $order->payment_link_id !== null,
        ];
    }

    private function authorizeSellerOrder(Request $request, StorefrontOrder $order): void
    {
        $user = $request->user();
        if (! $user instanceof User || $order->user_id !== $user->id) {
            abort(403);
        }
    }
}
