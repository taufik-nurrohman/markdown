<?php

require __DIR__ . '/../../from.php';

function external($r) {
    if (empty($r) || !is_array($r)) {
        return $r;
    }
    foreach ($r as $k => $v) {
        // Got a link (maybe from tight list item)
        if (is_array($v) && 'a' === ($v[0] ?? 0)) {
            $r[$k] = external_link($v);
            continue;
        }
        if (is_array($v[1] ?? 0) && in_array($v[0], ['blockquote', 'dl', 'ol', 'ul'], true)) {
            foreach ($v[1] as $kk => $vv) {
                if (is_array($vv[1] ?? 0)) {
                    $r[$k][1][$kk][1] = external($vv[1]);
                }
            }
            continue;
        }
        if (is_array($v[1] ?? 0)) {
            foreach ($v[1] as $kk => $vv) {
                if (is_array($vv) && 'a' === ($vv[0] ?? 0)) {
                    $r[$k][1][$kk] = external_link($vv);
                }
            }
        }
    }
    return $r;
}

function external_link(array $a) {
    // TODO: Determine if link is external
    $external = true;
    if (!$external) {
        return $a;
    }
    $a[2]['rel'] = 'nofollow';
    $a[2]['style'] = 'color:#f00;';
    $a[2]['target'] = '_blank';
    return $a;
}

$value = <<<MD
asdf asdf asdf [asdf](asdf)

1. asdf asdf asdf asdf
1. asdf asdf asdf [asdf](asdf)
1. asdf asdf asdf asdf

asdf asdf asdf asdf

1. asdf asdf asdf asdf

2. asdf asdf asdf [asdf](asdf)

3. asdf asdf asdf asdf

asdf asdf asdf asdf

    asdf asdf asdf [asdf](asdf)

asdf asdf asdf asdf

~~~ md
asdf asdf asdf [asdf](asdf)
~~~

<!--

asdf asdf asdf [asdf](asdf)

-->

asdf asdf asdf asdf

asdf asdf asdf asdf
: asdf asdf asdf asdf
: asdf asdf asdf [asdf](asdf)
: asdf asdf asdf asdf

<!-- -->

asdf asdf asdf asdf

: asdf asdf asdf asdf

: asdf asdf asdf [asdf](asdf)

: asdf asdf asdf asdf

<!-- -->

> asdf asdf asdf asdf
>
> > asdf asdf asdf asdf
> >
> > asdf asdf asdf [asdf](asdf)
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
    'with' => ['external']
]) . "\n";

echo '</body>' . "\n";
echo '</html>';