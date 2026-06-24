<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $broadcastSubject }}</title>
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
            background-color: #22c55e;
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
            white-space: pre-wrap;
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
            color: #22c55e;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <h1>{{ config('app.name') }}</h1>
        </div>

        <div class="body">{{ $broadcastMessage }}</div>

        <div class="footer">
            <p>
                You are receiving this message because you have an account on {{ config('app.name') }}.<br>
                <a href="{{ url('/dashboard/profile') }}">Manage your notification preferences</a>
            </p>
            <p style="margin-top: 8px;">&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
