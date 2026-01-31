<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - WordStockt</title>
    <style>
        :root {
            --color-background: #0D1B2A;
            --color-background-light: #1B2838;
            --color-primary: #4A90D9;
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
        }
        h1 {
            color: #333;
            font-size: 24px;
            margin-bottom: 8px;
            text-align: center;
        }
        .subtitle {
            color: #666;
            text-align: center;
            margin-bottom: 30px;
            font-size: 14px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            color: #555;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 6px;
        }
        input[type="password"] {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e1e1e1;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.2s;
        }
        input[type="password"]:focus {
            outline: none;
            border-color: var(--color-primary);
        }
        .errors {
            background: #fed7d7;
            border: 1px solid #fc8181;
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 20px;
        }
        .errors ul {
            list-style: none;
            color: #c53030;
            font-size: 14px;
        }
        button {
            width: 100%;
            padding: 14px;
            background-color: var(--color-primary);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        button:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(74, 144, 217, 0.4);
        }
        button:active {
            transform: translateY(0);
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
        <h1>Reset Password</h1>
        <p class="subtitle">Enter your new password below</p>

        @if ($errors->any())
            <div class="errors">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ url('/reset-password/' . $token) }}">
            @csrf
            <input type="hidden" name="email" value="{{ $email }}">

            <div class="form-group">
                <label for="password">New Password</label>
                <input type="password" id="password" name="password" required minlength="8" autofocus>
            </div>

            <div class="form-group">
                <label for="password_confirmation">Confirm Password</label>
                <input type="password" id="password_confirmation" name="password_confirmation" required minlength="8">
            </div>

            <button type="submit">Reset Password</button>
        </form>
    </div>
</body>
</html>
