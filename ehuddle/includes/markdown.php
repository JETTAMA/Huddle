<?php

declare(strict_types=1);

function parseMarkdown(string $text): string
{
    $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

    $text = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $text);
    $text = preg_replace('/\*(.*?)\*/', '<em>$1</em>', $text);

    $text = preg_replace_callback('/\$\[(.*?)\]\$/', function($matches) {
        $expr = html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8');
        
        if (preg_match('/[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*\(/', $expr)) {
            return '[Error: Logic Blocked]';
        }
        
        if (strpos($expr, '$') !== false || strpos($expr, '`') !== false) {
            return '[Error: Illegal Characters]';
        }

        try {
            ob_start();
            $result = eval('return ' . $expr . ';');
            $out = ob_get_clean();
            return (string)($result ?: $out);
        } catch (Throwable $e) {
            return '';
        }
    }, $text);

    return $text;
}
