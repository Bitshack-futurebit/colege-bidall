<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHP Upload Limits - BidAll</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
        }
        .card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            margin-bottom: 20px;
        }
        h1 {
            color: #2d3748;
            margin-bottom: 10px;
            font-size: 28px;
        }
        .subtitle {
            color: #718096;
            margin-bottom: 30px;
        }
        .status {
            padding: 20px;
            border-radius: 8px;
            margin: 25px 0;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .status-icon {
            font-size: 24px;
            line-height: 1;
        }
        .success {
            background: #d4edda;
            color: #155724;
            border: 2px solid #c3e6cb;
        }
        .warning {
            background: #fff3cd;
            color: #856404;
            border: 2px solid #ffeaa7;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 2px solid #f5c6cb;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 25px 0;
        }
        th, td {
            padding: 14px 12px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        th {
            background: #f7fafc;
            font-weight: 600;
            color: #2d3748;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        td {
            color: #4a5568;
        }
        .recommended {
            color: #38a169;
            font-weight: 600;
        }
        .section-title {
            font-size: 20px;
            color: #2d3748;
            margin: 30px 0 15px 0;
            padding-bottom: 10px;
            border-bottom: 2px solid #e2e8f0;
        }
        .fix-instructions {
            background: #edf2f7;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .fix-instructions h3 {
            color: #2d3748;
            margin-bottom: 15px;
        }
        .fix-instructions ul {
            margin-left: 20px;
            color: #4a5568;
            line-height: 1.8;
        }
        .fix-instructions code {
            background: #fff;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
            color: #e53e3e;
            font-size: 14px;
        }
        pre {
            background: #2d3748;
            color: #f7fafc;
            padding: 20px;
            border-radius: 6px;
            overflow-x: auto;
            margin: 15px 0;
            font-size: 14px;
            line-height: 1.6;
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 24px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            transition: background 0.3s;
        }
        .back-link:hover {
            background: #5568d3;
        }
        @media (max-width: 768px) {
            .card {
                padding: 20px;
            }
            h1 {
                font-size: 24px;
            }
            table {
                font-size: 14px;
            }
            th, td {
                padding: 10px 8px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h1>📊 PHP Upload Configuration</h1>
            <p class="subtitle">Checking server limits for image uploads</p>

            @php
                $formatBytes = function($bytes) {
                    $units = ['B', 'KB', 'MB', 'GB'];
                    $bytes = max($bytes, 0);
                    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
                    $pow = min($pow, count($units) - 1);
                    $bytes /= (1 << (10 * $pow));
                    return round($bytes, 2) . ' ' . $units[$pow];
                };
                $effectiveMB = $effectiveLimit / 1048576;
            @endphp

            @if ($effectiveLimit >= 15728640)
                <div class="status success">
                    <span class="status-icon">✅</span>
                    <div>
                        <strong>Configuration OK</strong><br>
                        Your server can handle images up to {{ $formatBytes($effectiveLimit) }}
                    </div>
                </div>
            @elseif ($effectiveLimit >= 10485760)
                <div class="status warning">
                    <span class="status-icon">⚠️</span>
                    <div>
                        <strong>Marginal Configuration</strong><br>
                        Limit is {{ $formatBytes($effectiveLimit) }}. Modern phone cameras may produce larger images (8-15MB).
                    </div>
                </div>
            @else
                <div class="status error">
                    <span class="status-icon">❌</span>
                    <div>
                        <strong>Limit Too Low</strong><br>
                        Limit is only {{ $formatBytes($effectiveLimit) }}. Phone camera uploads will fail!
                    </div>
                </div>
            @endif

            <h2 class="section-title">Current PHP Settings</h2>
            <table>
                <thead>
                    <tr>
                        <th>Setting</th>
                        <th>Current Value</th>
                        <th>Recommended</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>upload_max_filesize</strong></td>
                        <td>{{ $uploadMaxFilesize }} ({{ $formatBytes($uploadBytes) }})</td>
                        <td class="recommended">16M or higher</td>
                    </tr>
                    <tr>
                        <td><strong>post_max_size</strong></td>
                        <td>{{ $postMaxSize }} ({{ $formatBytes($postBytes) }})</td>
                        <td class="recommended">32M or higher</td>
                    </tr>
                    <tr>
                        <td><strong>memory_limit</strong></td>
                        <td>{{ $memoryLimit }} ({{ $formatBytes($memoryBytes) }})</td>
                        <td class="recommended">256M or higher</td>
                    </tr>
                    <tr>
                        <td><strong>max_execution_time</strong></td>
                        <td>{{ $maxExecutionTime }} seconds</td>
                        <td class="recommended">300 seconds</td>
                    </tr>
                    <tr>
                        <td><strong>max_input_time</strong></td>
                        <td>{{ $maxInputTime }} seconds</td>
                        <td class="recommended">300 seconds</td>
                    </tr>
                </tbody>
            </table>

            <h2 class="section-title">📱 Phone Camera Reference</h2>
            <table>
                <thead>
                    <tr>
                        <th>Device Type</th>
                        <th>Typical Image Size</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Budget smartphones</td>
                        <td>2-5 MB</td>
                    </tr>
                    <tr>
                        <td>Mid-range phones</td>
                        <td>5-10 MB</td>
                    </tr>
                    <tr>
                        <td>Flagship phones (iPhone, Samsung)</td>
                        <td>8-15 MB</td>
                    </tr>
                    <tr>
                        <td>High-res mode / RAW</td>
                        <td>15-30 MB</td>
                    </tr>
                </tbody>
            </table>

            @if ($effectiveLimit < 15728640)
                <h2 class="section-title">🔧 How to Fix Low Limits</h2>
                <div class="fix-instructions">
                    <h3>Option 1: Contact Xneelo Support</h3>
                    <p>Request the following PHP configuration changes:</p>
                    <ul>
                        <li><code>upload_max_filesize = 16M</code></li>
                        <li><code>post_max_size = 32M</code></li>
                        <li><code>memory_limit = 256M</code></li>
                    </ul>
                </div>

                <div class="fix-instructions">
                    <h3>Option 2: Create php.ini File</h3>
                    <p>Create a file named <code>php.ini</code> in your <code>public_html</code> or root directory:</p>
                    <pre>upload_max_filesize = 16M
post_max_size = 32M
memory_limit = 256M
max_execution_time = 300
max_input_time = 300</pre>
                    <p style="margin-top: 10px;"><strong>Note:</strong> Not all hosting providers allow this. Contact Xneelo if it doesn't work.</p>
                </div>
            @else
                <div class="status success">
                    <span class="status-icon">✅</span>
                    <div>
                        <strong>All Good!</strong><br>
                        Your current limits are sufficient for phone camera uploads.
                    </div>
                </div>
            @endif

            <a href="{{ route('seller.dashboard') }}" class="back-link">← Back to Dashboard</a>
        </div>
    </div>
</body>
</html>
