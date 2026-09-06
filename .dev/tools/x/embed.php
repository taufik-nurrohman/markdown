<?php

require __DIR__ . '/../../from.php';

function embed(array $rows) {
    if (empty($rows) || !is_array($rows)) {
        return $rows;
    }
    foreach ($rows as $k => $row) {
        // Current node is a link, possibly from a “tight” list item
        if ('a' === (($a = $row ?? [])[0] ?? 0)) {
            if ($a = embed_link($a)) {
                $rows[$k] = $a;
            }
            continue;
        }
        // Find paragraph
        if ('p' === ($row[0] ?? 0)) {
            // Find a link that stands alone
            if (is_array($row[1]) && 1 === count($row[1]) && 'a' === (($a = $row[1][0] ?? [])[0] ?? 0)) {
                if ($a = embed_link($a)) {
                    $rows[$k][1] = [$a];
                    $rows[$k][2]['style'] = 'display:flex;justify-content:center;margin-left:0;margin-right:0;padding:0;';
                }
            }
            continue;
        }
        // Recurse to look for potential embed syntax in container block(s)
        if (in_array($row[0] ?? 0, ['blockquote', 'dl', 'ol', 'ul'], true)) {
            foreach ($row[1] as $kk => $vv) {
                if (is_array($vv[1] ?? 0)) {
                    $rows[$k][1][$kk][1] = embed($vv[1]);
                }
            }
        }
    }
    return $rows;
}

function embed_link(array $a) {
    // Verify that the current link is an auto-link
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
        $a[2] = ['src' => 'https://gist.github.com/taufik-nurrohman/' . $value . '.js'];
        return $a;
    }
    // Vimeo
    if (in_array($key, ['vimeo', 'vm'], true)) {
        $a[0] = 'iframe';
        $a[1] = "";
        $a[2] = [
            'src' => 'https://player.vimeo.com/video/' . $value,
            'style' => 'aspect-ratio:16/9;border-radius:0;border:0;box-shadow:none;display:block;margin:0;outline:0;padding:0;width:100%;'
        ];
        return $a;
    }
    // YouTube
    if (in_array($key, ['youtube', 'yt'], true)) {
        $a[0] = 'iframe';
        $a[1] = "";
        $a[2] = [
            'src' => 'https://www.youtube.com/embed/' . $value,
            'style' => 'aspect-ratio:16/9;border-radius:0;border:0;box-shadow:none;display:block;margin:0;outline:0;padding:0;width:100%;'
        ];
        return $a;
    }
    return false;
}

echo '<!DOCTYPE html>' . "\n";
echo '<html dir="ltr">' . "\n";
echo '<head>' . "\n";
echo '<meta content="width=device-width" name="viewport">' . "\n";
echo '<meta charset="utf-8">' . "\n";
echo '<title>Embed Extension</title>' . "\n";
echo '</head>' . "\n";
echo '<body style="
margin:0 auto;
max-width:48em;
padding:1em;
">' . "\n";

echo x\markdown\from(file_get_contents(__DIR__ . '/embed.md'), [
    'tab' => 0,
    'with' => ['embed']
]) . "\n";

echo '</body>' . "\n";
echo '</html>';