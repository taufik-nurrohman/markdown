<?php

require __DIR__ . '/../from.php';

$embed = static function (array $rows) use (&$embed) {
    if (!$rows) {
        return $rows;
    }
    $try = static function (array $a) {
        // Verify that the link is an auto-link
        if (5 !== $a[3][0] ?? 0) {
            return false;
        }
        if (!$href = $a[2]['href'] ?? 0) {
            return false;
        }
        // Auto-link scheme must be at least two letter(s)
        // <https://spec.commonmark.org/0.31.2#scheme>
        [$key, $value] = explode(':', $href, 2);
        // GitHub Gist
        if ('gist' === $key) {
            $a[0] = 'script';
            $a[1] = "";
            $a[2] = ['src' => 'https://gist.github.com/' . $value . '.js'];
            return [$a, false]; // Remove paragraph
        }
        // Vimeo
        if (in_array($key, ['vimeo', 'vm'], true)) {
            $a[0] = 'iframe';
            $a[1] = "";
            $a[2] = [
                'src' => 'https://player.vimeo.com/video/' . $value,
                'style' => 'aspect-ratio:16/9;border-radius:0;border:0;box-shadow:none;display:block;margin:0;outline:0;padding:0;width:100%;'
            ];
            return [$a, true];
        }
        // YouTube
        if (in_array($key, ['youtube', 'yt'], true)) {
            $a[0] = 'iframe';
            $a[1] = "";
            $a[2] = [
                'src' => 'https://www.youtube.com/embed/' . $value,
                'style' => 'aspect-ratio:16/9;border-radius:0;border:0;box-shadow:none;display:block;margin:0;outline:0;padding:0;width:100%;'
            ];
            return [$a, true];
        }
        return false;
    };
    foreach ($rows as $k => $row) {
        // Current data is a link, possibly from a “tight” list item
        if (is_array($row) && 'a' === ($row[0] ?? 0) && 1 === count($rows)) {
            if ($r = $try($row)) {
                $rows[$k] = $r[0];
            }
            continue;
        }
        // Find paragraph
        if ('p' === ($row[0] ?? 0)) {
            // Find a link that stands alone
            if (is_array($row[1]) && 1 === count($row[1]) && is_array($r = $row[1][0] ?? 0) && 'a' === ($r[0] ?? 0)) {
                if ($r = $try($r)) {
                    $rows[$k][1] = [$r[0]];
                    if (false === $r[1]) {
                        $rows[$k][0] = null; // Remove paragraph
                    } else {
                        $rows[$k][2]['style'] = 'display:flex;justify-content:center;margin-left:0;margin-right:0;padding:0;';
                    }
                }
            }
            continue;
        }
        // Recurse to look for “embed” syntax in child data
        if (is_array($row[1] ?? 0)) {
            $rows[$k][1] = $embed($row[1]);
        }
    }
    return $rows;
};

echo '<!DOCTYPE html>' . "\n";
echo '<html dir="ltr">' . "\n";
echo '<head>' . "\n";
echo '<meta content="width=device-width" name="viewport">' . "\n";
echo '<meta charset="utf-8">' . "\n";
echo '<title>Embed Extension</title>' . "\n";
echo '</head>' . "\n";
echo '<body>' . "\n";

echo x\markdown\from(file_get_contents(__DIR__ . '/embed.md'), [
    'tab' => 0,
    'with' => [$embed]
]) . "\n";

echo '</body>' . "\n";
echo '</html>';