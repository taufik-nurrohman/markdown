<?php

require __DIR__ . '/../from.php';

$strip = static function (array $rows) use (&$strip) {
    if (!$rows) {
        return $rows;
    }
    foreach ($rows as $k => $row) {
        // Find raw HTML
        if (false === ($row[0] ?? 0)) {
            $rows[$k][1] = strip_tags($row[1]);
            continue;
        }
        // Recurse to look for raw HTML syntax in child data
        if (is_array($row[1] ?? 0)) {
            $rows[$k][1] = $strip($row[1]);
        }
    }
    return $rows;
};

echo '<!DOCTYPE html>' . "\n";
echo '<html dir="ltr">' . "\n";
echo '<head>' . "\n";
echo '<meta content="width=device-width" name="viewport">' . "\n";
echo '<meta charset="utf-8">' . "\n";
echo '<title>Strip Extension</title>' . "\n";
echo '</head>' . "\n";
echo '<body>' . "\n";

echo x\markdown\from(file_get_contents(__DIR__ . '/strip.md'), [
    'tab' => 0,
    'with' => [$strip]
]) . "\n";

echo '</body>' . "\n";
echo '</html>';