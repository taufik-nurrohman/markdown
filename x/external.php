<?php

require __DIR__ . '/../from.php';

$external = static function (array $rows) use (&$external) {
    if (!$rows) {
        return $rows;
    }
    $try = static function (array $a) {
        $href = $a[2]['href'] ?? "";
        // An external link is a link with an empty destination or with a destination that lacks the protocol part
        if ("" === $href || false === strpos($href, '://')) {
            return $a;
        }
        $a[2]['rel'] ??= 'nofollow noopener noreferrer';
        $a[2]['target'] ??= '_blank';
        return $a;
    };
    foreach ($rows as $k => $row) {
        // Current data is a link, possibly from a “tight” list item
        if (is_array($row) && 'a' === ($row[0] ?? 0)) {
            $rows[$k] = $try($row);
            continue;
        }
        // Recurse to look for link syntax in child data
        if (is_array($row[1] ?? 0)) {
            $rows[$k][1] = $external($row[1]);
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
echo '<style>[target^="_"]::after{content:\'↗\'}</style>';
echo '</head>' . "\n";
echo '<body>' . "\n";

echo x\markdown\from(file_get_contents(__DIR__ . '/external.md'), [
    'tab' => 0,
    'with' => [$external]
]) . "\n";

echo '</body>' . "\n";
echo '</html>';