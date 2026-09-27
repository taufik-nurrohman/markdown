<?php

require __DIR__ . '/../from.php';

$admonition = static function (array $rows) use (&$admonition) {
    if (!$rows) {
        return $rows;
    }
    foreach ($rows as $k => $row) {
        // Find quote block
        if ('blockquote' === ($row[0] ?? 0)) {
            if (is_array($row[1] ?? 0)) {
                if (is_array($r = reset($row[1])) && 'p' === ($r[0] ?? 0)) {
                    if (is_array($r[1] ?? 0) && is_string($s = reset($r[1]))) {
                        // TODO
                        echo json_encode($r[1]);
                        echo '<br/>';
                    } else if (is_string($s = $r[1] ?? 0) && 0 === strpos($s, '[!')) {
                        $type = strtolower(substr($s, 2, strpos($s, ']') - 2));
                        $s = trim(substr($s, 2 + strlen($type) + 1));
                        if ("" === $s) {
                            array_shift($rows[$k][1]);
                        } else {
                            $rows[$k][1][0][1] = $s;
                        }
                        $rows[$k][0] = 'div';
                        $rows[$k][2]['class'] = 'admonition admonition-' . $type;
                        // <https://kb.daisy.org/publishing/docs/html/dpub-aria/doc-notice.html>
                        // <https://kb.daisy.org/publishing/docs/html/dpub-aria/doc-tip.html>
                        $rows[$k][2]['role'] = 'doc-' . ('tip' !== $type ? 'notice' : $type);
                        $rows[$k][2]['style'] = 'color:#00f;';
                        continue;
                    }
                }
            }
        }
        // Recurse to look for admonition syntax in child data
        if (is_array($row[1] ?? 0)) {
            $rows[$k][1] = $admonition($row[1]);
        }
    }
    return $rows;
};

echo '<!DOCTYPE html>' . "\n";
echo '<html dir="ltr">' . "\n";
echo '<head>' . "\n";
echo '<meta content="width=device-width" name="viewport">' . "\n";
echo '<meta charset="utf-8">' . "\n";
echo '<title>Admonition Extension</title>' . "\n";
echo '</head>' . "\n";
echo '<body>' . "\n";

echo x\markdown\from(file_get_contents(__DIR__ . '/admonition.md'), [
    'tab' => 0,
    'with' => [$admonition]
]) . "\n";

echo '</body>' . "\n";
echo '</html>';