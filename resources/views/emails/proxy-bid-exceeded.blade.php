<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proxy Bid Exceeded</title>
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
            background-color: #f97316;
            color: #ffffff;
            padding: 28px 40px;
        }
        .header h1 {
            margin: 0;
            font-size: 20px;
            font-weight: 600;
        }
        .body {
            padding: 32px 40px;
            font-size: 15px;
            line-height: 1.7;
            color: #374151;
        }
        .bid-info {
            background-color: #fff7ed;
            border: 1px solid #fed7aa;
            border-radius: 8px;
            padding: 16px 20px;
            margin: 20px 0;
        }
        .bid-info p {
            margin: 4px 0;
        }
        .bid-info strong {
            color: #9a3412;
        }
        .cta-button {
            display: inline-block;
            background-color: #f97316;
            color: #ffffff;
            padding: 14px 32px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            font-size: 15px;
            margin-top: 16px;
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
            color: #f97316;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <h1>Proxy Bid Exceeded</h1>
        </div>

        <div class="body">
            <p>Hi {{ $user->name }},</p>

            <p>Your proxy bid on <strong>{{ $lot->title }}</strong> in <strong>{{ $lot->auction->title }}</strong> has been exceeded by another bidder.</p>

            <div class="bid-info">
                <p><strong>Lot:</strong> #{{ $lot->lot_number }} - {{ $lot->title }}</p>
                <p><strong>Your Max Bid:</strong> {{ formatCurrency($proxyMax) }}</p>
                <p><strong>Current Winning Bid:</strong> {{ formatCurrency($currentBid) }}</p>
            </div>

            <p>If you still want to win this lot, you can increase your proxy bid or place a manual bid.</p>

            <p style="text-align: center;">
                <a href="{{ url('/lots/' . $lot->id) }}" class="cta-button">Increase Your Proxy Bid</a>
            </p>
        </div>

        <div class="footer">
            <p>
                You are receiving this because you set a proxy bid on {{ config('app.name') }}.<br>
                <a href="{{ url('/dashboard/profile') }}">Manage your notification preferences</a>
            </p>
            <p style="margin-top: 8px;">&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
