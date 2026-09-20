<?php

require __DIR__ . '/../from.php';

echo '<!DOCTYPE html>' . "\n";
echo '<html dir="ltr">' . "\n";
echo '<head>' . "\n";
echo '<meta content="width=device-width" name="viewport">' . "\n";
echo '<meta charset="utf-8">' . "\n";
echo '<title>Define Extension</title>' . "\n";
echo '</head>' . "\n";
echo '<body>' . "\n";

$extra = implode("\n", [
    "",
    '*[CSS]: asdf',
    '*[HTML]: asdf',
    '*[JS]: asdf',
    "",
    '[^1]: asdf asdf asdf asdf',
    "",
    '[asdf]: asdf',
]);

echo x\markdown\from(file_get_contents(__DIR__ . '/define.md') . "\n" . $extra, [
    'tab' => 0
]) . "\n";

echo '</body>' . "\n";
echo '</html>';