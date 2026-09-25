<?php

require __DIR__ . '/../from.php';

$tab = static function (array $rows) use (&$tab) {
    if (!$rows) {
        return $rows;
    }
    foreach ($rows as $k => $row) {
        if (is_array($row) && 'pre' === ($row[0] ?? 0)) {
            $r = [];
            $text = $row[1][0][1];
            foreach (explode("\n", $text) as $t) {
                $n = strspn($t, ' ');
                if ($n >= 4) {
                    $r[] = str_repeat("\t", $n >> 2) . substr($t, ($n >> 2) << 2);
                    continue;
                }
                $r[] = $t;
            }
            $rows[$k][1][0][1] = implode("\n", $r);
            continue;
        }
        // Recurse to look for code block syntax in child data
        if (is_array($row[1] ?? 0)) {
            $rows[$k][1] = $tab($row[1]);
        }
    }
    return $rows;
};

echo '<!DOCTYPE html>' . "\n";
echo '<html dir="ltr">' . "\n";
echo '<head>' . "\n";
echo '<meta content="width=device-width" name="viewport">' . "\n";
echo '<meta charset="utf-8">' . "\n";
echo '<title>Tab Extension</title>' . "\n";
echo '<style>pre{tab-size:4}</style>';
echo '</head>' . "\n";
echo '<body>' . "\n";

echo x\markdown\from(file_get_contents(__DIR__ . '/tab.md'), [
    'tab' => 0,
    'with' => [$tab]
]) . "\n";

echo '</body>' . "\n";
echo '</html>';