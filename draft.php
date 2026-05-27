<?php namespace x\markdown;

function from(?string $value, $block = true): ?string {}

function n($text) {
    if ($t = \strspn($text, ' ')) {
        $text = \substr($text, $t > 4 ? 4 : $t);
    }
    return [$text, $t];
}

function row($text, $lot) {}

function rows($text, $lot) {
    static $nodes_1 = [
        'pre' => 1,
        'script' => 1,
        'style' => 1,
        'textarea' => 1
    ];
    static $nodes_6 = [
        'address' => 1,
        'article' => 1,
        'aside' => 1,
        'base' => 1,
        'basefont' => 1,
        'blockquote' => 1,
        'body' => 1,
        'caption' => 1,
        'center' => 1,
        'col' => 1,
        'colgroup' => 1,
        'dd' => 1,
        'details' => 1,
        'dialog' => 1,
        'dir' => 1,
        'div' => 1,
        'dl' => 1,
        'dt' => 1,
        'fieldset' => 1,
        'figcaption' => 1,
        'figure' => 1,
        'footer' => 1,
        'form' => 1,
        'frame' => 1,
        'frameset' => 1,
        'h1' => 1,
        'h2' => 1,
        'h3' => 1,
        'h4' => 1,
        'h5' => 1,
        'h6' => 1,
        'head' => 1,
        'header' => 1,
        'hr' => 1,
        'html' => 1,
        'iframe' => 1,
        'legend' => 1,
        'li' => 1,
        'link' => 1,
        'main' => 1,
        'menu' => 1,
        'menuitem' => 1,
        'nav' => 1,
        'noframes' => 1,
        'ol' => 1,
        'optgroup' => 1,
        'option' => 1,
        'p' => 1,
        'param' => 1,
        'search' => 1,
        'section' => 1,
        'summary' => 1,
        'table' => 1,
        'tbody' => 1,
        'td' => 1,
        'tfoot' => 1,
        'th' => 1,
        'thead' => 1,
        'title' => 1,
        'tr' => 1,
        'track' => 1,
        'ul' => 1
    ];
    $r = ['p', "", [], 0];
    $raws = \explode("\n", \rtrim(\strtr($text, [
        "\r\n" => "\n",
        "\r" => "\n"
    ]), "\n"));
    $rows = [];
    foreach ($raws as $raw) {
        [$row, $t] = n($raw);
        if (false === $r[0]) {
            // HTML block type 1
            if (isset($nodes_1[$r[4][1]]) && false !== \stripos($row, '</' . $r[4][1] . '>')) {
                $r[1] .= $row . "\n";
                $rows[] = $r;
                $r = ['p', "", [], 0];
                continue;
            }
            // HTML block type 2, 3, 4, and 5
            if (
                2 === $r[4][0] && false !== \strpos($row, '-->') ||
                3 === $r[4][0] && false !== \strpos($row, '?>') ||
                4 === $r[4][0] && false !== \strpos($row, '>') ||
                5 === $r[4][0] && false !== \strpos($row, ']]>')
            ) {
                $r[1] .= $row . "\n";
                $rows[] = $r;
                $r = ['p', "", [], 0];
                continue;
            }
            // HTML block type 6
            if (isset($nodes_6[$r[4][1]]) && "" === \trim($row)) {
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
        // <https://spec.commonmark.org/0.31.2#html-block>
        if ('<' === ($row[0] ?? '%') && ($node = \substr($row, 1, \strcspn($row, " \t>", 1)))) {
            if ('!' === ($node[0] ?? '%') && '>' !== ($node[1] ?? '%')) {
                if ("" !== $r[1]) {
                    // All type(s) of HTML block(s) except type 7 may interrupt a paragraph
                    $rows[] = $r;
                }
                if ('--' === \substr($node, 1, 2)) {
                    $r = [false, $row . "\n", [], $t, [2, null]];
                    if (false !== \strpos($row, '-->')) {
                        // Ends on its own line
                        $rows[] = $r;
                        $r = ['p', "", [], 0];
                    }
                    continue;
                }
                if ('[CDATA[' === \substr($node, 1, 7)) {
                    $r = [false, $row . "\n", [], $t, [5, null]];
                    if (false !== \strpos($row, ']]>')) {
                        // Ends on its own line
                        $rows[] = $r;
                        $r = ['p', "", [], 0];
                    }
                    continue;
                }
                $r = [false, $row . "\n", [], $t, [4, null]];
                if (false !== \strpos($row, '>')) {
                    // Ends on its own line
                    $rows[] = $r;
                    $r = ['p', "", [], 0];
                }
                continue;
            }
            if ('?' === ($node[0] ?? '%') && '>' !== ($node[1] ?? '%')) {
                if ("" !== $r[1]) {
                    // All type(s) of HTML block(s) except type 7 may interrupt a paragraph
                    $rows[] = $r;
                }
                $r = [false, $row . "\n", [], $t, [3, null]];
                if (false !== \strpos($row, '?>')) {
                    // Ends on its own line
                    $rows[] = $r;
                    $r = ['p', "", [], 0];
                }
                continue;
            }
            // The initial tag doesn’t need to be a valid tag, as long as it starts like one
            $node = \strtolower($node);
            if (isset($nodes_1[$node])) {
                if ("" !== $r[1]) {
                    // All type(s) of HTML block(s) except type 7 may interrupt a paragraph
                    $rows[] = $r;
                }
                $r = [false, $row . "\n", [], $t, [1, $node]];
                if (false !== \stripos($row, '</' . $node . '>')) {
                    // Ends on its own line
                    $rows[] = $r;
                    $r = ['p', "", [], 0];
                }
                continue;
            }
            // HTML block(s) type 6 does not care whether it is an open or close tag
            if (isset($nodes_6[$node_6 = \trim($node, '/')])) {
                if ("" !== $r[1]) {
                    // All type(s) of HTML block(s) except type 7 may interrupt a paragraph
                    $rows[] = $r;
                }
                $r = [false, $row . "\n", [], $t, [6, $node_6]];
                continue;
            }
            $r[1] .= $row . "\n";
            if (0 === $r[3]) {
                $r[3] = $t;
            }
            continue;
        }
        // <https://spec.commonmark.org/0.31.2#example-80>
        if ('-' === ($row[0] ?? '%') && \strspn($row, '-') === \strlen($row) && "" !== $r[1]) {
            $r[0] = 'h2';
            $r[4] = [2, '-'];
            $rows[] = $r;
            $r = ['p', "", [], 0];
            continue;
        }
        if ('=' === ($row[0] ?? '%') && \strspn($row, '=') === \strlen($row) && "" !== $r[1]) {
            $r[0] = 'h1';
            $r[4] = [1, '='];
            $rows[] = $r;
            $r = ['p', "", [], 0];
            continue;
        }
        // <https://spec.commonmark.org/0.31.2#thematic-break>
        // NOTE: This thematic break parser must come after the `<h2>`’s “setext” heading parser
        // because the `-` character is used as the “setext” marker for `<h2>` as well
        if (($test = \strtr($row, ["\t" => "", ' ' => ""])) && (
            '*' === $test[0] ||
            '-' === $test[0] ||
            '_' === $test[0]
        ) && \strspn($test, $test[0]) === \strlen($test)) {
            if ("" !== $r[1]) {
                $rows[] = $r;
            }
            $rows[] = ['hr', false, [], $t, [$test[0]]];
            $r = ['p', "", [], 0];
            continue;
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
    $lot[0] = $lot[0] ?? []; // Reference(s)
    $lot[1] = $lot[1] ?? []; // Abbreviation(s)
    $lot[2] = $lot[2] ?? []; // Note(s)
    return [$rows, $lot];
}




foreach ([

    // hr
    "***\nasdf asdf asdf asdf\nasdf asdf asdf asdf",
    "asdf asdf asdf asdf\n***\nasdf asdf asdf asdf",
    "asdf asdf asdf asdf\nasdf asdf asdf asdf\n***",

    // hr
    "---\nasdf asdf asdf asdf\nasdf asdf asdf asdf",
    "asdf asdf asdf asdf\n---\nasdf asdf asdf asdf",
    "asdf asdf asdf asdf\nasdf asdf asdf asdf\n---",

    // hr
    "___\nasdf asdf asdf asdf\nasdf asdf asdf asdf",
    "asdf asdf asdf asdf\n___\nasdf asdf asdf asdf",
    "asdf asdf asdf asdf\nasdf asdf asdf asdf\n___",

    // h2
    "-\nasdf asdf asdf asdf\nasdf asdf asdf asdf",
    "asdf asdf asdf asdf\n-\nasdf asdf asdf asdf",
    "asdf asdf asdf asdf\nasdf asdf asdf asdf\n-",
    // h2
    "--------------------\nasdf asdf asdf asdf\nasdf asdf asdf asdf",
    "asdf asdf asdf asdf\n--------------------\nasdf asdf asdf asdf",
    "asdf asdf asdf asdf\nasdf asdf asdf asdf\n--------------------",

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

    // raw 1
    "<script asdf asdf\nasdf asdf asdf asdf\nasdf asdf asdf asdf",
    "asdf asdf asdf asdf\n<script asdf asdf\nasdf asdf asdf asdf",
    "asdf asdf asdf asdf\nasdf asdf asdf asdf\n<script asdf asdf",
    // raw 1
    "<script asdf asdf\n\nasdf asdf asdf asdf\n\nasdf asdf asdf asdf\n</script>asdf asdf asdf",
    "<script asdf asdf\n\nasdf asdf asdf asdf\n\nasdf asdf asdf asdf\n</script>\nasdf asdf asdf asdf",
    "<script asdf asdf\n\nasdf asdf asdf asdf\n\nasdf asdf asdf asdf\n</script>\n\nasdf asdf asdf asdf",
    // raw 1
    "<script asdf </script> asdf asdf\nasdf asdf asdf asdf",
    "<script asdf asdf </script>\nasdf asdf asdf asdf",
    // raw 2
    "<!-- asdf asdf asdf\nasdf asdf asdf asdf\nasdf asdf asdf asdf",
    "asdf asdf asdf asdf\n<!-- asdf asdf asdf\nasdf asdf asdf asdf",
    "asdf asdf asdf asdf\nasdf asdf asdf asdf\n<!-- asdf asdf asdf",
    // raw 2
    "<!-- asdf asdf asdf\n\nasdf asdf asdf -->\nasdf asdf asdf asdf",
    "<!-- asdf asdf asdf\n\nasdf asdf asdf -->\n\nasdf asdf asdf asdf",
    // raw 3
    "<? asdf asdf asdf\nasdf asdf asdf asdf\nasdf asdf asdf asdf",
    "asdf asdf asdf asdf\n<? asdf asdf asdf\nasdf asdf asdf asdf",
    "asdf asdf asdf asdf\nasdf asdf asdf asdf\n<? asdf asdf asdf",
    // raw 3
    "<? asdf asdf asdf\n\nasdf asdf asdf ?>\nasdf asdf asdf asdf",
    "<? asdf asdf asdf\n\nasdf asdf asdf ?>\n\nasdf asdf asdf asdf",
    // raw 6
    "<div asdf asdf asdf\nasdf asdf asdf asdf\nasdf asdf asdf asdf",
    "asdf asdf asdf asdf\n<div asdf asdf asdf\nasdf asdf asdf asdf",
    "asdf asdf asdf asdf\nasdf asdf asdf asdf\n<div asdf asdf asdf",
    // raw 6
    "<div asdf asdf asdf\n\nasdf asdf asdf asdf\nasdf asdf asdf asdf",
    "<div asdf asdf asdf\nasdf asdf asdf asdf\n\nasdf asdf asdf asdf",

] as $text) {
    echo '<pre style="border:2px solid #f00;font:normal normal 12px/1.25 monospace;overflow:auto;padding:0 0.25em;">';
    echo \htmlspecialchars($text);
    echo '</pre>';
    echo '<pre style="border:2px solid #00f;font:normal normal 12px/1.25 monospace;overflow:auto;padding:0 0.25em;">';
    echo \htmlspecialchars(\json_encode(rows($text, [])[0], \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE));
    echo '</pre>';
    echo '<hr>';
}