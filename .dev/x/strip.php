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
        // Recurse to look for potential raw HTML in container block(s)
        if (in_array($row[0] ?? 0, ['blockquote', 'dl', 'ol', 'ul'], true)) {
            foreach ($row[1] as $kk => $vv) {
                if (is_array($vv[1] ?? 0)) {
                    $rows[$k][1][$kk][1] = $strip($vv[1]);
                }
            }
        }
        if (is_array($row[1])) {
            foreach ($row[1] as $kk => $vv) {
                // Find raw HTML
                if (false === ($vv[0] ?? 0)) {
                    $rows[$k][1][$kk][1] = strip_tags($vv[1]);
                }
            }
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