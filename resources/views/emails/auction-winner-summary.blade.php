<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auction Results</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            color: #333333;
        }
        .wrapper {
            max-width: 600px;
            margin: 30px auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .header {
            background-color: #1d4ed8;
            color: #ffffff;
            padding: 32px 40px;
        }
        .header h1 {
            margin: 0 0 6px 0;
            font-size: 22px;
        }
        .header p {
            margin: 0;
            font-size: 14px;
            opacity: 0.85;
        }
        .body {
            padding: 32px 40px;
        }
        .greeting {
            font-size: 16px;
            margin-bottom: 20px;
        }
        .auction-meta {
            background-color: #f0f4ff;
            border-left: 4px solid #1d4ed8;
            padding: 14px 18px;
            border-radius: 4px;
            margin-bottom: 28px;
            font-size: 14px;
        }
        .auction-meta strong {
            display: block;
            font-size: 16px;
            margin-bottom: 4px;
        }
        h2 {
            font-size: 17px;
            margin: 0 0 14px 0;
            color: #111827;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
            margin-bottom: 24px;
        }
        thead tr {
            background-color: #1d4ed8;
            color: #ffffff;
        }
        thead th {
            padding: 10px 12px;
            text-align: left;
            font-weight: 600;
        }
        tbody tr:nth-child(even) {
            background-color: #f8fafc;
        }
        tbody td {
            padding: 10px 12px;
            border-bottom: 1px solid #e5e7eb;
        }
        .text-right {
            text-align: right;
        }
        .total-row td {
            border-top: 2px solid #1d4ed8;
            font-weight: bold;
            font-size: 15px;
            background-color: #f0f4ff;
        }
        .cta {
            text-align: center;
            margin: 28px 0;
        }
        .cta a {
            background-color: #1d4ed8;
            color: #ffffff;
            text-decoration: none;
            padding: 14px 32px;
            border-radius: 6px;
            font-size: 15px;
            font-weight: 600;
            display: inline-block;
        }
        .notice {
            background-color: #fefce8;
            border: 1px solid #fbbf24;
            border-radius: 4px;
            padding: 14px 18px;
            font-size: 13px;
            margin-bottom: 24px;
            color: #78350f;
        }
        .footer {
            background-color: #f9fafb;
            border-top: 1px solid #e5e7eb;
            padding: 20px 40px;
            font-size: 12px;
            color: #6b7280;
            text-align: center;
        }
        .footer a {
            color: #1d4ed8;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <!-- Header -->
        <div class="header">
            <h1>Congratulations, {{ $winner->name }}!</h1>
            <p>You won {{ $lots->count() }} {{ Str::plural('lot', $lots->count()) }} in this auction</p>
        </div>

        <!-- Body -->
        <div class="body">
            <p class="greeting">
                The auction has ended and you have been selected as the winning bidder on the following lots.
                Please review your winnings and arrange payment at your earliest convenience.
            </p>

            <!-- Auction details -->
            <div class="auction-meta">
                <strong>{{ $auction->title }}</strong>
                Ended: {{ $auction->end_time->format('l, d F Y \a\t H:i') }}<br>
                Auctioneer: {{ $auction->auctioneer->business_name }}
            </div>

            <!-- Lots table -->
            <h2>Your Winning Lots</h2>
            <table>
                <thead>
                    <tr>
                        <th>Lot #</th>
                        <th>Description</th>
                        <th class="text-right">Hammer Price</th>
                        @if($auction->buyers_premium_percentage > 0)
                        <th class="text-right">Buyer's Premium ({{ number_format($auction->buyers_premium_percentage, 0) }}%)</th>
                        @endif
                        <th class="text-right">Total Due</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($lots as $lot)
                    @php
                        $hammerPrice = (float) $lot->current_bid;
                        $premium = $hammerPrice * ((float) $auction->buyers_premium_percentage / 100);
                        $lotTotal = $hammerPrice + $premium;
                    @endphp
                    <tr>
                        <td>{{ $lot->lot_number }}</td>
                        <td>{{ $lot->title }}</td>
                        <td class="text-right">{{ formatCurrency($hammerPrice) }}</td>
                        @if($auction->buyers_premium_percentage > 0)
                        <td class="text-right">{{ formatCurrency($premium) }}</td>
                        @endif
                        <td class="text-right">{{ formatCurrency($lotTotal) }}</td>
                    </tr>
                    @endforeach
                    <!-- Grand total -->
                    <tr class="total-row">
                        <td colspan="{{ $auction->buyers_premium_percentage > 0 ? 4 : 3 }}">Grand Total</td>
                        <td class="text-right">{{ formatCurrency($grandTotal) }}</td>
                    </tr>
                </tbody>
            </table>

            <!-- Payment notice -->
            <div class="notice">
                <strong>Collection Required</strong><br>
                Please contact the auctioneer to arrange payment and collection for your won lots.
                @if($auction->payment_deadline)
                Payment deadline: <strong>{{ $auction->payment_deadline->format('d F Y') }}</strong>.
                @endif
            </div>

            <!-- CTA button -->
            <div class="cta">
                <a href="{{ url('/dashboard/won') }}">View My Won Lots</a>
            </div>

            <p style="font-size: 13px; color: #6b7280;">
                If you have any questions, please contact the auctioneer directly:<br>
                <strong>{{ $auction->auctioneer->business_name }}</strong>
                @if($auction->auctioneer->whatsapp_number)
                &mdash; WhatsApp: {{ $auction->auctioneer->whatsapp_number }}
                @endif
            </p>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>
                You are receiving this email because you placed winning bids on the {{ config('app.name') }} platform.<br>
                <a href="{{ url('/dashboard/profile') }}">Manage your notification preferences</a>
            </p>
            <p style="margin-top: 8px;">&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
