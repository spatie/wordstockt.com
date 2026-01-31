<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset - WordStockt</title>
    <style>
        :root {
            --color-background: #0D1B2A;
            --color-primary: #4A90D9;
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
            background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
        }
        .icon svg {
            width: 40px;
            height: 40px;
            fill: white;
        }
        h1 {
            color: #333;
            font-size: 24px;
            margin-bottom: 12px;
        }
        p {
            color: #666;
            font-size: 16px;
            line-height: 1.5;
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
        <div class="icon">
            <svg viewBox="0 0 24 24">
                <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
            </svg>
        </div>
        <h1>Password Reset!</h1>
        <p>Your password has been successfully reset. You can now log in to the WordStockt app with your new password.</p>
    </div>
</body>
</html>
