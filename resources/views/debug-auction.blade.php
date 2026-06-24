<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auction Debug Report</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-7xl mx-auto">
        <h1 class="text-3xl font-bold mb-8">Auction Debug Report</h1>

        @if(isset($error))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                {{ $error }}
            </div>
        @endif

        <!-- Auction Overview -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-2xl font-bold mb-4">Auction Overview</h2>
            <div class="grid grid-cols-2 gap-4">
                <div><strong>ID:</strong> {{ $auction['id'] }}</div>
                <div><strong>Title:</strong> {{ $auction['title'] }}</div>
                <div><strong>Auctioneer:</strong> {{ $auction['auctioneer'] }}</div>
                <div><strong>Status:</strong>
                    <span class="px-2 py-1 rounded text-white
                        @if($auction['status'] === 'draft') bg-gray-500
                        @elseif($auction['status'] === 'upcoming') bg-blue-500
                        @elseif($auction['status'] === 'live') bg-green-500
                        @elseif($auction['status'] === 'ended') bg-red-500
                        @endif">
                        {{ strtoupper($auction['status']) }}
                    </span>
                </div>
                <div><strong>Created:</strong> {{ $auction['created_at'] }}</div>
                <div><strong>Start Time:</strong> {{ $auction['start_time'] ?? 'Not set' }}</div>
                <div><strong>End Time:</strong> {{ $auction['end_time'] ?? 'Not set' }}</div>
                <div><strong>Total Lots:</strong> {{ $auction['total_lots'] }}</div>
                <div><strong>Current Time:</strong> {{ $current_time }}</div>
                <div><strong>Auctioneer Balance:</strong> R{{ number_format($auctioneer_credit_balance, 2) }}</div>
            </div>
        </div>

        <!-- Warnings -->
        @if($warnings['stuck_in_draft'] || $warnings['stuck_in_upcoming'] || $warnings['stuck_in_live'])
        <div class="bg-yellow-100 border border-yellow-400 text-yellow-800 px-6 py-4 rounded mb-6">
            <h3 class="font-bold text-lg mb-2">⚠️ WARNINGS DETECTED</h3>
            <ul class="list-disc list-inside">
                @if($warnings['stuck_in_draft'])
                    <li>Auction is stuck in DRAFT status but should be UPCOMING or LIVE (scheduler not running?)</li>
                @endif
                @if($warnings['stuck_in_upcoming'])
                    <li>Auction is stuck in UPCOMING status but should be LIVE (scheduler not running?)</li>
                @endif
                @if($warnings['stuck_in_live'])
                    <li>Auction is stuck in LIVE status but should be ENDED (scheduler not running?)</li>
                @endif
            </ul>
        </div>
        @endif

        <!-- Time Checks -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-2xl font-bold mb-4">Status Validation</h2>
            <div class="space-y-2">
                <div class="flex items-center gap-2">
                    @if($time_checks['should_be_live'])
                        <span class="text-green-600">✓</span> Should be LIVE right now
                    @else
                        <span class="text-gray-400">○</span> Should NOT be live right now
                    @endif
                </div>
                <div class="flex items-center gap-2">
                    @if($time_checks['should_be_ended'])
                        <span class="text-green-600">✓</span> Should be ENDED
                    @else
                        <span class="text-gray-400">○</span> Should NOT be ended yet
                    @endif
                </div>
                <div class="flex items-center gap-2">
                    @if($time_checks['status_correct'])
                        <span class="text-green-600">✓</span> Status is CORRECT
                    @else
                        <span class="text-red-600">✗</span> Status is INCORRECT
                    @endif
                </div>
            </div>
        </div>

        <!-- Lots -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-2xl font-bold mb-4">Lots ({{ count($lots) }})</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left">#</th>
                            <th class="px-4 py-2 text-left">Title</th>
                            <th class="px-4 py-2 text-left">Status</th>
                            <th class="px-4 py-2 text-right">Starting</th>
                            <th class="px-4 py-2 text-right">Current</th>
                            <th class="px-4 py-2 text-center">Bids</th>
                            <th class="px-4 py-2 text-left">Top Bidder</th>
                            <th class="px-4 py-2 text-left">End Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($lots as $lot)
                        <tr class="border-t @if($lot['withdrawn']) bg-gray-100 @endif">
                            <td class="px-4 py-2">{{ $lot['lot_number'] }}</td>
                            <td class="px-4 py-2">
                                {{ $lot['title'] }}
                                @if($lot['withdrawn'])
                                    <span class="text-red-500 text-xs">(WITHDRAWN)</span>
                                @endif
                            </td>
                            <td class="px-4 py-2">
                                <span class="px-2 py-1 rounded text-xs text-white
                                    @if($lot['status'] === 'draft') bg-gray-500
                                    @elseif($lot['status'] === 'pending') bg-yellow-500
                                    @elseif($lot['status'] === 'live') bg-green-500
                                    @elseif($lot['status'] === 'sold') bg-blue-500
                                    @elseif($lot['status'] === 'unsold') bg-red-500
                                    @endif">
                                    {{ strtoupper($lot['status']) }}
                                </span>
                            </td>
                            <td class="px-4 py-2 text-right">R{{ number_format($lot['starting_bid'], 2) }}</td>
                            <td class="px-4 py-2 text-right font-semibold">R{{ number_format($lot['current_bid'], 2) }}</td>
                            <td class="px-4 py-2 text-center">{{ $lot['total_bids'] }}</td>
                            <td class="px-4 py-2">{{ $lot['top_bidder'] ?? '-' }}</td>
                            <td class="px-4 py-2 text-sm">
                                {{ $lot['end_time'] }}
                                @if($lot['is_past_end'])
                                    <span class="text-red-500">⏰ EXPIRED</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Bids -->
        @if(count($all_bids) > 0)
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-2xl font-bold mb-4">All Bids ({{ count($all_bids) }})</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left">Time</th>
                            <th class="px-4 py-2 text-left">Lot</th>
                            <th class="px-4 py-2 text-left">Bidder</th>
                            <th class="px-4 py-2 text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($all_bids as $bid)
                        <tr class="border-t">
                            <td class="px-4 py-2 text-sm">{{ $bid['time'] }}</td>
                            <td class="px-4 py-2">{{ $bid['lot'] }}</td>
                            <td class="px-4 py-2">{{ $bid['bidder'] }}</td>
                            <td class="px-4 py-2 text-right font-semibold">R{{ number_format($bid['amount'], 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        <!-- Credit Transactions -->
        @if(count($credit_transactions) > 0)
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-2xl font-bold mb-4">Credit Transactions ({{ count($credit_transactions) }})</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left">Time</th>
                            <th class="px-4 py-2 text-left">Type</th>
                            <th class="px-4 py-2 text-left">Description</th>
                            <th class="px-4 py-2 text-right">Amount</th>
                            <th class="px-4 py-2 text-right">Balance After</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($credit_transactions as $txn)
                        <tr class="border-t">
                            <td class="px-4 py-2 text-sm">{{ $txn['time'] }}</td>
                            <td class="px-4 py-2">
                                <span class="px-2 py-1 rounded text-xs text-white
                                    @if($txn['type'] === 'purchase') bg-green-500
                                    @elseif($txn['type'] === 'lot_live') bg-blue-500
                                    @elseif($txn['type'] === 'lot_close') bg-purple-500
                                    @else bg-gray-500
                                    @endif">
                                    {{ strtoupper($txn['type']) }}
                                </span>
                            </td>
                            <td class="px-4 py-2 text-sm">{{ $txn['description'] }}</td>
                            <td class="px-4 py-2 text-right @if($txn['amount'] < 0) text-red-600 @else text-green-600 @endif">
                                R{{ number_format($txn['amount'], 2) }}
                            </td>
                            <td class="px-4 py-2 text-right font-semibold">R{{ number_format($txn['balance_after'], 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        <div class="text-center mt-8">
            <a href="/" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                Back to Home
            </a>
        </div>
    </div>
</body>
</html>
