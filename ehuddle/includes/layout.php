<?php

declare(strict_types=1);

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function renderLayoutStart(string $title): void
{
    ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($title) ?></title>
    <script>
        tailwind = {
            config: {
                theme: {
                    extend: {
                        colors: {
                            background: 'hsl(45, 100%, 96%)',
                            foreground: 'hsl(260, 30%, 15%)',
                            card: 'hsl(45, 100%, 98%)',
                            primary: 'hsl(221, 83%, 45%)',
                            secondary: 'hsl(210, 90%, 65%)',
                            muted: 'hsl(45, 40%, 90%)',
                            accent: 'hsl(45, 95%, 58%)',
                            'muted-foreground': 'hsl(260, 15%, 45%)',
                            'primary-foreground': 'hsl(0, 0%, 100%)',
                            'cartoon-orange': 'hsl(25, 95%, 55%)',
                            'cartoon-purple': 'hsl(270, 70%, 55%)',
                            'cartoon-green': 'hsl(145, 70%, 45%)',
                            'cartoon-pink': 'hsl(330, 85%, 65%)',
                            'cartoon-yellow': 'hsl(45, 95%, 58%)',
                            'cartoon-blue': 'hsl(215, 90%, 50%)',
                            'cartoon-red': 'hsl(0, 80%, 55%)'
                        },
                        fontFamily: {
                            display: ['"Baloo 2"', 'cursive'],
                            body: ['Nunito', 'sans-serif']
                        }
                    }
                }
            }
        };
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@400;500;600;700;800&family=Nunito:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="/assets/style.css">
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/assets/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="192x192" href="/assets/favicon-192x192.png">
</head>
<body class="bg-background text-foreground font-body">
<?php
}

function renderLayoutEnd(): void
{
    $apiBaseUrl = getenv('EH_API_BASE_URL');
    if (!is_string($apiBaseUrl)) {
        $apiBaseUrl = '';
    }
    ?>
<script>
    window.EH_CONFIG = {
        apiBaseUrl: <?= json_encode(rtrim($apiBaseUrl, '/'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>
    };
</script>
<script src="/assets/ui.js"></script>
</body>
</html>
<?php
}
