<?php

require __DIR__ . '/../from.php';

$external = static function (array $rows) use (&$external) {
    if (!$rows) {
        return $rows;
    }
    $link = static function (array $a) {
        $href = $a[2]['href'] ?? "";
        if ("" === $href) {
            return $a;
        }
        if (false === strpos($href, '://')) {
            return $a;
        }
        $a[2]['rel'] = 'nofollow noopener noreferrer';
        $a[2]['target'] = '_blank';
        return $a;
    };
    foreach ($rows as $k => $row) {
        // Current data is a link, possibly from a “tight” list item
        if (is_array($row) && 'a' === ($row[0] ?? 0)) {
            $rows[$k] = $link($row);
            continue;
        }
        if (is_array($row[1] ?? 0) && in_array($row[0], ['blockquote', 'dl', 'ol', 'ul'], true)) {
            foreach ($row[1] as $kk => $vv) {
                if (is_array($vv[1] ?? 0)) {
                    $rows[$k][1][$kk][1] = $external($vv[1]);
                }
            }
            continue;
        }
        if (is_array($row[1] ?? 0)) {
            foreach ($row[1] as $kk => $vv) {
                if (is_array($vv) && 'a' === ($vv[0] ?? 0)) {
                    $rows[$k][1][$kk] = $link($vv);
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
echo '<title>External Link Extension</title>' . "\n";
echo '</head>' . "\n";
echo '<body>' . "\n";

echo x\markdown\from(file_get_contents(__DIR__ . '/external.md'), [
    'tab' => 0,
    'with' => [$external]
]) . "\n";

echo '</body>' . "\n";
echo '</html>';