<?php

require __DIR__ . '/../from.php';

echo '<!DOCTYPE html>' . "\n";
echo '<html dir="ltr">' . "\n";
echo '<head>' . "\n";
echo '<meta content="width=device-width" name="viewport">' . "\n";
echo '<meta charset="utf-8">' . "\n";
echo '<title>Note Extension</title>' . "\n";
echo '</head>' . "\n";
echo '<body>' . "\n";

$value = x\markdown\from(file_get_contents(__DIR__ . '/note.md'), [
    'tab' => 0
]);

$value = preg_replace_callback('/<hr\s*\/?>(\s*<p><strong>NOTE:<\/strong>[\s\S]*?<\/p>\s*)<hr\s*\/?>/', static function ($m) {
    return '<div role="note">' . $m[1] . '</div>';
}, $value);

echo $value . "\n";

echo '</body>' . "\n";
echo '</html>';