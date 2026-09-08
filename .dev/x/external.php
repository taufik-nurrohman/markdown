<?php

require __DIR__ . '/../from.php';

function external(array $rows) {
    if (empty($rows) || !is_array($rows)) {
        return $rows;
    }
    foreach ($rows as $k => $row) {
        // Current node is a link, possibly from a “tight” list item
        if (is_array($row) && 'a' === ($row[0] ?? 0)) {
            $rows[$k] = external_link($row);
            continue;
        }
        if (is_array($row[1] ?? 0) && in_array($row[0], ['blockquote', 'dl', 'ol', 'ul'], true)) {
            foreach ($row[1] as $kk => $vv) {
                if (is_array($vv[1] ?? 0)) {
                    $rows[$k][1][$kk][1] = external($vv[1]);
                }
            }
            continue;
        }
        if (is_array($row[1] ?? 0)) {
            foreach ($row[1] as $kk => $vv) {
                if (is_array($vv) && 'a' === ($vv[0] ?? 0)) {
                    $rows[$k][1][$kk] = external_link($vv);
                }
            }
        }
    }
    return $rows;
}

function external_link(array $a) {
    $href = $a[2]['href'] ?? "";
    if ("" === $href) {
        return $a;
    }
    if (false === strpos($href, '://')) {
        return $a;
    }
    $a[2]['rel'] = 'nofollow';
    $a[2]['style'] = 'color:#f00;';
    $a[2]['target'] = '_blank';
    return $a;
}

echo '<!DOCTYPE html>' . "\n";
echo '<html dir="ltr">' . "\n";
echo '<head>' . "\n";
echo '<meta content="width=device-width" name="viewport">' . "\n";
echo '<meta charset="utf-8">' . "\n";
echo '<title>External Link Extension</title>' . "\n";
echo '</head>' . "\n";
echo '<body style="margin:0 auto;max-width:48em;padding:1em;">' . "\n";

echo x\markdown\from(file_get_contents(__DIR__ . '/external.md'), [
    'tab' => 0,
    'with' => ['external']
]) . "\n";

echo '</body>' . "\n";
echo '</html>';