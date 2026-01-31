<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Email - WordStockt</title>
    <style>
        :root {
            --color-background: #0D1B2A;
            --color-background-light: #1B2838;
            --color-primary: #4A90D9;
            --color-success: #48BB78;
            --color-error: #F56565;
            --color-text-secondary: #8B9DC3;
            --color-tile: #E8E4DC;
            --color-tile-text: #1A1A1A;
        }
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: var(--color-background);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .wordmark {
            display: flex;
            gap: 4px;
            margin-bottom: 32px;
        }
        .tile {
            width: 32px;
            height: 32px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            font-weight: bold;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        .tile-primary {
            background-color: var(--color-primary);
            color: white;
        }
        .tile-default {
            background-color: var(--color-tile);
            color: var(--color-tile-text);
        }
        .container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            padding: 40px;
            width: 100%;
            max-width: 400px;
            text-align: center;
        }
        .icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .icon-success {
            background-color: rgba(72, 187, 120, 0.1);
        }
        .icon-error {
            background-color: rgba(245, 101, 101, 0.1);
        }
        .icon svg {
            width: 40px;
            height: 40px;
        }
        .icon-success svg {
            color: var(--color-success);
        }
        .icon-error svg {
            color: var(--color-error);
        }
        h1 {
            color: #333;
            font-size: 24px;
            margin-bottom: 12px;
        }
        .message {
            color: #666;
            font-size: 16px;
            line-height: 1.5;
            margin-bottom: 24px;
        }
        .username {
            color: var(--color-primary);
            font-weight: 600;
        }
        .cta {
            color: #888;
            font-size: 14px;
            margin-top: 16px;
        }
        .app-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 12px;
            padding: 12px 24px;
            background: var(--color-background);
            color: white;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .app-badge:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }
    </style>
</head>
<body>
    <div class="wordmark">
        <div class="tile tile-primary">W</div>
        <div class="tile tile-default">O</div>
        <div class="tile tile-default">R</div>
        <div class="tile tile-default">D</div>
        <div class="tile tile-primary">S</div>
        <div class="tile tile-default">T</div>
        <div class="tile tile-default">O</div>
        <div class="tile tile-default">C</div>
        <div class="tile tile-default">K</div>
        <div class="tile tile-default">T</div>
    </div>

    <div class="container">
        @if($success)
            <div class="icon icon-success">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <h1>Email Verified</h1>
            @if(isset($username))
                <p class="message">Welcome, <span class="username">{{ $username }}</span>! {{ $message }}</p>
            @else
                <p class="message">{{ $message }}</p>
            @endif
            <p class="cta">You can now open the app and start playing.</p>
            <a href="wordstockt://" class="app-badge">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                </svg>
                Open WordStockt
            </a>
        @else
            <div class="icon icon-error">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </div>
            <h1>Verification Failed</h1>
            <p class="message">{{ $message }}</p>
            <p class="cta">Please request a new verification email from the app.</p>
            <a href="wordstockt://" class="app-badge">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                </svg>
                Open WordStockt
            </a>
        @endif
    </div>
</body>
</html>
