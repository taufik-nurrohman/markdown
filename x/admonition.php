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
                // Get first paragraph
                if (is_array($r = reset($row[1])) && 'p' === ($r[0] ?? 0)) {
                    if (is_array($r[1] ?? 0) && is_string($s = reset($r[1])) && 0 === strpos($s, '[!')) {
                        $type = trim(strtolower(substr(strstr($s, ']', true), 2)));
                        $s = ltrim(substr(strstr($s, ']'), 1));
                        // Remove the text if it contains only the admonition tag
                        if ("" === $s) {
                            array_shift($rows[$k][1][0][1]);
                        // Remove the admonition tag from the text
                        } else {
                            $rows[$k][1][0][1][0] = $s;
                        }
                    } else if (is_string($s = $r[1] ?? 0) && 0 === strpos($s, '[!')) {
                        $type = strtolower(substr($s, 2, strpos($s, ']') - 2));
                        $s = ltrim(substr($s, 2 + strlen($type) + 1));
                        // Remove the paragraph if it contains only the admonition tag
                        if ("" === $s) {
                            array_shift($rows[$k][1]);
                        // Remove the admonition tag from the paragraph
                        } else {
                            $rows[$k][1][0][1] = $s;
                        }
                    }
                    if (isset($type)) {
                        $rows[$k][0] = 'div';
                        if ("" !== $type) {
                            $rows[$k][2]['aria-label'] = ucfirst($type);
                        }
                        // <https://kb.daisy.org/publishing/docs/html/dpub-aria/doc-notice.html>
                        // <https://kb.daisy.org/publishing/docs/html/dpub-aria/doc-tip.html>
                        $rows[$k][2]['role'] = 'doc-' . ('tip' !== $type ? 'notice' : $type);
                        $row[1] = $rows[$k][1]; // Update value
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
echo '<style>' . "\n";
echo <<<CSS
[role='doc-notice'],
[role='doc-tip'] {
  background: #ffe;
  border: 1px solid;
  box-shadow: 2px 2px 0 rgb(0 0 0 / 0.125);
  margin: 1em 0;
  padding: 0.5em 0.75em;
}
[role='doc-notice'] :first-child,
[role='doc-tip'] :first-child {
  margin-top: 0;
}
[role='doc-notice'] :last-child,
[role='doc-tip'] :last-child {
  margin-bottom: 0;
}
[role='doc-notice']::before,
[role='doc-tip']::before {
  content: '👋️ Notice';
  display: block;
  font-weight: bold;
  margin: 0 0 0.5em;
}
[aria-label='Caution'][role='doc-notice']::before {
  content: '🔥 Important';
}
[aria-label='Important'][role='doc-notice']::before {
  content: '📢 Caution';
}
[aria-label='Note'][role='doc-notice']::before {
  content: '📒 Note';
}
[aria-label='Warning'][role='doc-notice']::before {
  content: '⚠️ Warning';
}
[aria-label='Tip'][role='doc-tip']::before {
  content: '💡 Tip';
}
CSS;
echo '</style>' . "\n";
echo '</head>' . "\n";
echo '<body>' . "\n";

echo x\markdown\from(file_get_contents(__DIR__ . '/admonition.md'), [
    'tab' => 0,
    'with' => [$admonition]
]) . "\n";

echo '</body>' . "\n";
echo '</html>';