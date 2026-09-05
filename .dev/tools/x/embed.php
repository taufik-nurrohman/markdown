<?php

require __DIR__ . '/../../from.php';

function embed($r) {
    if (empty($r) || !is_array($r)) {
        return $r;
    }
    foreach ($r as $k => $v) {
        // Got a link (maybe from list item)
        if ('a' === $v[0]) {
            // Check if current link is an auto-link and it has `youtube:` scheme
            if (5 === ($v[3][0] ?? 0) && 0 === strpos($v[2]['href'] ?? "", 'youtube:')) {
                // Convert to `<iframe>` element
                $v[0] = 'iframe';
                $v[1] = "";
                $v[2] = [
                    'frameborder' => 0,
                    'height' => 315,
                    'src' => 'https://www.youtube.com/embed/' . substr($v[2]['href'], 8),
                    'width' => 560,
                ];
                $r[$k] = $v;
            }
            continue;
        }
        // Find paragraph
        if ('p' === $v[0]) {
            // Find lone link
            if (is_array($v[1]) && 1 === count($v[1])) {
                if ('a' === ($a = $v[1][0])[0] ?? 0) {
                    // Check if current link is an auto-link and it has `youtube:` scheme
                    if (5 === ($a[3][0] ?? 0) && 0 === strpos($a[2]['href'] ?? "", 'youtube:')) {
                        // Convert to `<iframe>` element
                        $a[0] = 'iframe';
                        $a[1] = "";
                        $a[2] = [
                            'frameborder' => 0,
                            'height' => 315,
                            'src' => 'https://www.youtube.com/embed/' . substr($a[2]['href'], 8),
                            'width' => 560,
                        ];
                        $r[$k] = $a;
                    }
                }
            }
            continue;
        }
        if (in_array($v[0], ['blockquote', 'dl', 'ol', 'ul'], true)) {
            foreach ($v[1] as $kk => $vv) {
                if (is_array($vv[1] ?? 0)) {
                    $r[$k][1][$kk][1] = embed($vv[1]);
                }
            }
        }
    }
    return $r;
}

$value = <<<MD
asdf asdf asdf asdf

<youtube:yXXgvKQexLs>

asdf asdf asdf asdf

1. asdf asdf asdf asdf
2. <youtube:yXXgvKQexLs>
3. asdf asdf asdf asdf

asdf asdf asdf asdf

1. asdf asdf asdf asdf

   <youtube:yXXgvKQexLs>

   asdf asdf asdf asdf

2. asdf asdf asdf asdf

3. asdf asdf asdf asdf

asdf asdf asdf asdf

    <youtube:yXXgvKQexLs>

asdf asdf asdf asdf

~~~ md
<youtube:yXXgvKQexLs>
~~~

<!--

<youtube:yXXgvKQexLs>

-->

asdf asdf asdf asdf

asdf asdf asdf asdf
: asdf asdf asdf asdf
: <youtube:yXXgvKQexLs>
: asdf asdf asdf asdf

<!-- -->

asdf asdf asdf asdf

: asdf asdf asdf asdf

  <youtube:yXXgvKQexLs>

  asdf asdf asdf asdf

: asdf asdf asdf asdf

: asdf asdf asdf asdf

<!-- -->

> asdf asdf asdf asdf
>
> > asdf asdf asdf asdf
> >
> > <youtube:yXXgvKQexLs>
>
> asdf asdf asdf asdf
MD;

echo '<!DOCTYPE html>' . "\n";
echo '<html dir="ltr">' . "\n";
echo '<head>' . "\n";
echo '<meta content="width=device-width" name="viewport">' . "\n";
echo '<meta charset="utf-8">' . "\n";
echo '<title>Test</title>' . "\n";
echo '</head>' . "\n";
echo '<body>' . "\n";

echo x\markdown\from($value, [
    'tab' => 0,
    'with' => ['embed']
]) . "\n";

echo '</body>' . "\n";
echo '</html>';