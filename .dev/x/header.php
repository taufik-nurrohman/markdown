<?php

require __DIR__ . '/../from.php';

echo '<!DOCTYPE html>' . "\n";
echo '<html dir="ltr">' . "\n";
echo '<head>' . "\n";
echo '<meta content="width=device-width" name="viewport">' . "\n";
echo '<meta charset="utf-8">' . "\n";
echo '<title>Header Extension</title>' . "\n";
echo '<style>:target{background:#ff0}</style>' . "\n";
echo '</head>' . "\n";
echo '<body>' . "\n";

$header = static function (array $rows) use (&$header) {
    if (!$rows) {
        return $rows;
    }
    static $f = []; // Keep track of the defined automatic identifier(s) to avoid duplicate(s)
    foreach ($rows as $k => $row) {
        // Find header
        if (in_array($row[0] ?? 0, ['h2', 'h3', 'h4', 'h5', 'h6'], true)) {
            $text = "";
            if (is_array($row[1])) {
                foreach ($row[1] as $r) {
                    if (is_array($r) && false !== $r[0]) {
                        if (is_string($r[1])) {
                            $text .= $r[1];
                        }
                        continue;
                    }
                    if (is_string($r)) {
                        $text .= $r;
                    }
                }
            } else if (is_string($row[1])) {
                $text = $row[1];
            }
            $text = html_entity_decode($text, ENT_HTML5 | ENT_QUOTES, 'UTF-8');
            $id = $row[2]['id'] ?? trim(preg_replace('/[^a-z\d]+/', '-', strtolower($text)), '-');
            $f[$id] = ($f[$id] ?? -1) + 1;
            $id .= ($f[$id] > 0 ? '.' . $f[$id] : ""); // Add a suffix if necessary
            // Prepend an anchor element that points to this header
            $anchor = ['a', '&#x2693;', [
                'href' => '#' . $id,
                'style' => 'text-decoration: none;'
            ]];
            if (is_array($row[1])) {
                array_unshift($rows[$k][1], $anchor, ' ');
            } else if (is_string($row[1])) {
                $rows[$k][1] = [$anchor, ' ', $row[1]];
            }
            // Add `id` attribute
            $rows[$k][2]['id'] = $id;
            continue;
        }
        // Recurse to look for header syntax in container block(s)
        if (in_array($row[0] ?? 0, ['blockquote', 'dl', 'ol', 'ul'], true) && is_array($row[1])) {
            $rows[$k][1] = $header($row[1]);
        }
    }
    return $rows;
};

echo x\markdown\from(file_get_contents(__DIR__ . '/header.md'), [
    'tab' => 0,
    'with' => [$header]
]) . "\n";

echo '</body>' . "\n";
echo '</html>';