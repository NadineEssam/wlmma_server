<?php

namespace App\Console\Commands;

use App\Http\Controllers\NotificationController;
use App\Http\Requests\PushNotificationRequest;
use App\Models\Order;
use App\Services\CreditNoteService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AutoRefundOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:auto-refund';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically refund cancelled orders to user wallets and generate ZATCA credit notes';

    /**
     * Execute the console command.
     */
    public function handle(CreditNoteService $creditNoteService)
    {
        // Find orders that are:
        // - Cancelled (status = 'cancelled')
        // - Already paid (is_paid = true)
        // - Not yet refunded (is_returned = false)
        // - Cancelled at least 48 hours ago (same grace period as bookings)
        $orders = Order::where('status', 'cancelled')
            ->where('is_paid', true)
            ->where('is_returned', false)
            ->where('updated_at', '<=', Carbon::now()->subHours(48))
            ->get();

        $this->info('Processing cancelled orders for refund:');

        foreach ($orders as $order) {
            $this->line("Found order ID: {$order->id}");

            DB::transaction(function () use ($order, $creditNoteService) {
                $user = $order->user;

                if (!$user) {
                    Log::warning('Cannot refund order: no user found', [
                        'order_id' => $order->id,
                    ]);
                    return;
                }

                // Refund amount
                $amount = $order->total;

                // Credit the user's wallet
                $wallet = $user->wallet;

                if (!$wallet) {
                    Log::warning('Cannot refund order: user has no wallet', [
                        'order_id' => $order->id,
                        'user_id' => $user->id,
                    ]);
                    return;
                }

                $wallet->update([
                    'balance' => $wallet->balance + $amount,
                ]);

                // Record wallet transaction
                $wallet->transactions()->create([
                    'type' => 'credit',
                    'user_id' => $user->id,
                    'order_id' => $order->id,
                    'amount' => $amount,
                    'reason' => 'Order refund',
                    'wallet_id' => $wallet->id,
                    'provider_id' => null,
                ]);

                // Mark order as refunded
                $order->update([
                    'is_paid' => false,
                    'is_returned' => true,
                ]);

                // Generate ZATCA credit note for the refund
                try {
                    $creditNoteService->createForOrder(
                        $order,
                        $amount,
                        'Order cancellation and refund'
                    );
                    Log::info('ZATCA credit note created for refunded order', [
                        'order_id' => $order->id,
                        'amount' => $amount,
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to create ZATCA credit note for order refund', [
                        'order_id' => $order->id,
                        'error' => $e->getMessage(),
                    ]);
                }

                // Send push notification to user
                $toolNames = $order->orderItems->map(function ($item) {
                    return $item->tool->name_en ?? $item->tool->name_ar ?? 'Tool';
                })->join(', ');

                $notificationData = [
                    'title_en' => 'Refund Issued',
                    'title_ar' => 'تم استرداد المبلغ',
                    'body_en' => "Your order for {$toolNames} has been cancelled and an amount of {$order->total} SAR has been refunded to your wallet.",
                    'body_ar' => "تم إلغاء طلبك ({$toolNames}) وتم رد مبلغ {$order->total} ريال إلى محفظتك.",
                    'type' => 'wallet',
                    'order_id' => $order->id,
                    'action' => 'order & wallet',
                    'send_to_all' => false,
                ];

                $pushController = new NotificationController();
                $pushController->push_welcome(new PushNotificationRequest($notificationData), $user->id); // OLD

                // $notificationData['user_id'] = $user->id;

                // $response = $pushController->push_welcome(
                //     new PushNotificationRequest($notificationData)
                // );
            });
        }

        $this->info('Auto refund complete. Count: ' . $orders->count());
    }
}
