<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --color-background: #0D1B2A;
            --color-background-light: #1B2838;
            --color-primary: #4A90D9;
            --color-secondary: #64B5F6;
            --color-tile: #E8E4DC;
            --color-tile-text: #1A1A1A;
            --color-text-secondary: #8B9DC3;
        }

        body {
            width: 1200px;
            height: 630px;
            background: radial-gradient(ellipse at 30% 20%, rgba(74, 144, 217, 0.15) 0%, transparent 50%),
                        radial-gradient(ellipse at 70% 80%, rgba(100, 181, 246, 0.1) 0%, transparent 50%),
                        linear-gradient(180deg, var(--color-background-light) 0%, var(--color-background) 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            position: relative;
            overflow: hidden;
        }

        .decoration {
            position: absolute;
            border-radius: 8px;
            opacity: 0.08;
        }

        .decoration-1 {
            width: 60px;
            height: 60px;
            background: var(--color-tile);
            top: 80px;
            left: 100px;
            transform: rotate(15deg);
        }

        .decoration-2 {
            width: 50px;
            height: 50px;
            background: var(--color-primary);
            top: 120px;
            right: 150px;
            transform: rotate(-10deg);
        }

        .decoration-3 {
            width: 45px;
            height: 45px;
            background: var(--color-tile);
            bottom: 100px;
            left: 180px;
            transform: rotate(-20deg);
        }

        .decoration-4 {
            width: 55px;
            height: 55px;
            background: var(--color-secondary);
            bottom: 140px;
            right: 120px;
            transform: rotate(25deg);
        }

        .content {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 32px;
            z-index: 1;
        }

        .tiles {
            display: flex;
            gap: 12px;
        }

        .tile {
            width: 88px;
            height: 88px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 52px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }

        .tile-primary {
            background-color: var(--color-primary);
            color: white;
        }

        .tile-default {
            background-color: var(--color-tile);
            color: var(--color-tile-text);
        }

        .tagline {
            text-align: center;
        }

        .tagline h1 {
            font-size: 54px;
            font-weight: 700;
            color: white;
            margin-bottom: 16px;
        }

        .tagline h1 span {
            background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-secondary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .tagline p {
            font-size: 28px;
            color: var(--color-text-secondary);
        }
    </style>
</head>
<body>
    <div class="decoration decoration-1"></div>
    <div class="decoration decoration-2"></div>
    <div class="decoration decoration-3"></div>
    <div class="decoration decoration-4"></div>

    <div class="content">
        <div class="tiles">
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

        <div class="tagline">
            <h1>Challenge friends to a <span>battle of words</span></h1>
            <p>Free multiplayer word game for iOS & Android</p>
        </div>
    </div>
</body>
</html>
