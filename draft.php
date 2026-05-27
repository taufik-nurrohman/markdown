<?php

function n($text) {
    if ($t = \strspn($text, ' ')) {
        $text = \substr($text, $t > 4 ? 4 : $t);
    }
    return [$text, $t];
}

function row($text) {}

function rows($text) {
    $r = ['p', "", [], 0];
    $raws = \explode("\n", \rtrim(\strtr($text, [
        "\r\n" => "\n",
        "\r" => "\n"
    ]), "\n"));
    $rows = [];
    foreach ($raws as $raw) {
        [$row, $t] = n($raw);
        if (false === $r[0]) {
            if (false !== \strpos(',pre,script,style,textarea', ',' . $r[4][1] . ',')) {
                // TODO
            }
            if (false !== \strpos(',address,article,aside,base,basefont,blockquote,body,caption,center,col,colgroup,dd,details,dialog,dir,div,dl,dt,fieldset,figcaption,figure,footer,form,frame,frameset,h1,h2,h3,h4,h5,h6,head,header,hr,html,iframe,legend,li,link,main,menu,menuitem,nav,noframes,ol,optgroup,option,p,param,search,section,summary,table,tbody,td,tfoot,th,thead,title,tr,track,ul,', ',' . $r[4][1] . ',') && "" === \trim($row)) {
                $rows[] = $r;
                $r = ['p', "", [], 0];
                continue;
            }
            $r[1] .= $row . "\n";
            continue;
        }
        if ('ol' === $r[0]) {
            // <https://spec.commonmark.org/0.31.2#example-306>
            if ("" === \trim($row)) {
                $r[1] .= "\n";
                continue;
            }
            // <https://spec.commonmark.org/0.31.2#of-the-same-type>
            if (($n = \strspn($row, '0123456789')) && \strspn($row, " \t", $n + 1) && $row[$n] === $r[4][2] && $t <= $r[3] + $r[4][0] - 1) {
                $r[1] .= "\x3" . $row . "\n";
                continue;
            }
            // <https://spec.commonmark.org/0.31.2#example-307>
            if ($t >= $r[3] + $r[4][0]) {
                $r[1] .= \str_repeat(' ', $t) . $row . "\n";
                continue;
            }
            $r[1] = \explode("\x3", \rtrim($r[1], "\n") . "\n");
            $rows[] = $r;
            $r = ['p', "", [], 0];
            continue;
        }
        if ('ul' === $r[0]) {
            // <https://spec.commonmark.org/0.31.2#example-306>
            if ("" === \trim($row)) {
                $r[1] .= "\n";
                continue;
            }
            // <https://spec.commonmark.org/0.31.2#of-the-same-type>
            if (false !== \strpos('*+-', $row[0] ?? '%') && \strspn($row, " \t", 1) && $row[0] === $r[4][1] && $t <= $r[3] + $r[4][0] - 1) {
                $r[1] .= "\x3" . $row . "\n";
                continue;
            }
            // <https://spec.commonmark.org/0.31.2#example-307>
            if ($t >= $r[3] + $r[4][0]) {
                $r[1] .= \str_repeat(' ', $t) . $row . "\n";
                continue;
            }
            $r[1] = \explode("\x3", \rtrim($r[1], "\n") . "\n");
            $rows[] = $r;
            $r = ['p', "", [], 0];
            continue;
        }
        // <https://spec.commonmark.org/0.31.2#indented-code-block>
        if ($t >= 4) {
            $r[0] = 'pre';
            $r[1] .= $row . "\n";
            $r[2]['class'] = false;
            if (0 === $r[3]) {
                $r[3] = $t;
            }
            continue;
        }
        if ('<' === ($row[0] ?? '%') && ($name = \substr($row, 1, \strcspn($row, " \t", 1)))) {
            // <https://spec.commonmark.org/0.31.2#html-block>
            if (false !== \strpos(',pre,script,style,textarea,', ',' . $name . ',')) {
                if ("" !== $r[1]) {
                    $rows[] = $r;
                    $r = [false, $row . "\n", [], $t, [1, $name]];
                    continue;
                }
                $r[0] = false;
                $r[1] .= $row . "\n";
                if (0 === $r[3]) {
                    $r[3] = $t;
                }
                $r[4] = [1, $name];
                continue;
            }
            if (false !== \strpos(',address,article,aside,base,basefont,blockquote,body,caption,center,col,colgroup,dd,details,dialog,dir,div,dl,dt,fieldset,figcaption,figure,footer,form,frame,frameset,h1,h2,h3,h4,h5,h6,head,header,hr,html,iframe,legend,li,link,main,menu,menuitem,nav,noframes,ol,optgroup,option,p,param,search,section,summary,table,tbody,td,tfoot,th,thead,title,tr,track,ul,', ',' . \trim($name = \strtolower($name), '/') . ',')) {
                if ("" !== $r[1]) {
                    $rows[] = $r;
                    $r = [false, $row . "\n", [], $t, [6, $name]];
                    continue;
                }
                $r[0] = false;
                $r[1] .= $row . "\n";
                if (0 === $r[3]) {
                    $r[3] = $t;
                }
                $r[4] = [6, $name];
                continue;
            }
            // TODO
        }
        // <https://spec.commonmark.org/0.31.2/#bullet-list>
        if (false !== \strpos('*+-', $row[0] ?? '%') && ($z = \strspn($row, " \t", 1))) {
            $r[0] = 'ul';
            $r[1] .= $row . "\n";
            if (0 === $r[3]) {
                $r[3] = $t;
            }
            $r[4] = [1 + $z, $row[0], ""];
            continue;
        }
        // <https://spec.commonmark.org/0.31.2#ordered-list>
        if (($n = \strspn($row, '0123456789')) && false !== \strpos(').', $row[$n] ?? '%') && ($z = \strspn($row, " \t", $n + 1))) {
            $r[0] = 'ol';
            $r[1] .= $row . "\n";
            // <https://spec.commonmark.org/0.31.2#start-number>
            $r[2]['start'] = $now = (int) \substr($row, 0, $n);
            if (0 === $r[3]) {
                $r[3] = $t;
            }
            $r[4] = [$n + 1 + $z, $now, $row[$n]];
            continue;
        }
        // <https://spec.commonmark.org/0.31.2#paragraph>
        if ("" === \trim($row)) {
            $rows[] = $r;
            $r = ['p', "", [], 0];
            continue;
        }
        $r[1] .= $row . "\n";
        if (0 === $r[3]) {
            $r[3] = $t;
        }
    }
    if ("" !== $r[1]) {
        if ('ol' === $r[0] || 'ul' === $r[0]) {
            $r[1] = \explode("\x3", \rtrim($r[1], "\n") . "\n");
        }
        $rows[] = $r;
    }
    return $rows;
}




