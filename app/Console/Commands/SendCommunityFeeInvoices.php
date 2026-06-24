<?php

namespace App\Console\Commands;

use App\Jobs\SendPushNotification;
use App\Models\CommunityCommissionLedger;
use App\Models\PushNotification;
use Illuminate\Console\Command;

class SendCommunityFeeInvoices extends Command
{
    protected $signature = 'community:invoice-fees {--dry-run}';

    protected $description = 'Notify community sellers with outstanding platform fee debt — runs monthly on the 1st.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        // Aggregate accrued debt per seller — single grouped query.
        $debts = CommunityCommissionLedger::where('status', 'accrued')
            ->selectRaw('seller_user_id, COUNT(*) as line_count, SUM(commission_amount) as total_owed')
            ->groupBy('seller_user_id')
            ->get();

        if ($debts->isEmpty()) {
            $this->info('No sellers with outstanding fees. Nothing to invoice.');
            return self::SUCCESS;
        }

        $sent = 0;
        foreach ($debts as $row) {
            $title = "Platform fees due: R" . number_format((float) $row->total_owed, 2);
            $body = "{$row->line_count} sold " . ($row->line_count === 1 ? 'lot' : 'lots')
                . " owe a total of R" . number_format((float) $row->total_owed, 2) . " in platform fees. "
                . "Tap to settle via PayFast.";

            $this->line("- Seller #{$row->seller_user_id}: {$row->line_count} lines, R" . number_format((float) $row->total_owed, 2));

            if ($dryRun) continue;

            $notification = PushNotification::create([
                'sender_type' => 'admin',
                'sender_id' => null,
                'audience' => 'specific_user',
                'target_user_id' => $row->seller_user_id,
                'title' => $title,
                'body' => $body,
                'url' => route('community.fees'),
                'sent_count' => 0,
                'failed_count' => 0,
            ]);

            SendPushNotification::dispatch($notification);
            $sent++;
        }

        $verb = $dryRun ? 'would notify' : 'Notified';
        $this->info("\n{$verb} {$sent} seller(s) with a total of {$debts->count()} active debt(s).");

        return self::SUCCESS;
    }
}
