<?php

require __DIR__ . '/../from.php';

$note = [
    // Pre-parse
    null,
    // Parse
    null,
    // Post-parse
    static function (?string $value) {
        if (!$value || false === strpos($value, '<strong>NOTE:</strong>')) {
            return $value;
        }
        return preg_replace_callback('/<hr\s*\/?>(\s*<p><strong>NOTE:<\/strong>.*?<\/p>\s*)<hr\s*\/?>/s', static function ($m) {
            return '<div role="note">' . $m[1] . '</div>';
        }, $value);
    }
];

echo '<!DOCTYPE html>' . "\n";
echo '<html dir="ltr">' . "\n";
echo '<head>' . "\n";
echo '<meta content="width=device-width" name="viewport">' . "\n";
echo '<meta charset="utf-8">' . "\n";
echo '<title>Note Extension</title>' . "\n";
echo '</head>' . "\n";
echo '<body>' . "\n";

echo x\markdown\from(file_get_contents(__DIR__ . '/note.md'), [
    'tab' => 0,
    'with' => [$note]
]) . "\n";

echo '</body>' . "\n";
echo '</html>';