foreach ([

    // p
    "asdf asdf asdf asdf\nasdf asdf asdf asdf\nasdf asdf asdf asdf",
    "asdf asdf asdf asdf\n\nasdf asdf asdf asdf\nasdf asdf asdf asdf",
    "asdf asdf asdf asdf\n\nasdf asdf asdf asdf\n\nasdf asdf asdf asdf",
    "asdf asdf asdf asdf\nasdf asdf asdf asdf\n\nasdf asdf asdf asdf",

    // pre
    "    asdf asdf asdf asdf\n\nasdf asdf asdf asdf\n\nasdf asdf asdf asdf",
    "asdf asdf asdf asdf\n\n    asdf asdf asdf asdf\n\nasdf asdf asdf asdf",
    "asdf asdf asdf asdf\n\nasdf asdf asdf asdf\n\n    asdf asdf asdf asdf",

    // ol
    "1. asdf asdf asdf asdf\n\nasdf asdf asdf asdf\n\nasdf asdf asdf asdf",
    "asdf asdf asdf asdf\n\n1. asdf asdf asdf asdf\n\nasdf asdf asdf asdf",
    "asdf asdf asdf asdf\n\nasdf asdf asdf asdf\n\n1. asdf asdf asdf asdf",
    // ol
    "1. asdf asdf asdf asdf\n1. asdf asdf asdf asdf\n\nasdf asdf asdf asdf\n\nasdf asdf asdf asdf",
    "asdf asdf asdf asdf\n\n1. asdf asdf asdf asdf\n1. asdf asdf asdf asdf\n\nasdf asdf asdf asdf",
    "asdf asdf asdf asdf\n\nasdf asdf asdf asdf\n\n1. asdf asdf asdf asdf\n1. asdf asdf asdf asdf",
    // ol
    "1. asdf asdf asdf asdf\n   1. asdf asdf asdf asdf\n\nasdf asdf asdf asdf\n\nasdf asdf asdf asdf",
    "asdf asdf asdf asdf\n\n1. asdf asdf asdf asdf\n   1. asdf asdf asdf asdf\n\nasdf asdf asdf asdf",
    "asdf asdf asdf asdf\n\nasdf asdf asdf asdf\n\n1. asdf asdf asdf asdf\n   1. asdf asdf asdf asdf",

    // ul
    "* asdf asdf asdf asdf\n\nasdf asdf asdf asdf\n\nasdf asdf asdf asdf",
    "asdf asdf asdf asdf\n\n* asdf asdf asdf asdf\n\nasdf asdf asdf asdf",
    "asdf asdf asdf asdf\n\nasdf asdf asdf asdf\n\n* asdf asdf asdf asdf",
    // ul
    "* asdf asdf asdf asdf\n* asdf asdf asdf asdf\n\nasdf asdf asdf asdf\n\nasdf asdf asdf asdf",
    "asdf asdf asdf asdf\n\n* asdf asdf asdf asdf\n* asdf asdf asdf asdf\n\nasdf asdf asdf asdf",
    "asdf asdf asdf asdf\n\nasdf asdf asdf asdf\n\n* asdf asdf asdf asdf\n* asdf asdf asdf asdf",
    // ul
    "* asdf asdf asdf asdf\n   * asdf asdf asdf asdf\n\nasdf asdf asdf asdf\n\nasdf asdf asdf asdf",
    "asdf asdf asdf asdf\n\n* asdf asdf asdf asdf\n   * asdf asdf asdf asdf\n\nasdf asdf asdf asdf",
    "asdf asdf asdf asdf\n\nasdf asdf asdf asdf\n\n* asdf asdf asdf asdf\n   * asdf asdf asdf asdf",

    // "raw"
    "<div asdf asdf asdf\nasdf asdf asdf asdf\nasdf asdf asdf asdf",
    "asdf asdf asdf asdf\n<div asdf asdf asdf\nasdf asdf asdf asdf",
    "asdf asdf asdf asdf\nasdf asdf asdf asdf\n<div asdf asdf asdf",
    // "raw"
    "<div asdf asdf asdf\n\nasdf asdf asdf asdf\nasdf asdf asdf asdf",
    "<div asdf asdf asdf\nasdf asdf asdf asdf\n\nasdf asdf asdf asdf",

] as $text) {
    echo '<pre style="border:2px solid #f00;font:normal normal 12px/1.25 monospace;overflow:auto;padding:0 0.25em;">';
    echo \htmlspecialchars($text);
    echo '</pre>';
    echo '<pre style="border:2px solid #00f;font:normal normal 12px/1.25 monospace;overflow:auto;padding:0 0.25em;">';
    echo \htmlspecialchars(\json_encode(rows($text), \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE));
    echo '</pre>';
    echo '<hr>';
}