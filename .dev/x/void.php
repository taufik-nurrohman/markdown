<?php

require __DIR__ . '/../from.php';

function _void(array $rows) {
    if (empty($rows) || !is_array($rows)) {
        return $rows;
    }
    foreach ($rows as $k => $row) {
        // Find thematic break
        if ('hr' === ($row[0] ?? 0)) {
            // Add content so that it will be treated as an HTML element with a content.
            // Use text that is difficult to write by hand so that we can remove it later with confidence.
            $rows[$k][1] = "\x1a";
        }
        // Recurse to look for potential void syntax in container block(s)
        if (in_array($row[0] ?? 0, ['blockquote', 'dl', 'ol', 'ul'], true)) {
            foreach ($row[1] as $kk => $vv) {
                if (is_array($vv[1] ?? 0)) {
                    $rows[$k][1][$kk][1] = _void($vv[1]);
                }
            }
        }
        if (is_array($row[1])) {
            foreach ($row[1] as $kk => $vv) {
                // Find image
                if ('img' === ($vv[0] ?? 0)) {
                    // Apply the same change to the image syntax
                    $rows[$k][1][$kk][1] = "\x1a";
                }
            }
        }
    }
    return $rows;
}

echo '<!DOCTYPE html>' . "\n";
echo '<html dir="ltr">' . "\n";
echo '<head>' . "\n";
echo '<meta content="width=device-width" name="viewport">' . "\n";
echo '<meta charset="utf-8">' . "\n";
echo '<title>Void Extension</title>' . "\n";
echo '</head>' . "\n";
echo '<body style="margin:0 auto;max-width:48em;padding:1em;">' . "\n";

$value = x\markdown\from(file_get_contents(__DIR__ . '/void.md'), [
    'tab' => 0,
    'with' => ['_void']
]);

// Now, remove the marker(s) with the closing tag that follows
$value = strtr($value, [
    "\x1a</hr>" => "",
    "\x1a</img>" => ""
]);

echo $value . "\n";

echo '</body>' . "\n";
echo '</html>';