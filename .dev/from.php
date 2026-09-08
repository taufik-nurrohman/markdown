<?php

namespace x\markdown {
    function from(?string $value, $state = []): ?string {
        if ("" === $value) {
            return null;
        }
        if (!\is_array($state)) {
            $state = ['block' => !!$state];
        }
        $state = \array_replace_recursive([
            'block' => true,
            'tab' => false,
            'with' => []
        ], $state);
        $block = !empty($state['block']);
        $with = (array) ($state['with'] ?? []);
        if (!$block) {
            $lot = [];
            $row = from\row($value, $lot, from\deep, \strspn($value, from\c3), \strlen($value));
            $row[] = $state = ['tab' => false] + $state;
            if ($with) foreach ($with as $w) {
                $row[0] = $w(...$row);
            }
            $s = from\tags($row[0], $state);
            return "" !== $s ? $s : null;
        }
        $lot = [];
        $rows = from\rows($value, $lot, from\deep, 0, \strlen($value));
        $rows[] = $state;
        if ($with) foreach ($with as $w) {
            $rows[0] = $w(...$rows);
        }
        $s = from\tags($rows[0], $state);
        return "" !== $s ? $s : null;
    }
}

namespace x\markdown\from {
    const b1 = ['pre' => 1, 'script' => 1, 'style' => 1, 'textarea' => 1];
    const b6 = [
        'address' => 1, 'article' => 1, 'aside' => 1, 'base' => 1, 'basefont' => 1, 'blockquote' => 1, 'body' => 1,
        'caption' => 1, 'center' => 1, 'col' => 1, 'colgroup' => 1, 'dd' => 1, 'details' => 1, 'dialog' => 1,
        'dir' => 1, 'div' => 1, 'dl' => 1, 'dt' => 1, 'fieldset' => 1, 'figcaption' => 1, 'figure' => 1, 'footer' => 1,
        'form' => 1, 'frame' => 1, 'frameset' => 1, 'h1' => 1, 'h2' => 1, 'h3' => 1, 'h4' => 1, 'h5' => 1, 'h6' => 1,
        'head' => 1, 'header' => 1, 'hr' => 1, 'html' => 1, 'iframe' => 1, 'legend' => 1, 'li' => 1, 'link' => 1,
        'main' => 1, 'menu' => 1, 'menuitem' => 1, 'nav' => 1, 'noframes' => 1, 'ol' => 1, 'optgroup' => 1,
        'option' => 1, 'p' => 1, 'param' => 1, 'search' => 1, 'section' => 1, 'summary' => 1, 'table' => 1,
        'tbody' => 1, 'td' => 1, 'tfoot' => 1, 'th' => 1, 'thead' => 1, 'title' => 1, 'tr' => 1, 'track' => 1, 'ul' => 1
    ];
    // <https://spec.commonmark.org/0.31.2#line-ending>
    // <https://spec.commonmark.org/0.31.2#space>
    // <https://spec.commonmark.org/0.31.2#tab>
    const c1 = " \t";
    const c2 = "\r\n";
    const c3 = c1 . c2;
    const c4 = '0123456789'; // Digit
    const c5 = 'ABCDEF';
    const c6 = 'abcdef';
    const c7 = c4 . c5 . c6; // Hex
    const c8 = c5 . 'GHIJKLMNOPQRSTUVWXYZ';
    const c9 = c6 . 'ghijklmnopqrstuvwxyz';
    const c10 = c8 . c9; // Alpha
    const c11 = c4 . c10 . '-'; // Alpha + Digit + `-`
    // <https://spec.commonmark.org/0.31.2#attribute-name>
    const c12 = c10 . ':_';
    const c13 = c11 . ':_.';
    // <https://www.rfc-editor.org/rfc/rfc3986.html#section-2.2>
    const c14 = '!#$&()*+,/:;=?@[]' . "'";
    // <https://www.rfc-editor.org/rfc/rfc3986.html#section-2.3>
    const c15 = c11 . '._~';
    // <https://spec.commonmark.org/0.31.2#ascii-punctuation-character>
    const c16 = c14 . '"%-.<>^_`{|}~' . "\\";
    // <https://spec.commonmark.org/0.31.2#ascii-control-character>
    const c17 = "\x00\x01\x02\x03\x04\x05\x06\x07\x08\x09\x0a\x0b\x0c\x0d\x0e\x0f\x10\x11\x12\x13\x14\x15\x16\x17\x18\x19\x1a\x1b\x1c\x1d\x1e\x1f\x7f";
    // <https://en.wikipedia.org/wiki/Latin_script_in_Unicode>
    const c18 = c4 . c10 . '_ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûüýþÿĀāĂăĄąĆćĈĉĊċČčĎďĐđĒēĔĕĖėĘęĚěĜĝĞğĠġĢģĤĥĦħĨĩĪīĬĭĮįİıĲĳĴĵĶķĹĺĻļĽľĿŀŁłŃńŅņŇňŊŋŌōŎŏŐőŒœŔŕŖŗŘřŚśŜŝŞşŠšŢţŤťŦŧŨũŪūŬŭŮůŰűŲųŴŵŶŷŸŹźŻżŽž';
    const deep = 25;
    const x1 = "\x1"; // SOH
    const x2 = "\x2"; // STX
    const x3 = "\x3"; // ETX
    // Currently, there is no official attribute syntax specification in CommonMark except for the raw HTML attribute.
    // To make it as close as possible to the CommonMark specification or to prepare for the possibility of such
    // specification in the future, I will make the attribute syntax rule(s) as close as possible to the raw HTML
    // attribute specification.
    function a(string $value, int $i, int $limit, $raw = false, string $f = "") {
        if ($i >= $limit) {
            return [];
        }
        if (!$raw && '{' !== $value[$i]) {
            return [];
        }
        $m = $raw ? 0 : 1;
        $n = $m + \strspn($value, c3, $m + $i, $limit - ($m + $i));
        $not = c3 . '"#.<=>`{}' . "'";
        $r = [];
        while ($i + $n < $limit) {
            $c = $value[$i + $n];
            if (!$raw && '}' === $c) {
                if (\is_array($a = $r['class'] ?? 0)) {
                    \ksort($a);
                    $r['class'] = \implode(' ', \array_keys($a));
                }
                return [$r, $n + 1];
            }
            if ('#' === $c) {
                ++$n; // Move past `#`
                $eat = \strcspn($value, $not, $i + $n, $limit - ($i + $n));
                if (isset($r['id'])) {
                    $n += $eat;
                    continue;
                }
                if ("" === ($s = \substr($value, $i + $n, $eat))) {
                    return [];
                }
                $r['id'] = $s;
                $n += $eat;
                continue;
            }
            if ('.' === $c) {
                ++$n; // Move past `.`
                $eat = \strcspn($value, $not, $i + $n, $limit - ($i + $n));
                if (\is_string($r['class'] ?? 0)) {
                    $n += $eat;
                    continue;
                }
                if ("" === ($s = \substr($value, $i + $n, $eat))) {
                    return [];
                }
                $r['class'][$s] = 1;
                $n += $eat;
                continue;
            }
            // <https://spec.commonmark.org/0.31.2#attribute-name>
            if ($eat = \strspn($value, c12, $i + $n, $limit - ($i + $n))) {
                $eat += \strspn($value, c13, $eat + $i + $n);
                $exist = isset($r[$k = \substr($value, $i + $n, $eat)]);
                $n += $eat;
                // <https://spec.commonmark.org/0.31.2#attribute-value-specification>
                $n += \strspn($value, c3, $i + $n, $limit - ($i + $n));
                if ('=' === ($value[$i + $n] ?? 0)) {
                    ++$n; // Move past `=`
                    $exist || ($r[$k] = "");
                    $n += \strspn($value, c3, $i + $n, $limit - ($i + $n));
                    $q = ($value[$i + $n] ?? 0);
                    // <https://spec.commonmark.org/0.31.2#attribute-value>
                    // <https://spec.commonmark.org/0.31.2#double-quoted-attribute-value>
                    // <https://spec.commonmark.org/0.31.2#single-quoted-attribute-value>
                    if ('"' === $q || "'" === $q) {
                        $eat = ++$n;
                        if (false === ($m = \strpos($value, $q, $eat + $i))) {
                            return [];
                        }
                        $exist || ($r[$k] = \substr($value, $eat + $i, $m - ($eat + $i)));
                        $n = $m - $i + 1;
                        continue;
                    }
                    $n += \strspn($value, c3, $i + $n, $limit - ($i + $n));
                    // <https://spec.commonmark.org/0.31.2#unquoted-attribute-value>
                    if ($eat = \strcspn($value, $not, $i + $n)) {
                        $exist || ($r[$k] = \substr($value, $i + $n, $eat));
                        $n += $eat;
                    }
                    continue;
                }
                if ($raw && "" === $f) {
                    return [];
                }
                // Boolean attribute(s)
                if ($raw) {
                    $r['class'][\sprintf($f, $k)] = 1;
                } else if (!$exist) {
                    $r[$k] = true;
                }
                continue;
            }
            if ($eat = \strcspn($value, c3 . ($raw ? "" : '}'), $i + $n)) {
                // If there is an invalid attribute name found in the wrapped attribute syntax, or in the raw attribute
                // syntax where no class format is provided, the entire attribute syntax must be marked as invalid.
                if (!$raw || "" === $f) {
                    return [];
                }
                // If a class format is provided, treat it as part of the class name and put it in the class queue.
                if (!\is_string($r['class'] ?? 0)) {
                    $r['class'][\sprintf($f, \substr($value, $i + $n, $eat))] = 1;
                }
                $n += $eat;
            }
            $n += \strspn($value, c3, $i + $n, $limit - ($i + $n));
        }
        if (!$raw) {
            return [];
        }
        if (\is_array($a = $r['class'] ?? 0)) {
            \ksort($a);
            $r['class'] = \implode(' ', \array_keys($a));
        }
        return [$r, $n];
    }
    function a6k(string $value, int $i, int $limit) {
        if ('<' !== ($value[$i] ?? 0) || false === ($end = \strpos($value, '>', $i + 2))) {
            return [];
        }
        // <https://spec.commonmark.org/0.31.2#uri-autolink>
        if (false !== \strpos($value, ':', $i + 3) && ($m = \strspn($value, c10, $n = $i + 1))) {
            $m += \strspn($value, c11 . '+.', $m + $n);
            if ($m >= 2 && $m <= 32) { // <https://spec.commonmark.org/0.31.2#scheme>
                if (':' === ($value[$m + $n] ?? 0)) {
                    $m += \strcspn($value, c17 . ' <>', $m + $n + 1) + 1;
                    if ($end === $m + $n) {
                        return [['a', h($u = \substr($value, $n, $m)), ['href' => u($u)], [5]], $m + 2];
                    }
                }
            }
        }
        // <https://spec.commonmark.org/0.31.2#email-autolink>
        if (false !== \strpos($value, '@', $i + 2) && ($m = \strspn($value, c11 . '!#$%&*+./=?^`{|}~' . "'", $n = $i + 1))) {
            if ('@' === ($value[$m + $n] ?? 0)) {
                $m += \strspn($value, c11 . '.', $m + $n + 1) + 1;
                if ($end === $m + $n) {
                    return [['a', h($u = \substr($value, $n, $m)), ['href' => u('mailto:' . $u)], [6]], $m + 2];
                }
            }
        }
        return [];
    }
    // <https://spec.commonmark.org/0.31.2#image-description>
    function alt($row) {
        if (\is_string($row)) {
            return \strtr($row, ['"' => '&quot;']);
        }
        $s = "";
        foreach ($row as $r) {
            if (\is_array($r)) {
                if ('img' === $r[0]) {
                    $s .= $r[2]['alt'] ?? "";
                    continue;
                }
                if (false === $r[0] && '&' !== ($r[1][0] ?? 0)) {
                    continue; // Strip raw HTML element(s)
                }
                if (\is_array($r[1])) {
                    $s .= alt($r[1]);
                    continue;
                }
                $s .= $r[1];
                continue;
            }
            $s .= $r;
        }
        return \htmlspecialchars_decode($s);
    }
    // <https://spec.commonmark.org/0.31.2#code-span>
    function c2e(string $value, int $i, int $limit) {
        $c = '`';
        if ($i + 1 >= $limit || "" === $value || $c !== ($value[$i] ?? 0)) {
            return [];
        }
        $n = \strspn($value, $c, $i, $limit - $i);
        if ($i + $n >= $limit) {
            return [];
        }
        $eat = $i + $n;
        while (false !== ($eat = \strpos($value, $c, $eat))) {
            if ($eat >= $limit) {
                break;
            }
            $w = \strspn($value, $c, $eat, $limit - $eat);
            if ($n === $w && $eat + $w <= $limit) {
                $text = \substr($value, $i + $n, $eat - ($i + $n));
                // Line break(s) are converted to space(s)
                $text = \strtr($text, ["\n" => ' ', "\r\n" => ' ', "\r" => ' ']);
                // If the resulting string both begins and ends with a space character, but does not consist entirely of
                // space character(s), a single space character is removed from the front and back.
                if (\strlen($text) > 1 && ' ' === $text[0] && ' ' === \substr($text, -1) && "" !== \trim($text, ' ')) {
                    $text = \substr($text, 1, -1);
                }
                return [$text, $eat + $n - $i];
            }
            $eat += $w;
        }
        return [];
    }
    function d(string $value, int $i, int $limit) {
        if ($i >= $limit || false === \strpos(c1, $value[$i])) {
            return [0, 0];
        }
        $d = $n = 0;
        while ($i + $n < $limit) {
            $c = $value[$i + $n];
            if ("\t" === $c) {
                $d += 4 - ($d % 4);
                ++$n;
                continue;
            }
            if (' ' === $c) {
                $d += 1;
                ++$n;
                continue;
            }
            break; // Stop at the first character that is not a white-space
        }
        return [$d, $n];
    }
    // <https://spec.commonmark.org/0.31.2#process-emphasis>
    function e(array $row, array $stack, ?int $last) {
        // Find the bottom (start) of the stack
        if (null !== $last) while (null !== $stack[$last][2][1]) {
            $last = $stack[$last][2][1];
        }
        // Process closing delimiter from left ro right
        $right = $last;
        while (null !== $right) {
            // Not an active closing delimiter of emphasis?
            if (0 === $stack[$right][1][0] || ('*' !== $stack[$right][0] && '_' !== $stack[$right][0]) || !$stack[$right][1][2]) {
                $right = $stack[$right][2][2];
                continue;
            }
            $valid = false;
            // Look backward for a valid opening delimiter
            for ($left = $stack[$right][2][1]; null !== $left; $left = $stack[$left][2][1]) {
                // Must be active, match the character, and can open
                if (0 === $stack[$left][1][0] || $stack[$left][0] !== $stack[$right][0] || !$stack[$left][1][1]) {
                    continue;
                }
                $end_n = $stack[$right][1][0];
                $start_n = $stack[$left][1][0];
                // The “rule of 3” for multiple delimiter(s)
                if (($stack[$left][1][2] || $stack[$right][1][1]) && 0 === ($start_n + $end_n) % 3 && 0 !== $start_n % 3 && 0 !== $end_n % 3) {
                    continue;
                }
                $valid = true;
                // Determine whether to use 1 or 2 delimiter(s)
                $n = ($start_n >= 2 && $end_n >= 2) ? 2 : 1;
                $stack[$left][1][0] -= $n;
                $stack[$right][1][0] -= $n;
                $k = 2 === $n ? 'strong' : 'em';
                $end_k = $stack[$right][2][0];
                $start_k = $stack[$left][2][0];
                $c = $stack[$left][0];
                $chunk = [];
                for ($i = $start_k + 1; $i < $end_k; ++$i) {
                    if ("" !== $row[$i]) {
                        $chunk[] = $row[$i];
                    }
                    $row[$i] = "";
                }
                // Put the excess delimiter back as plain text in case it still exists
                $row[$end_k] = ($x = $end_n - $n) ? \str_repeat($c, $x) : "";
                $row[$start_k] = ($x = $start_n - $n) ? \str_repeat($c, $x) : "";
                // Put the newly created AST node back into the tree. Since `strspn()` groups runs of the same
                // delimiter, the value of `$end_k` is always greater than the value of `$start_k + 1`. Thus,
                // `$start_k + 1` is always a safe, empty slot in which to store the new AST sub-tree.
                if ($start_k + 1 < $end_k) {
                    $row[$start_k + 1] = [$k, y($chunk), []];
                } else {
                    // $row[$start_k] = [$k, y($chunk), []];
                }
                // Sever link(s) for all delimiter(s) physically between `$left` and `$right`
                $stack[$left][2][2] = $right;
                $stack[$right][2][1] = $left;
                // Check for opener and closer exhaustion, then remove it from the stack if it is `0`
                if (0 === $stack[$left][1][0]) {
                    if (null !== ($prev = $stack[$left][2][1])) {
                        $stack[$prev][2][2] = $right;
                    }
                    $stack[$right][2][1] = $prev;
                }
                if (0 === $stack[$right][1][0]) {
                    $next = $stack[$right][2][2];
                    $prev = $stack[$right][2][1];
                    if (null !== $prev) {
                        $stack[$prev][2][2] = $next;
                    }
                    if (null !== $next) {
                        $stack[$next][2][1] = $prev;
                    }
                    // Move pointer to the next delimiter for the outer loop
                    $right = $next;
                }
                // Break the backward search and continue with the outer loop
                break;
            }
            // If no match was found, we must advance `$right` safely. If a match was found but `$right` wasn’t
            // exhausted, we let it stay on the same `$right` so it can attempt to close additional tag(s).
            if (!$valid) {
                $right = $stack[$right][2][2];
            }
        }
        return $row;
    }
    // <https://spec.commonmark.org/0.31.2#matches>
    function f(string $text) {
        return \defined("\\MB_CASE_FOLD") ? \mb_convert_case($text, \MB_CASE_FOLD, 'UTF-8') : \strtolower($text);
    }
    function h(string $text) {
        return \htmlspecialchars($text, \ENT_HTML5 | \ENT_NOQUOTES, 'UTF-8', false);
    }
    // <https://spec.commonmark.org/0.31.2#link-label>
    function k(string $value, int $i, int $limit, int $deep = 0, $void = false) {
        if ($i >= $limit || '[' !== $value[$i]) {
            return [];
        }
        $n = 1;
        $s = "";
        while ($i + $n < $limit) {
            $c = $value[$i + $n];
            if ("\\" === $c && $i + $n + 1 < $limit && false !== \strpos(c16, $value[$i + $n + 1])) {
                $s .= $value[$i + ($n += 2)];
                continue;
            }
            if ($r = r($value, $i + $n, $limit)) {
                $n += $r;
                $n += \strspn($value, c1, $i + $n);
                // A blank line invalidates the current label
                if (r($value, $i + $n, $limit)) {
                    return [];
                }
                $s .= $c;
                continue;
            }
            if ('[' === $c) {
                if ($deep < 1 || !($k = k($value, $i + $n, $limit, $deep - 1))) {
                    return [];
                }
                $s .= \substr($value, $i + $n, $k[1]);
                $n += $k[1];
                continue;
            }
            if (']' === $c) {
                $max = \strlen($s);
                if (!$void || $max > 0) {
                    // Link label can have at most 999 character(s) inside the `[` and `]` character(s). It originally
                    // considers line(s) to be composed of character(s) rather than byte(s), but this one counts byte(s)
                    // for simplicity.
                    if (\strspn($s, c3) === $max || $max > 999) {
                        return [];
                    }
                }
                return [$s = n($s, ' '), $n + 1];
            }
            $s .= $c;
            ++$n;
        }
        return [];
    }
    function l3n(string $s, $u = false) {
        $a = $u ? 65 : 97;
        $n = 0;
        for ($i = 0, $j = \strlen($s); $i < $j; ++$i) {
            $c = \ord($s[$i]);
            if ($c < $a || $c > $a + 25) {
                return -1;
            }
            $n = $n * 26 + $c - $a + 1;
        }
        return $n;
    }
    function m(string $value, int $i, int $limit) {
        return [$n = \strcspn($value, c2, $i, $limit - $i), r($value, $i + $n, $limit)];
    }
    function n(string $text, $join = false) {
        $i = 0;
        $limit = \strlen($text);
        $r = [];
        while ($i < $limit) {
            $i += \strspn($text, c3, $i);
            if ($i >= $limit) {
                break;
            }
            $r[] = \substr($text, $i, $w = \strcspn($text, c3, $i));
            $i += $w;
        }
        return false !== $join ? \implode($join, $r) : $r;
    }
    function n2e(string $value, int $i, int $limit) {
        if ($i + 2 >= $limit || "" === $value || '<' !== ($value[$i] ?? 0)) {
            return [];
        }
        // <https://spec.commonmark.org/0.31.2#processing-instruction>
        if ('?' === ($value[$i + 1] ?? 0) && false !== ($n = \strpos($value, '?>', $i + 2))) {
            return [\substr($value, $i, $n += 2 - $i), $n, 3];
        }
        if ('!' === ($value[$i + 1] ?? 0)) {
            // <https://spec.commonmark.org/0.31.2#html-comment>
            if (0 === \substr_compare($value, '--', $i + 2, 2) && false !== ($n = \strpos($value, '-->', $i + 2))) {
                return [\substr($value, $i, $n += 3 - $i), $n, 2];
            }
            // <https://spec.commonmark.org/0.31.2#cdata-section>
            if (0 === \substr_compare($value, '[CDATA[', $i + 2, 7) && false !== ($n = \strpos($value, ']]>', $i + 2))) {
                return [\substr($value, $i, $n += 3 - $i), $n, 5];
            }
            // <https://spec.commonmark.org/0.31.2#declaration>
            if (\strspn($value, c10, $i + 2, 1) && false !== ($n = \strpos($value, '>', $i + 3))) {
                return [\substr($value, $i, $n += 1 - $i), $n, 4];
            }
            return [];
        }
        // <https://spec.commonmark.org/0.31.2#html-tag>
        // <https://spec.commonmark.org/0.31.2#closing-tag>
        if ($end = '/' === $value[$n = $i + 1]) {
            ++$n;
        }
        // <https://spec.commonmark.org/0.31.2#tag-name>
        if ($m = \strspn($value, c10, $n)) {
            $n += \strspn($value, c11, $m + $n) + $m;
            // <https://spec.commonmark.org/0.31.2#closing-tag>
            if ($end) {
                $n += \strspn($value, c3, $n);
            // <https://spec.commonmark.org/0.31.2#open-tag>
            } else {
                if ($n < $limit && '>' !== $value[$n]) {
                    // <https://spec.commonmark.org/0.31.2#attribute>
                    while ($n < $limit) {
                        if (!$m = \strspn($value, c3, $n)) {
                            break;
                        }
                        // <https://spec.commonmark.org/0.31.2#attribute-name>
                        if (!$m = \strspn($value, c12, $n += $m)) {
                            break;
                        }
                        $n += \strspn($value, c13, $n + $m) + $m;
                        // <https://spec.commonmark.org/0.31.2#attribute-value-specification>
                        $n += \strspn($value, c3, $n);
                        if ('=' === ($value[$n] ?? 0)) {
                            $q = $value[$n += \strspn($value, c3, ++$n)] ?? 0;
                            // <https://spec.commonmark.org/0.31.2#double-quoted-attribute-value>
                            // <https://spec.commonmark.org/0.31.2#single-quoted-attribute-value>
                            if ('"' === $q || "'" === $q) {
                                if (false === ($m = \strpos($value, $q, ++$n))) {
                                    break;
                                }
                                $n = $m + 1;
                                continue;
                            }
                            // <https://spec.commonmark.org/0.31.2#unquoted-attribute-value>
                            $n += \strcspn($value, c3 . '"<=>`' . "'", $n);
                            continue;
                        }
                    }
                }
                if ('/' === ($value[$n] ?? 0)) {
                    ++$n;
                }
            }
        }
        if ('>' === ($value[$n] ?? 0)) {
            ++$n;
            return [\substr($value, $i, $n -= $i), $n, 7];
        }
        return [];
    }
    // The table syntax rule(s), which is as close as possible to the common CommonMark rule(s), are available on the
    // GitHub Flavored Markdown specification page. However, I decided to give more consideration to the original
    // Markdown Extra and Parsedown Extra behavior in the case where `|` character(s) exist within auto-link, code span,
    // and raw HTML syntax. In auto-link, code span, and raw HTML syntax, a `|` character doesn’t need to be escaped
    // when you want to display it literally. I favor the rule that code span, auto-link, and raw HTML syntax bind more
    // tightly than any other syntax in the “inline” sense. I can understand why GitHub prefers the other favor. For
    // GitHub, `|` character(s) in a table syntax may be considered as nearly identical to the block syntax, so that
    // block(s) need to be parsed earlier than inline(s). Here, I consider `|` character(s) to be at the same level of
    // tightness as the `[` and `]` character(s) in a link label syntax.
    function p(string $value, int $i, int $limit, ?int $max = null) {
        $r = [];
        $x = 0;
        while ($i < $limit) {
            $c = $value[$i];
            if ("\\" === $c) {
                ++$x;
                ++$i;
                continue;
            }
            $esc = 1 & $x;
            $x = 0;
            if (!$esc && '`' === $c && ($m = c2e($value, $i, $limit))) {
                $i += $m[1];
                continue;
            }
            if (!$esc && '<' === $c && ($m = a6k($value, $i, $limit)) || ($m = n2e($value, $i, $limit))) {
                $i += $m[1];
                continue;
            }
            if (!$esc && '|' === $c) {
                if (null !== $max && \count($r) >= $max) {
                    break;
                }
                $r[] = $i;
            }
            ++$i;
        }
        return $r;
    }
    function r(string $value, int $i, int $limit) {
        if ($i >= $limit) {
            return 0;
        }
        if ("\n" === $value[$i]) {
            return 1;
        }
        if ("\r" === $value[$i]) {
            if ($i + 1 < $limit && "\n" === $value[$i + 1]) {
                return 2;
            }
            return 1;
        }
        return 0;
    }
    function r3n(string $text) {
        $n = $now = 0;
        for ($i = \strlen($text) - 1; $i >= 0; --$i) {
            $w = match (\strtolower($text[$i])) {
                'i' => 1, 'v' => 5, 'x' => 10, 'l' => 50, 'c' => 100, 'd' => 500, 'm' => 1000, default => 0
            };
            if (!$w) {
                return 0;
            }
            $n += $w < $now ? -$w : $w;
            $now = $w;
        }
        return $n;
    }
    function row(string $value, array &$lot = [], int $deep = 0, int $i = 0, int $limit = 0) {
        $lot = \array_replace([[], [], []], $lot);
        // In case the entire text is an abbreviation, parse it right away!
        if ($v = $lot[2][$value] ?? 0) {
            return [[['abbr', h(v($value)), ['title' => $v]]], $lot, 0];
        }
        $last = null;
        $row = [];
        $s = "";
        $stack = []; // <https://spec.commonmark.org/0.31.2#delimiter-stack>
        while ($i < $limit) {
            $c = $value[$i];
            if ("\\" === $c && $i + 1 < $limit && false !== \strpos(c16, $value[$i + 1])) {
                $s .= $value[++$i];
                ++$i;
                continue;
            }
            if ($r = r($value, $i, $limit)) {
                // <https://spec.commonmark.org/0.31.2#hard-line-break>
                if ("\\" === ($value[$i - 1] ?? 0)) {
                    "" !== $s && ($row[] = h(\substr($s, 0, -1)));
                    $row[] = ['br', false, []];
                    $i += $r;
                    $i += \strspn($value, c1, $i, $limit - $i); // <https://spec.commonmark.org/0.31.2#example-637>
                    $s = "";
                    continue;
                }
                // <https://spec.commonmark.org/0.31.2#hard-line-break>
                if (' ' === ($value[$i - 1] ?? 0)) {
                    $s = \rtrim($s);
                    if (' ' === ($value[$i - 2] ?? 0)) {
                        "" !== $s && ($row[] = h($s));
                        $row[] = ['br', false, []];
                        $i += $r;
                        $i += \strspn($value, c1, $i, $limit - $i); // <https://spec.commonmark.org/0.31.2#example-636>
                        $s = "";
                        continue;
                    }
                }
                // <https://spec.commonmark.org/0.31.2#softbreak>
                $i += \strspn($value, c1, $i, $limit - $i) + $r;
                // Also, remove the initial tab(s) and space(s) on the next line
                $i += \strspn($value, c3, $i, $limit - $i);
                $s .= "\n";
                continue;
            }
            // <https://spec.commonmark.org/0.31.2#entity-and-numeric-character-references>
            if ('&' === $c && false !== ($end = \strpos($value, ';', $i + 2))) {
                static $e;
                $c = h($c);
                if ('#' === ($value[$n = $i + 1] ?? 0)) {
                    if (false !== \strpos('Xx', $value[$i + 2] ?? x1)) {
                        // <https://spec.commonmark.org/0.31.2#hexadecimal-numeric-character-references>
                        $n += \strspn($value, c7, $n + 2) + 2;
                        if ($end === $n && $n - $i - 3 < 7) {
                            $e ??= [];
                            $e[$k = \substr($value, $i, ++$end - $i)] ??= $k !== ($y = \html_entity_decode($k, \ENT_HTML5 | \ENT_QUOTES)) ? $y : "";
                            if ("" !== ($e[$k] ?? "")) {
                                "" !== $s && ($row[] = h($s));
                                $row[] = [false, $k, [], [3]];
                                $i = $end;
                                $s = "";
                                continue;
                            }
                        }
                        $s .= $c;
                        ++$i;
                        continue;
                    }
                    // <https://spec.commonmark.org/0.31.2#decimal-numeric-character-references>
                    $n += \strspn($value, c4, $n + 1) + 1;
                    if ($end === $n && $n - $i - 2 < 8) {
                        $e ??= [];
                        $e[$k = \substr($value, $i, ++$end - $i)] ??= $k !== ($y = \html_entity_decode($k, \ENT_HTML5 | \ENT_QUOTES)) ? $y : "";
                        if ("" !== ($e[$k] ?? "")) {
                            "" !== $s && ($row[] = h($s));
                            $row[] = [false, $k, [], [2]];
                            $i = $end;
                            $s = "";
                            continue;
                        }
                    }
                    $s .= $c;
                    ++$i;
                    continue;
                }
                // <https://spec.commonmark.org/0.31.2#entity-references>
                $n += \strspn($value, c4 . c10, $n);
                if ($end === $n) {
                    // Load a list of known entity reference(s) supported by your PHP to validate the current HTML
                    // entity pattern. This step is necessary to reject unknown entity name(s), such as `&123;`
                    $e ??= \array_flip(\get_html_translation_table(\HTML_ENTITIES, \ENT_HTML5 | \ENT_QUOTES));
                    // If the entity is not present in the list, try to validate it using a more expensive method: pass
                    // the string to the `html_entity_decode()` function and compare the result. If they are the same,
                    // the matching entity pattern is not valid.
                    $e[$k = \substr($value, $i, ++$end - $i)] ??= $k !== ($y = \html_entity_decode($k, \ENT_HTML5 | \ENT_QUOTES)) ? $y : "";
                    if ("" !== ($e[$k] ?? "")) {
                        "" !== $s && ($row[] = h($s));
                        $row[] = [false, $k, [], [1]];
                        $i = $end;
                        $s = "";
                        continue;
                    }
                }
                $s .= $c;
                ++$i;
                continue;
            }
            if ('<' === $c && ($m = a6k($value, $i, $limit))) {
                "" !== $s && ($row[] = h($s));
                $row[] = $m[0];
                $i += $m[1];
                $s = "";
                // Check for attribute syntax after code
                if ('{' === ($value[$i] ?? 0) && ($a = a($value, $i, $limit))) {
                    $row[$k = \array_key_last($row)][2] = $a[0] + $row[$k][2];
                    $i += $a[1];
                }
                continue;
            }
            if ('<' === $c && ($m = n2e($value, $i, $limit))) {
                "" !== $s && ($row[] = h($s));
                $row[] = [false, $m[0], [], [$m[2]]];
                $i += $m[1];
                $s = "";
                continue;
            }
            // <https://spec.commonmark.org/0.31.2#code-span>
            if ('`' === $c && ($m = c2e($value, $i, $limit))) {
                "" !== $s && ($row[] = h($s));
                $row[] = ['code', h($m[0]), []];
                $i += $m[1];
                $s = "";
                // Check for attribute syntax after code
                if ('{' === ($value[$i] ?? 0) && ($a = a($value, $i, $limit))) {
                    $row[\array_key_last($row)][2] = $a[0];
                    $i += $a[1];
                }
                continue;
            }
            // <https://spec.commonmark.org/0.31.2#image-description>
            if ($deep > 0 && '!' === $c && '[' === ($value[$i + 1] ?? 0)) {
                "" !== $s && ($row[] = h($s));
                $current = \count($stack);
                $row[] = $c .= '[';
                $s = "";
                $stack[] = [$c, [2, true, false], [\array_key_last($row), $last, null, $i += 2]];
                if (null !== $last) {
                    $stack[$last][2][2] = $current;
                }
                $last = $current;
                continue;
            }
            // <https://spec.commonmark.org/0.31.2#link-text>
            if ($deep > 0 && '[' === $c) {
                "" !== $s && ($row[] = h($s));
                $current = \count($stack);
                $row[] = $c;
                $s = "";
                $stack[] = [$c, [1, true, false], [\array_key_last($row), $last, null, ++$i]];
                if (null !== $last) {
                    $stack[$last][2][2] = $current;
                }
                $last = $current;
                continue;
            }
            // <https://spec.commonmark.org/0.31.2#delimiter-run>
            if ($deep > 1 && ('*' === $c || '_' === $c)) {
                "" !== $s && ($row[] = h($s));
                $current = \count($stack);
                $n = \strspn($value, $c, $i, $limit - $i);
                // Extract left boundary character
                if (0 === $i) {
                    $c_left = ' ';
                } else {
                    if (\ord($value[$i - 1]) < 0x80) {
                        // Single-byte ASCII
                        $c_left = $value[$i - 1];
                    } else {
                        // Safe backward walk over UTF-8 continuation byte(s)
                        for ($w = 1; $w < 4 && $i - $w > 0 && (0x80 === \ord($value[$i - $w]) & 0xC0); ++$w);
                        $c_left = \substr($value, $i - $w, $w);
                    }
                }
                // Extract right boundary character
                if ($i + $n >= $limit) {
                    $c_right = ' ';
                } else {
                    if (($b = \ord($value[$i + $n])) < 0x80) {
                        // Single-byte ASCII
                        $c_right = $value[$i + $n];
                    } else {
                        // Determine UTF-8 character byte length directly from the lead byte
                        $w = $b < 0xC0 || $b >= 0xF8 ? 1 : ($b < 0xE0 ? 2 : ($b < 0xF0 ? 3 : 4));
                        $c_right = \substr($value, $i + $n, $w);
                    }
                }
                // <https://spec.commonmark.org/0.31.2#left-flanking-delimiter-run>
                $left_w = false !== \strpos(c3, $c_left) || \preg_match('/^[\p{Z}\s]$/u', $c_left);
                $left_p = !$left_w && (false !== \strpos(c16, $c_left) || \preg_match('/^[\p{P}\p{S}]$/u', $c_left));
                // <https://spec.commonmark.org/0.31.2#right-flanking-delimiter-run>
                $right_w = false !== \strpos(c3, $c_right) || \preg_match('/^[\p{Z}\s]$/u', $c_right);
                $right_p = !$right_w && (false !== \strpos(c16, $c_right) || \preg_match('/^[\p{P}\p{S}]$/u', $c_right));
                // Determine whether the current delimiter is flanking to the left or right
                $left_flank = !$right_w && (!$right_p || $left_w || $left_p);
                $right_flank = !$left_w && (!$left_p || $right_w || $right_p);
                $can_close = '*' === $c ? $right_flank : $right_flank && (!$left_flank || $right_p);
                $can_open = '*' === $c ? $left_flank : $left_flank && (!$right_flank || $left_p);
                $row[] = \substr($value, $i, $n);
                $s = "";
                $stack[] = [$c, [$n, $can_open, $can_close], [\array_key_last($row), $last, null, $i += $n]];
                if (null !== $last) {
                    $stack[$last][2][2] = $current;
                }
                $last = $current;
                continue;
            }
            // Michel Fortin’s way to determine text that should be replaced with an abbreviation is very simple. He
            // looks for text that is not preceded or followed by a word character. He relied on a regular expression,
            // yet I already have a list of word(s) to examine the character that precedes and follows the current
            // portion of text. Below is my effort to achieve the same result, without regular expression.
            // <https://github.com/michelf/php-markdown/blob/2.0.0/Michelf/MarkdownExtra.php#L1845-L1847>
            if ($deep > 0 && $lot[1]) {
                $best = [null, -1];
                // The abbreviation list is already sorted by the length of the key(s), from longest to shortest
                foreach ($lot[1] as $k => $v) {
                    if (false === ($n = \strpos($value, $k, $i))) {
                        continue;
                    }
                    if ($best[1] < 0 || $n < $best[1]) {
                        $best = [$k, $n];
                    }
                }
                if (null !== $best[0]) {
                    [$k, $n] = $best;
                    $max = \strlen($k);
                    $s .= \substr($value, $i, $n - $i);
                    if ($n > 0 && false !== \strpos(c18, $value[$n - 1])) {
                        $i = $n + 1;
                        $s .= $value[$n];
                        continue;
                    }
                    if ($max + $n < $limit && false !== \strpos(c18, $value[$max + $n])) {
                        $i = $n + $max;
                        $s .= $k;
                        continue;
                    }
                    "" !== $s && ($row[] = h($s));
                    $i = $max + $n;
                    $row[] = ['abbr', h(v($k)), ['title' => $lot[1][$k]]];
                    $s = "";
                    continue;
                }
            }
            // <https://spec.commonmark.org/0.31.2#look-for-link-or-image>
            if ($deep > 0 && ']' === $c) {
                // No `[` and `![`
                if (null === $last) {
                    $s .= $c;
                    ++$i;
                    continue;
                }
                // Iterate over the delimiter stack from the stack’s bottom to find the nearest `[` or `![`
                for ($at = $last; null !== $at; $at = $stack[$at][2][1]) {
                    if (('![' === $stack[$at][0] || '[' === $stack[$at][0]) && 0 !== $stack[$at][1][0]) {
                        break;
                    }
                }
                if (null === $at) {
                    $s .= $c;
                    ++$i;
                    continue;
                }
                "" !== $s && ($row[] = h($s));
                $eat = null;
                $s = "";
                $v = [null, null, []];
                // <https://spec.commonmark.org/0.31.2#inline-link>
                if ('(' === ($value[$n = $i + 1] ?? 0)) {
                    $n += \strspn($value, c3, $n + 1) + 1;
                    // <https://spec.commonmark.org/0.31.2#link-destination>
                    if ('<' === ($value[$n] ?? 0)) {
                        $eat = ++$n;
                        while ($n < $limit) {
                            $n += \strcspn($value, c2 . "\\<>", $n);
                            if ($n >= $limit || "\\" !== $value[$n]) {
                                break;
                            }
                            $n += 2;
                        }
                        if ('>' === ($value[$n] ?? 0)) {
                            $v[0] = u(v(\substr($value, $eat, $n - $eat)));
                            ++$n;
                        }
                    } else {
                        $d = 0;
                        $eat = $n;
                        while ($n < $limit) {
                            if ("\\" === $value[$n]) {
                                $n += 2;
                                continue;
                            }
                            if ('(' === $value[$n]) {
                                ++$d;
                                ++$n;
                                continue;
                            }
                            if (')' === $value[$n]) {
                                if (0 === $d--) {
                                    break;
                                }
                                ++$n;
                                continue;
                            }
                            if (false !== \strpos(c3 . c17, $value[$n])) {
                                break;
                            }
                            ++$n;
                        }
                        $v[0] = u(v(\substr($value, $eat, $n - $eat)));
                    }
                    if (isset($v[0]) && ($w = \strspn($value, c3, $n))) {
                        if ('(' === ($q = $value[$n += $w] ?? 0)) {
                            $eat = ++$n;
                            while ($n < $limit) {
                                $n += \strcspn($value, "\\()", $n, $limit - $n);
                                if ($n >= $limit || '(' === $value[$n]) {
                                    break;
                                }
                                if ("\\" === $value[$n]) {
                                    $n += 2;
                                    continue;
                                }
                                $v[1] = v(\substr($value, $eat, $n - $eat));
                                ++$n;
                                break;
                            }
                        } else if ('"' === $q || "'" === $q) {
                            $eat = ++$n;
                            while ($n < $limit) {
                                $n += \strcspn($value, "\\" . $q, $n, $limit - $n);
                                if ($n >= $limit || "\\" !== $value[$n]) {
                                    break;
                                }
                                $n += 2;
                            }
                            // Title isn’t closed properly
                            if ($n >= $limit || $q !== $value[$n]) {
                                $v[0] = $eat = null;
                            } else {
                                $v[1] = v(\substr($value, $eat, $n - $eat));
                                ++$n;
                            }
                        }
                    }
                    $n += \strspn($value, c3, $n);
                    // Link or image isn’t closed properly
                    if (!isset($v[0]) || ')' !== ($value[$n] ?? 0)) {
                        $v[0] = $eat = null;
                    } else {
                        $eat = $n + 1;
                        $v[3] = 4;
                    }
                } else if ('[' === ($value[$n = $i + 1] ?? 0)) {
                    // <https://spec.commonmark.org/0.31.2#collapsed-reference-link>
                    if (']' === ($value[$n + 1] ?? 0) && ($key = n(\substr($value, $stack[$at][2][3], $i - $stack[$at][2][3]), ' '))) {
                        if ($f = ($lot[0][f($key)] ?? 0)) {
                            $eat = $i + 3;
                            $v = $f;
                            $v[3] = 2;
                        }
                    // <https://spec.commonmark.org/0.31.2#full-reference-link>
                    } else if ($key = k($value, $n, $limit)) {
                        if ($f = ($lot[0][f($key[0])] ?? 0)) {
                            $eat = $key[1] + $n;
                            $v = $f;
                            $v[3] = 1;
                        }
                    }
                // <https://spec.commonmark.org/0.31.2#shortcut-reference-link>
                } else if ($key = n(\substr($value, $stack[$at][2][3], $i - $stack[$at][2][3]), ' ')) {
                    if ('^' === $key[0] && isset($lot[2][$key])) {
                        $eat = $i + 1;
                        $suffix = $lot[x1][$key] = ($lot[x1][$key] ?? -1) + 1;
                        $v = [(string) ($n = \array_search($key, \array_keys($lot[2]), true) + 1), $n . (0 !== $suffix ? '.' . $suffix : ""), [], x1];
                    } else if ($f = $lot[0][f($key)] ?? 0) {
                        $eat = $i + 1;
                        $v = $f;
                        $v[3] = 3;
                    }
                }
                if (null === $eat) {
                    // Remove current opener from the delimiter stack
                    $next = $stack[$at][2][2];
                    $prev = $stack[$at][2][1];
                    if (null !== $prev) {
                        $stack[$prev][2][2] = $next;
                    }
                    if (null !== $next) {
                        $stack[$next][2][1] = $prev;
                    }
                    if ($last === $at) {
                        $last = $prev;
                    }
                    $stack[$at][1][0] = 0;
                    $stack[$at][2][1] = $stack[$at][2][2] = null;
                    $s .= $c;
                    ++$i;
                    continue;
                }
                $chunk = [];
                $chunk_set = [];
                $current = $stack[$at][2][0];
                foreach ($row as $k => $r) {
                    if ($k <= $current) {
                        continue;
                    }
                    $chunk_set[$k] = \count($chunk);
                    $chunk[] = $r;
                    $row[$k] = "";
                }
                $chunk_at = $stack[$at][2][2];
                $chunk_last = null;
                $chunk_stack = [];
                while (null !== $chunk_at && isset($chunk_set[$stack[$chunk_at][2][0]])) {
                    $chunk_next = $stack[$chunk_at][2][2];
                    $chunk_v = $stack[$chunk_at];
                    $chunk_v[2][0] = $chunk_set[$chunk_v[2][0]];
                    $chunk_v[2][1] = $chunk_last;
                    $chunk_v[2][2] = null;
                    $chunk_stack[] = $chunk_v;
                    $chunk_current = \array_key_last($chunk_stack);
                    if (null !== $chunk_current) {
                        $chunk_stack[$chunk_last][2][2] = $chunk_current;
                    }
                    $chunk_last = $chunk_current;
                    // Cleanly unlink from the main stack
                    $parent_next = $stack[$chunk_at][2][2];
                    $parent_prev = $stack[$chunk_at][2][1];
                    if (null !== $parent_prev) {
                        $stack[$parent_prev][2][2] = $parent_next;
                    }
                    if (null !== $parent_next) {
                        $stack[$parent_next][2][1] = $parent_prev;
                    }
                    // Once we have successfully separated the current chunk’s stack from the main stack, we can safely
                    // move the main `$last` cursor backward to the previous node of the main stack.
                    if ($last === $chunk_at) {
                        $last = $parent_prev;
                    }
                    $stack[$chunk_at][2][1] = null;
                    $stack[$chunk_at][2][2] = null;
                    $chunk_at = $chunk_next;
                }
                // Process emphasis in the chunk
                if ($deep > 1) {
                    $chunk = e($chunk, $chunk_stack, $chunk_last);
                }
                $chunk = y($chunk);
                // <https://spec.commonmark.org/0.31.2#links>
                if ('[' === $stack[$at][0]) {
                    if (x1 === $v[3]) {
                        $row[$current] = ['sup', [['a', $v[0], [
                            'href' => '#to:' . $v[0],
                            'role' => 'doc-noteref'
                        ]]], ['id' => 'from:' . $v[1]]];
                    } else {
                        $row[$current] = ['a', $chunk, ($v[2] ?? []) + [
                            'href' => $v[0],
                            'title' => $v[1]
                        ], [$v[3]]];
                    }
                    if ('[' === $stack[$at][0]) {
                        for ($k = $stack[$at][2][1]; null !== $k; $k = $stack[$k][2][1]) {
                            if ('[' === $stack[$k][0]) {
                                $stack[$k][1][0] = 0;
                            }
                        }
                    }
                // <https://spec.commonmark.org/0.31.2#images>
                } else {
                    $row[$current] = ['img', false, ($v[2] ?? []) + [
                        'alt' => alt($chunk),
                        'src' => $v[0],
                        'title' => $v[1]
                    ], [$v[3]]];
                }
                $i = $eat;
                // We don’t store the active state of the stack data on the delimiter stack to save space. Instead, we
                // use the delimiter length to determine if a stack is active. For example, to turn off a stack, we
                // simply set the delimiter length to `0`.
                $stack[$at][1][0] = 0;
                // Check for attribute syntax after link or image
                if ('{' === ($value[$i] ?? 0) && ($a = a($value, $i, $limit))) {
                    $row[$current][2] = $a[0] + $row[$current][2];
                    $i += $a[1];
                }
                continue;
            }
            // At this point, it is safe to skip ahead to the next character that Markdown finds “interesting”
            if ($n = \strcspn($value, c2 . '&<`' . "\\" . ($deep > 0 ? '![]' : "") . ($deep > 1 ? '*_' : ""), $i)) {
                $s .= s($value, $i, $n);
                $i += $n;
                continue;
            }
            $s .= $c;
            ++$i;
        }
        if ("" !== $s) {
            $row[] = h($s);
        }
        // Process emphasis
        if ($deep > 1) {
            $row = e($row, $stack, $last);
        }
        return [y($row, true), $lot, 0];
    }
    function rows(string $value, array &$lot = [], int $deep = 0, int $i = 0, int $limit = 0) {
        $lot = \array_replace([[], [], []], $lot);
        if ("" === \trim($value)) {
            return [[], $lot, 0];
        }
        $rows = [];
        $s = "";
        $void = 0;
        while ($i < $limit) {
            $m = m($value, $i, $limit);
            // <https://spec.commonmark.org/0.31.2#blank-line>
            if ($m[0] === \strspn($value, c1, $i, $limit - $i)) {
                "" !== $s && ($rows[] = ['p', \trim($s), []]) && ($s = "");
                while ($i < $limit && ($m = m($value, $i, $limit)) && $m[0] === \strspn($value, c1, $i, $limit - $i)) {
                    $i += $m[0] + $m[1];
                }
                ++$void;
                continue;
            }
            $d = d($value, $i, $limit);
            // A tab, 4 space(s), or less than 4 of space(s) followed by a tab should occupy at least 4 character(s)
            // <https://spec.commonmark.org/0.31.2#indented-code-block>
            if ($d[0] >= 4) {
                // <https://spec.commonmark.org/0.31.2#example-113>
                if ("" !== $s) {
                    $s .= s($value, $i, $m[0]) . "\n";
                    $i += $m[0] + $m[1];
                    continue;
                }
                $s = s($value, $i, $m[0], 0, 4);
                $i += $m[0] + $m[1];
                while ($i < $limit) {
                    $m = m($value, $i, $limit);
                    if ($m[0] !== \strspn($value, c1, $i, $m[0]) && d($value, $i, $limit)[0] < 4) {
                        // Previous line was a blank line
                        if ("" !== $s && "\n" === $s[-1]) {
                            $s = \rtrim($s, "\n");
                            ++$void;
                        }
                        break;
                    }
                    $s .= "\n" . s($value, $i, $m[0], 0, 4);
                    $i += $m[0] + $m[1];
                    // End of the stream
                    if (0 === $m[1]) {
                        break;
                    }
                }
                $rows[] = ['pre', [['code', h($s . "\n"), []]], [], [0, ""]];
                $s = "";
                continue;
            }
            // At this point, the number of character(s) occupied by the indentation, which is made up by a mix of
            // space(s) and tab(s), should be the same, because an indentation less than 4 character(s) would never be
            // made by a mix of space(s) and tab(s). A tab already covers at most 4 column(s), so any indentation less
            // than 4 character(s) must be made up of space(s) only. This variable can then be used to jump past the
            // first few space(s) that precede the actual block marker.
            $d = $d[1];
            // Perform an early check for the first character after the optional white-space(s) that could mark a block.
            // If the minimum required block marker is not present, treat the current line as a paragraph block.
            if (false === \strpos(c4 . '!#*+-=:<>AI[_`ai|~', $value[$d + $i])) {
                $s .= s($value, $i, $m[0]) . "\n";
                $i += $m[0] + $m[1];
                continue;
            }
            // The description list block uses Michel Fortin’s syntax. Unfortunately, this block cannot be processed in
            // one direction because the `:` character only indicates the start of a description detail. Semantically,
            // description detail(s) cannot stand alone without their term(s). To identify a valid description list,
            // locate the description detail syntax then check the previous line. If it is a paragraph block, then the
            // entire list is valid.
            if (':' === $value[$n = $d + $i] && false !== \strpos(c3, $value[$n + 1] ?? c3[0])) {
                // In case the paragraph block has been put in the queue, pop it out!
                if ("" === $s && $rows && \is_array($row = $rows[$last = \array_key_last($rows)]) && 'p' === $row[0]) {
                    $s = x1 . \trim($row[1]) . x2 . "\n";
                    unset($rows[$last]);
                    --$void; // Cancel out the last blank line count because the last block is now part of this one
                }
                if ("" !== $s) {
                    if (x1 !== $s[0] && x2 !== $s[-2]) {
                        $s = x1 . \trim($s, "\n") . x2;
                    }
                } else {
                    $s .= s($value, $i, $m[0]) . "\n";
                    $i += $m[0] + $m[1];
                    continue;
                }
                $min = $d + 2;
                if (!r($value, $i + $min - 1, $limit) && ($w = d($value, $i + $min, $limit)[0]) < 4) {
                    $min += $w;
                }
                $s .= "\n" . x3 . s($value, $i, $m[0], 0, $min);
                $i += $m[0] + $m[1];
                while ($i < $limit) {
                    $m = m($value, $i, $limit);
                    // A blank line continues the current block
                    if ($m[0] === \strspn($value, c1, $i, $m[0])) {
                        $s .= "\n";
                        $i += $m[0] + $m[1];
                        if (\strlen($s) > 1 && "\n" === $s[-1] && x3 === $s[-2]) {
                            break;
                        }
                        continue;
                    }
                    $d = d($value, $i, $limit);
                    if ($d[0] < \min($min, 4) && ':' === ($value[$n = $d[1] + $i] ?? 0) && false !== \strpos(c3, $value[$n + 1] ?? c3[0])) {
                        $min = $d[0] + 2;
                        if (!r($value, $i + $min - 1, $limit) && ($w = d($value, $i + $min, $limit)[0]) < 4) {
                            $min += $w;
                        }
                        $s .= "\n" . x3 . s($value, $i, $m[0], $min - $w, $min);
                        $i += $m[0] + $m[1];
                        continue;
                    }
                    if ($d[0] >= $min) {
                        $s .= "\n" . s($value, $i, $m[0], 0, $min);
                        $i += $m[0] + $m[1];
                        continue;
                    }
                    if ("" !== $s && "\n" !== $s[-1] && x3 !== $s[-1]) {
                        $b = rows($value, $lot, 0, $i, $i + $m[0])[0] ?? [];
                        // Current line is not a paragraph continuation text
                        if (!($b = \reset($b)) || !('p' === $b[0] || 'pre' === $b[0] && "" === $b[3][1] || false === $b[0] && 7 === $b[3][0])) {
                            break;
                        }
                        $s .= "\n" . s($value, $i, $m[0]);
                        $i += $m[0] + $m[1];
                        continue;
                    }
                    if ("\n" === ($s[-1] ?? 0)) {
                        ++$void;
                    }
                    break;
                }
                if ($rows && \is_array($row = $rows[$last = \array_key_last($rows)]) && 'dl' === $row[0]) {
                    $rows[$last][1] .= "\n\n" . x3 . \trim($s, "\n");
                    $s = "";
                    continue;
                }
                $rows[] = ['dl', \rtrim($s, "\n"), [], [':', false]];
                $s = "";
                continue;
            }
            // I am so sorry about the order, especially for those of you with ADHD. This parser does not process HTML
            // block of type 1 through 7 in order. It instead starts with a type of block that’s easier to spot.
            // <https://spec.commonmark.org/0.31.2#html-block>
            if ('<' === $value[$d + $i]) {
                // <https://spec.commonmark.org/0.31.2#processing-instruction>
                if ('?' === ($value[$n = $d + $i + 1] ?? 0) && false !== ($end = \strpos($value, '?>', $n + 1))) {
                    "" !== $s && ($rows[] = ['p', \trim($s), []]) && ($s = "");
                    $rows[] = [false, \substr($value, $i, ($end += \strcspn($value, c2, $end)) - $i), [], [3]];
                    $i = $end + r($value, $end, $limit);
                    continue;
                }
                if ('!' === ($value[$n = $d + $i + 1] ?? 0)) {
                    // <https://spec.commonmark.org/0.31.2#html-comment>
                    if (0 === substr_compare($value, '--', $n + 1, 2) && false !== ($end = \strpos($value, '-->', $n + 1))) {
                        "" !== $s && ($rows[] = ['p', \trim($s), []]) && ($s = "");
                        $rows[] = [false, \substr($value, $i, ($end += \strcspn($value, c2, $end)) - $i), [], [2]];
                        $i = $end + r($value, $end, $limit);
                        continue;
                    }
                    // <https://spec.commonmark.org/0.31.2#cdata-section>
                    if (0 === substr_compare($value, '[CDATA[', $n + 1, 7) && false !== ($end = \strpos($value, ']]>', $n + 1))) {
                        "" !== $s && ($rows[] = ['p', \trim($s), []]) && ($s = "");
                        $rows[] = [false, \substr($value, $i, ($end += \strcspn($value, c2, $end)) - $i), [], [5]];
                        $i = $end + r($value, $end, $limit);
                        continue;
                    }
                    // <https://spec.commonmark.org/0.31.2#declaration>
                    if (\strspn($value, c10, $n + 1, 1) && false !== ($end = \strpos($value, '>', $n + 1))) {
                        "" !== $s && ($rows[] = ['p', \trim($s), []]) && ($s = "");
                        $rows[] = [false, \substr($value, $i, ($end += \strcspn($value, c2, $end)) - $i), [], [4]];
                        $i = $end + r($value, $end, $limit);
                        continue;
                    }
                    $s .= s($value, $i, $m[0]) . "\n";
                    $i += $m[0] + $m[1];
                    continue;
                }
                // <https://spec.commonmark.org/0.31.2#html-tag>
                $b = \strtolower(\substr($value, $n = $d + $i + 1, \strcspn($value, c3 . '>', $n)));
                if (isset(b1[$b]) && false !== ($end = \stripos($value, '</' . $b . '>', $n + \strlen($b) + 1))) {
                    "" !== $s && ($rows[] = ['p', \trim($s), []]) && ($s = "");
                    $rows[] = [false, \substr($value, $i, ($end += \strcspn($value, c2, $end)) - $i), [], [1, $b]];
                    $i = $end + r($value, $end, $limit);
                    continue;
                }
                // HTML block of type 6 does not treat open and close tag(s) differently. The initial tag does not need
                // to be a valid HTML tag. As long as it starts like one, it will be interpreted as such. Even a start
                // tag that looks like `<div <!— <?asdf` still counts as a valid HTML block of type 6.
                if (isset(b6[$b = \trim($b, '/')])) {
                    "" !== $s && ($rows[] = ['p', \trim($s), []]) && ($s = "");
                    $s = \substr($value, $i, $m[0]);
                    $i += $m[0] + $m[1];
                    while ($i < $limit) {
                        $m = m($value, $i, $limit);
                        // A blank line ends the current block
                        if ($m[0] === \strspn($value, c1, $i, $m[0])) {
                            break;
                        }
                        $s .= "\n" . \substr($value, $i, $m[0]);
                        $i += $m[0] + $m[1];
                        // End of the stream
                        if (0 === $m[1]) {
                            break;
                        }
                    }
                    $rows[] = [false, $s, [], [6, $b]];
                    $s = "";
                    continue;
                }
                // HTML block of type 7 cannot interrupt a paragraph
                if ("" === $s && $d + $i + 2 < $limit) {
                    for ($n = $i + $m[0]; $n > $i && false !== \strpos(c1, $value[$n - 1]); --$n);
                    // HTML block of type 7 must be “complete”
                    if ($n > $i && '>' === $value[$n - 1] && ($b = n2e($value, $d + $i, $n)) && $n - ($d + $i) === $b[1]) {
                        $s = \substr($value, $i, $m[0]);
                        $i += $m[0] + $m[1];
                        while ($i < $limit) {
                            $m = m($value, $i, $limit);
                            // A blank line ends the current block
                            if ($m[0] === \strspn($value, c1, $i, $m[0])) {
                                break;
                            }
                            $s .= "\n" . \substr($value, $i, $m[0]);
                            $i += $m[0] + $m[1];
                            // End of the stream
                            if (0 === $m[1]) {
                                break;
                            }
                        }
                        $rows[] = [false, $s, [], [7]];
                        $s = "";
                        continue;
                    }
                }
                $s .= s($value, $i, $m[0]) . "\n";
                $i += $m[0] + $m[1];
                continue;
            }
            // <https://spec.commonmark.org/0.31.2#block-quotes>
            if ('>' === $value[$d + $i]) {
                "" !== $s && ($rows[] = ['p', \trim($s), []]) && ($s = "");
                $text = s($value, $i, $m[0], 0, $d + 1);
                if (' ' === ($text[0] ?? 0)) {
                    $text = \substr($text, 1);
                }
                $s .= $text;
                $i += $m[0] + $m[1];
                while ($i < $limit) {
                    $d = d($value, $i, $limit);
                    $m = m($value, $i, $limit);
                    if ($d[0] < 4 && '>' === ($value[$d[1] + $i] ?? 0)) {
                        $text = s($value, $i, $m[0], 0, $d[1] + 1);
                        if (' ' === ($text[0] ?? 0)) {
                            $text = \substr($text, 1);
                        }
                        $s .= "\n" . $text;
                        $i += $m[0] + $m[1];
                        continue;
                    }
                    // A blank line ends the current block
                    if ($m[0] === \strspn($value, c1, $i, $m[0])) {
                        break;
                    }
                    $b = rows($value, $lot, 0, $i, $i + $m[0])[0] ?? [];
                    // Current line is not a paragraph continuation text
                    if (!($b = \reset($b)) || !('p' === $b[0] || 'pre' === $b[0] && "" === $b[3][1] || false === $b[0] && 7 === $b[3][0])) {
                        break;
                    }
                    // At this point, a paragraph continuation text is present next to the currently captured quote
                    // block. According to the CommonMark specification, there are currently only 3 type(s) of block(s)
                    // that accept paragraph continuation text: quote, paragraph, and list item. Quote and paragraph
                    // block(s) usually will close when a blank line follows. As such, we can assume that quote block
                    // content that ends with a blank line is safe to mark as a closed block, which will not accept
                    // paragraph continuation text.
                    if ("" !== $s && "\n" === $s[-1]) {
                        break;
                    }
                    // For all other case(s), we need to verify that the last block in the quote block content can
                    // accept paragraph continuation text.
                    $b = rows($s, $lot, 0, 0, \strlen($s))[0] ?? [];
                    if (!($b = \end($b)) || !('blockquote' === $b[0] || 'p' === $b[0] || \in_array($b[0], ['dl', 'ol', 'ul'], true))) {
                        break;
                    }
                    // There is a special case for a paragraph continuation text that looks like a setext heading’s
                    // underline. From the “dingus”, it needs to be treated as textual content, somehow.
                    // <https://spec.commonmark.org/0.31.2#example-93>
                    if (false !== \strpos('-=', $value[$d[1] + $i] ?? x1)) {
                        $s .= "\n" . s($value, $i, $d[1]);
                        // Add a back-slash escape so current line will not be treated as a setext heading’s underline
                        $s .= "\\" . s($value, $i + $d[1], $m[0]);
                        $i += $m[0] + $m[1];
                        continue;
                    }
                    // <https://spec.commonmark.org/0.31.2#paragraph-continuation-text>
                    $s .= "\n" . s($value, $i, $m[0]);
                    $i += $m[0] + $m[1];
                    // End of the stream
                    if (0 === $m[1]) {
                        break;
                    }
                }
                // <https://spec.commonmark.org/0.31.2#example-218>
                "" !== $s && \strpos($s, ']:') > 1 && rows($s, $lot, 1, 0, \strlen($s));
                $rows[] = ['blockquote', $s, []];
                $s = "";
                continue;
            }
            // If an image stands alone in a paragraph, the paragraph will be converted into a figure element. This is
            // an improvisation that I came up with. The CommonMark specification does not specify that it has to behave
            // this way. Since there is no special delimiter to mark the boundary of this block, it has been configured
            // so that it cannot interrupt a paragraph, similar to setext heading(s).
            if ("" === $s && '!' === $value[$n = $d + $i] && '[' === ($value[++$n] ?? 0) && ($k = k($value, $n, $limit, $deep - 1, 1)) && false !== \strpos(c3 . '([{', $value[$n + $k[1]] ?? x1)) {
                for ($n = $i + $m[0]; $n > $i && false !== \strpos(c1, $value[$n - 1]); --$n);
                // Image block must be “complete”
                if ($n > $i && false !== \strpos(')]}', $value[$n - 1])) {
                    // Attempt to parse the image on the current line right away
                    $row = row($value, $lot, 1, $d + $i, $n)[0];
                    // Verify that the final result is a lone image element
                    $valid = \is_array($row) && 1 === \count($row) && \is_array($row = \reset($row)) && 'img' === $row[0];
                    // It is tricky to verify a reference-style image. In order for a reference-style label to be valid,
                    // the reference link must exist before the label. Since we are attempting to parse the image
                    // immediately, there may be a case where the parsing fails due to the missing reference link. This
                    // requires an extra step to capture reference link(s) that may exist further down the current line.
                    if (!$valid && false !== \strpos($value, ']:', $i + $m[0] + $m[1])) {
                        // Attempt to collect reference link(s) further down the current line
                        $try = [];
                        rows($value, $try, 1, $i + $m[0] + $m[1], $limit);
                        // Verify the final result once more
                        if (!empty($try[0]) && ($row = row($value, $try, 1, $d + $i, $n)[0])) {
                            $valid = \is_array($row) && 1 === \count($row) && \is_array($row = \reset($row)) && 'img' === $row[0];
                        }
                    }
                    if ($valid) {
                        $s = s($value, $i, $m[0]) . x2;
                        $i += $m[0] + $m[1];
                        $min = $d + 1;
                        // Capture potential image caption
                        while ($i < $limit) {
                            $m = m($value, $i, $limit);
                            // A blank line continues the current block
                            if ($m[0] === \strspn($value, c1, $i, $m[0])) {
                                $s .= "\n";
                                $i += $m[0] + $m[1];
                                continue;
                            }
                            // Found a line that is not blank and is more indented than the line with the image
                            if (d($value, $i, $limit)[0] > $d) {
                                // If an image block is immediately followed by a non-paragraph continuation text,
                                // append a blank line to mark the image caption as a container block.
                                if ("\n" !== $s[-1] && ($b = rows($value, $lot, 0, $d + $i, $i + $m[0])[0] ?? []) && ($b = \reset($b))) {
                                    if (!('p' === $b[0] || 'pre' === $b[0] && "" === $b[3][1] || false === $b[0] && 7 === $b[3][0])) {
                                        $s .= "\n";
                                    }
                                }
                                $s .= "\n" . s($value, $i, $m[0], 0, $min);
                                $i += $m[0] + $m[1];
                                continue;
                            }
                            // At this point, the image caption must be a leaf block
                            if ("\n" !== $s[-1] && false === \strpos($s, "\n\n") && ($b = rows($value, $lot, 0, $i, $i + $m[0])[0] ?? []) && ($b = \reset($b))) {
                                if ('p' === $b[0] || false === $b[0] && 7 === $b[3][0]) {
                                    $s .= "\n" . s($value, $i, $m[0]);
                                    $i += $m[0] + $m[1];
                                    continue;
                                }
                            }
                            if ("\n" === $s[-1]) {
                                ++$void;
                            }
                            break;
                        }
                        $rows[] = ['figure', \rtrim($s, "\n"), []];
                        $s = "";
                        continue;
                    }
                }
                $s .= s($value, $i, $m[0]) . "\n";
                $i += $m[0] + $m[1];
                continue;
            }
            // There is no formal specification for the abbreviation block in CommonMark, so I will treat it similarly
            // to the link reference definition block. It acts as a leaf block that cannot interrupt a paragraph. It can
            // span multiple line(s), but it cannot contain any blank line(s).
            if ("" === $s && '*' === $value[$n = $d + $i] && '[' === ($value[++$n] ?? 0) && ($k = k($value, $n, $limit)) && ':' === ($value[$n + $k[1]] ?? 0)) {
                $key = $k[0];
                $n += $k[1] + 1;
                $n += \strspn($value, c1, $n);
                // Refresh the cursor in case the label spans multiple line(s)
                $m = m($value, $i = $n, $limit);
                $s = s($value, $i, $m[0]);
                $i += $m[0] + $m[1];
                while ($i < $limit) {
                    $m = m($value, $i, $limit);
                    // A blank line ends the current block
                    if ($m[0] === \strspn($value, c1, $i, $m[0])) {
                        break;
                    }
                    // Current line is an indented line
                    if (d($value, $i, $limit)[0] > $d) {
                        $s .= "\n" . s($value, $i, $m[0]);
                        $i += $m[0] + $m[1];
                        continue;
                    }
                    $b = rows($value, $lot, 0, $i, $i + $m[0])[0] ?? [];
                    // Current line is not a paragraph continuation text
                    if (!($b = \reset($b)) || !('p' === $b[0] || 'pre' === $b[0] && "" === $b[3][1] || false === $b[0] && 7 === $b[3][0])) {
                        break;
                    }
                    // <https://spec.commonmark.org/0.31.2#paragraph-continuation-text>
                    $s .= "\n" . s($value, $i, $m[0]);
                    $i += $m[0] + $m[1];
                    // End of the stream
                    if (0 === $m[1]) {
                        break;
                    }
                }
                $deep > 0 && !isset($lot[1][$key]) && ($lot[1][$key] = "" !== ($s = n($s, ' ')) ? $s : null);
                $s = "";
                continue;
            }
            if ("" === $s && '[' === $value[$n = $d + $i] && '^' === ($value[$n + 1] ?? 0) && ($k = k($value, $n, $limit)) && ':' === ($value[$n + $k[1]] ?? 0)) {
                $key = f($k[0]);
                $n += $k[1] + 1;
                $n += \strspn($value, c1, $n);
                // Refresh the cursor in case the label spans multiple line(s)
                $m = m($value, $i = $n, $limit);
                $s = s($value, $i, $m[0]);
                $i += $m[0] + $m[1];
                while ($i < $limit) {
                    $m = m($value, $i, $limit);
                    if ($m[0] === \strspn($value, c1, $i, $m[0])) {
                        $s .= "\n";
                        $i += $m[0] + $m[1];
                        continue;
                    }
                    // Current line is an indented line
                    if (d($value, $i, $limit)[0] > $d) {
                        $s .= "\n" . s($value, $i, $m[0]);
                        $i += $m[0] + $m[1];
                        continue;
                    }
                    // Previous line was a blank line
                    if ("" !== $s && "\n" === $s[-1]) {
                        $s = \rtrim($s, "\n");
                        ++$void;
                        break;
                    }
                    $b = rows($value, $lot, 0, $i, $i + $m[0])[0] ?? [];
                    // Current line is not a paragraph continuation text
                    if (!($b = \reset($b)) || !('p' === $b[0] || 'pre' === $b[0] && "" === $b[3][1] || false === $b[0] && 7 === $b[3][0])) {
                        break;
                    }
                    // <https://spec.commonmark.org/0.31.2#paragraph-continuation-text>
                    $s .= "\n" . s($value, $i, $m[0]);
                    $i += $m[0] + $m[1];
                    // End of the stream
                    if (0 === $m[1]) {
                        break;
                    }
                }
                $deep > 0 && !isset($lot[2][$key]) && ($lot[2][$key] = $s);
                $s = "";
                continue;
            }
            // <https://spec.commonmark.org/0.31.2#link-reference-definition>
            if ("" === $s && '[' === $value[$n = $d + $i] && ($k = k($value, $n, $limit)) && ':' === ($value[$n + $k[1]] ?? 0)) {
                $key = f($k[0]);
                $n += $k[1] + 1;
                $n += \strspn($value, c1, $n);
                $n += ($r = r($value, $n, $limit));
                $v = [null, null];
                $w = \strspn($value, c1, $n);
                // <https://spec.commonmark.org/0.31.2#link-destination>
                if ('<' === ($value[$n + $w] ?? 0)) {
                    // Make sure it is not an HTML block of other than type 7
                    if ($r && ($b = rows($value, $lot, 0, $n, $n + \strcspn($value, c2, $n))[0][0] ?? 0)) {
                        if (false === $b[0] && 7 !== $b[3][0]) {
                            $s .= s($value, $i, $m[0]) . "\n";
                            $i += $m[0] + $m[1];
                            continue;
                        }
                    }
                    $eat = ++$n + $w;
                    while ($n + $w < $limit) {
                        $n += \strcspn($value, c2 . "\\<>", $n + $w);
                        if ($n + $w >= $limit || "\\" !== $value[$n + $w]) {
                            break;
                        }
                        $n += 2;
                    }
                    if ('>' === ($value[$n + $w] ?? 0)) {
                        $v[0] = u(v(\substr($value, $eat, ($n += $w) - $eat)));
                        ++$n;
                    }
                } else if ($eat = \strcspn($value, c3 . c17, $n + $w)) {
                    $v[0] = u(\substr($value, $n + $w, $eat));
                    $n += $eat + $w;
                }
                if (isset($v[0]) && ($r = r($value, $n, $limit))) {
                    $n += $r;
                    // A blank line ends the current block
                    if (r($value, $n + \strspn($value, c1, $n), $limit)) {
                        $deep > 0 && !isset($lot[0][$key]) && ($lot[0][$key] = $v);
                        $i = $n;
                        continue;
                    }
                }
                // <https://spec.commonmark.org/0.31.2#link-title>
                $w = \strspn($value, c1, $n);
                if (isset($v[0]) && ($r || $w)) {
                    $q = $value[$n + $w] ?? 0;
                    if ('"' === $q || "'" === $q || '(' === $q) {
                        $eat = ++$n + $w;
                        $q = '(' === $q ? ')' : $q;
                        while ($n + $w < $limit) {
                            $n += \strcspn($value, "\\" . $q, $n + $w);
                            if ($n + $w >= $limit || "\\" !== $value[$n + $w]) {
                                break;
                            }
                            $n += 2;
                        }
                        if ($n + $w >= $limit || $q !== $value[$n + $w]) {
                            break;
                        }
                        $v[1] = v(\substr($value, $eat, ($n += $w) - $eat));
                        ++$n;
                    }
                    if (isset($v[1]) && ($r = r($value, $n, $limit))) {
                        $n += $r;
                        // A blank line ends the current block
                        if (r($value, $n + \strspn($value, c1, $n), $limit)) {
                            $deep > 0 && !isset($lot[0][$key]) && ($lot[0][$key] = $v);
                            $i = $n;
                            continue;
                        }
                    }
                }
                // Check for attribute syntax after link reference definition
                $w = \strspn($value, c1, $n);
                if (isset($v[0]) && ($r || $w)) {
                    if ('{' === ($value[$n + $w] ?? 0) && ($a = a($value, $n + $w, $limit))) {
                        $v[2] = $a[0];
                        $n += $a[1] + $w;
                    }
                    if (isset($v[2]) && ($r = r($value, $n, $limit))) {
                        $n += $r;
                        // A blank line ends the current block
                        if (r($value, $n + \strspn($value, c1, $n), $limit)) {
                            $deep > 0 && !isset($lot[0][$key]) && ($lot[0][$key] = $v);
                            $i = $n;
                            continue;
                        }
                    }
                }
                if (isset($v[0]) && ($r || $n >= $limit)) {
                    $deep > 0 && !isset($lot[0][$key]) && ($lot[0][$key] = $v);
                    $i = $n;
                    continue;
                }
                $s .= s($value, $i, $m[0]) . "\n";
                $i += $m[0] + $m[1];
                continue;
            }
            // <https://spec.commonmark.org/0.31.2#code-fence>
            if (false !== \strpos('`~', $c = $value[$d + $i]) && ($min = \strspn($value, $c, $d + $i)) >= 3) {
                // <https://spec.commonmark.org/0.31.2#info-string>
                $info = \trim(\substr($value, $d + $i + $min, $m[0] - $d - $min));
                // <https://spec.commonmark.org/0.31.2#example-145>
                if ('`' === $c && false !== \strpos($info, $c)) {
                    $s .= s($value, $i, $m[0]) . "\n";
                    $i += $m[0] + $m[1];
                    continue;
                }
                "" !== $s && ($rows[] = ['p', \trim($s), []]) && ($s = "");
                $i += $m[0] + $m[1];
                while ($i < $limit) {
                    $m = m($value, $i, $limit);
                    $w = \strspn($value, ' ', $i);
                    // End of the block
                    if ($w < 4 && \strspn($value, $c, $i + $w) >= $min) {
                        $i += $m[0] + $m[1];
                        break;
                    }
                    // <https://spec.commonmark.org/0.31.2#example-131>
                    // <https://spec.commonmark.org/0.31.2#example-132>
                    // <https://spec.commonmark.org/0.31.2#example-133>
                    $text = s($value, $i, $m[0]);
                    if ($w = \strspn($text, ' ')) {
                        $text = \substr($text, \min($d, $w));
                    }
                    $s .= $text . "\n";
                    $i += $m[0] + $m[1];
                    // End of the stream
                    if (0 === $m[1]) {
                        break;
                    }
                }
                $rows[] = ['pre', [['code', h($s), a($info, 0, \strlen($info), '{' !== ($info[0] ?? 0), 'language-%s')[0] ?? []]], [], [$min, $c]];
                $s = "";
                continue;
            }
            // <https://github.github.com/gfm#table>
            if (false !== ($w = \strpos($value, '|', $d + $i)) && $w < $i + $m[0] && p($value, $i, $i + $m[0]) && ("" === $s || (\strlen($s) === \strpos($s, "\n") + 1 && p($s, 0, \strlen($s))))) {
                // <https://github.github.com/gfm#delimiter-row>
                if ($m[0] === \strspn($value, c1 . '-:|', $i, $limit)) {
                    $s .= x1;
                }
                $s .= \trim(\trim(\trim(s($value, $i, $m[0])), '|'));
                $i += $m[0] + $m[1];
                $min = $d + 1;
                while ($i < $limit) {
                    $m = m($value, $i, $limit);
                    // A blank line starts the table caption
                    if ($m[0] === \strspn($value, c1, $i, $m[0])) {
                        $s .= x2 . "\n";
                        $i += $m[0] + $m[1];
                        continue;
                    }
                    // Found a line that is not blank and is more indented than the table block
                    if (d($value, $i, $limit)[0] > $d) {
                        // If a table block is immediately followed by a non-paragraph continuation text, append a blank
                        // line to mark the table caption as a container block.
                        if ("\n" !== $s[-1] && ($b = rows($value, $lot, 0, $d + $i, $i + $m[0])[0] ?? []) && ($b = \reset($b))) {
                            $s .= x2;
                            if (!('p' === $b[0] || 'pre' === $b[0] && "" === $b[3][1] || false === $b[0] && 7 === $b[3][0])) {
                                $s .= "\n";
                            }
                        }
                        $s .= "\n" . s($value, $i, $m[0], 0, $min);
                        $i += $m[0] + $m[1];
                        continue;
                    }
                    if ("\n" !== $s[-1] && false === \strpos($s, "\n\n") && ($b = rows($value, $lot, 0, $i, $i + $m[0])[0] ?? []) && ($b = \reset($b))) {
                        if ('p' === $b[0] || 'table' === $b[0] || false === $b[0] && 7 === $b[3][0]) {
                            // At this point, the table caption must be a leaf block. A paragraph continuation text
                            // without any `|` character(s) that sits next to the table syntax stream will be treated as
                            // a table caption. This is different from the way paragraph continuation text is treated in
                            // the GFM specification, where it will be treated as a “lazy table row” instead.
                            // <https://github.github.com/gfm#example-202>
                            if (!p($value, $i, $i + $m[0])) {
                                $s .= (false !== \strpos($s, x2) ? "\n" : x2) . s($value, $i, $m[0]);
                            } else {
                                $s .= $m[0] === \strspn($value, c1 . '-:|', $i, $limit) ? x1 : "\n";
                                $s .= \trim(\trim(\trim(s($value, $i, $m[0])), '|'));
                            }
                            $i += $m[0] + $m[1];
                            continue;
                        }
                    }
                    if ("\n" === $s[-1]) {
                        ++$void;
                    }
                    break;
                }
                $rows[] = ['table', \rtrim($s, "\n"), []];
                $s = "";
                continue;
            }
            // <https://spec.commonmark.org/0.31.2#setext-heading>
            // This must come before the list and the thematic break parser because it uses `-` for heading level 2.
            // Since `-` can also be used as a list or thematic break marker, it is necessary to verify that the
            // previously identified block is a paragraph that is not followed by any blank line(s). Any other case is
            // considered invalid and will therefore fall through the list or thematic break parser.
            if (false !== \strpos('-=', $c = $value[$n = $d + $i]) && "" !== $s) {
                $a = [];
                $eat = $n + \strspn($value, $c, $n);
                $eat += \strspn($value, c1, $eat);
                if (!r($value, $eat, $limit) && ($a = a($value, $eat, $eat + \strcspn($value, c2, $eat), '{' !== ($value[$eat] ?? 0)))) {
                    $eat += $a[1];
                }
                $eat += \strspn($value, c1, $eat);
                if ($eat === $i + $m[0]) {
                    $s = \trim($s);
                    if (!$a && ($start = \strrpos($s, '{'))) {
                        for ($x = 0; $start - 1 - $x >= 0 && "\\" === $s[$start - 1 - $x]; ++$x);
                        $n = $start - 1 - $x;
                        if ($n >= 0 && !(1 & $x) && false !== \strpos(c1, $s[$n])) {
                            if ($a = a($s, $start, $max = \strlen($s))) {
                                if ($max === $start + $a[1]) {
                                    $s = \substr($s, 0, $start - 1);
                                } else {
                                    $a = []; // Broken attribute syntax
                                }
                            }
                        }
                    }
                    $rows[] = ['h' . ('-' === $c ? 2 : 1), $s, $a[0] ?? [], ['-' === $c ? 2 : 1, $c]];
                    $i += $m[0] + $m[1];
                    $s = "";
                    continue;
                }
            }
            // <https://spec.commonmark.org/0.31.2#thematic-break>
            // This must come before the list parser. Since `-` can also be used as a thematic break marker where the
            // next character is allowed to be a white-space, it is necessary to verify that the current line contains
            // more than 2 `-`, and consists solely of `-` and white-space(s). Any other combination is considered
            // invalid and will therefore fall through the list parser.
            if (false !== \strpos('*-_', $c = $value[$n = $d + $i])) {
                $a = [];
                $eat = $n + \strspn($value, c1 . $c, $n);
                if (!r($value, $eat, $limit) && ($a = a($value, $eat, $eat + \strcspn($value, c2, $eat), '{' !== ($value[$eat] ?? 0)))) {
                    $eat += $a[1];
                }
                $eat += \strspn($value, c1, $eat);
                if ($eat === $i + $m[0] && ($w = \substr_count($value, $c, $i, \strcspn($value, '={', $i, $m[0])) >= 3)) {
                    "" !== $s && ($rows[] = ['p', \trim($s), []]) && ($s = "");
                    $rows[] = ['hr', false, $a[0] ?? [], [$w, $c]];
                    $i += $m[0] + $m[1];
                    continue;
                }
            }
            // <https://spec.commonmark.org/0.31.2#ordered-list>
            if ((
                // A, B, C, …
                // I, II, III, …
                // a, b, c, …
                // i, ii, iii, …
                false !== \strpos('AIai', $value[$n = $d + $i]) && ($w = 1) ||
                // 1, 2, 3, …
                ($w = \strspn($value, c4, $n)) && $w < 10
            ) && false !== \strpos(').', $c = $value[$n + $w] ?? x1) && false !== \strpos(c3, $value[$n + $w + 1] ?? c3[0])) {
                $current = $start = match ($type = $t = \substr($value, $n, $min = $d + $w)) {
                    'A' => l3n($t, 1), 'I' => r3n($t, 1), 'a' => l3n($t), 'i' => r3n($t), default => (int) $t
                };
                $min += 2;
                if (!r($value, $i + $min - 1, $limit) && ($w = d($value, $i + $min, $limit)[0]) < 4) {
                    $min += $w;
                }
                if ("" !== $s) {
                    // <https://spec.commonmark.org/0.31.2#example-285>
                    // <https://spec.commonmark.org/0.31.2#example-304>
                    // <https://spec.commonmark.org/0.31.2#example-305>
                    if (1 !== $start || "" === \trim(s($value, $i, $m[0], 0, $min))) {
                        $s .= s($value, $i, $m[0]) . "\n";
                        $i += $m[0] + $m[1];
                        continue;
                    }
                    $rows[] = ['p', \trim($s), []];
                    $s = "";
                }
                $s .= s($value, $i, $m[0], 0, $min);
                $i += $m[0] + $m[1];
                while ($i < $limit) {
                    $m = m($value, $i, $limit);
                    // A blank line continues the current block
                    if ($m[0] === \strspn($value, c1, $i, $m[0])) {
                        $s .= "\n";
                        $i += $m[0] + $m[1];
                        // <https://spec.commonmark.org/0.31.2#example-280>
                        if ("\n" === $s) {
                            break;
                        }
                        continue;
                    }
                    $d = d($value, $i, $limit);
                    $n = $d[1] + $i;
                    if ($d[0] < \min($min, 4) && (
                        'A' === $type && ($w = \strspn($value, c8, $n)) ||
                        'I' === $type && ($w = \strspn($value, 'CDILMVX', $n)) ||
                        'a' === $type && ($w = \strspn($value, c9, $n)) ||
                        'i' === $type && ($w = \strspn($value, 'cdilmvx', $n)) ||
                        ($w = \strspn($value, c4, $n = $d[1] + $i)) && $w < 10
                    ) && $c === ($value[$n + $w] ?? 0) && false !== \strpos(c3, $value[$n + $w + 1] ?? c3[0])) {
                        // The CommonMark specification does not care about the order of the number value(s). My parser
                        // keeps track of the number value(s) and does a strict comparison with the previous number
                        // value. A valid continuation list item number value must be the same as the previous list item
                        // number value or must be the one more step of the previous list item number value.
                        $t = \substr($value, $i, $min = $d[0] + $w);
                        $next = match ($type) {
                            'A' => l3n($t, 1), 'I' => r3n($t, 1), 'a' => l3n($t), 'i' => r3n($t), default => (int) $t
                        };
                        if ($next - $current > 1 || $next < $current) {
                            if (1 === $next) {
                                break;
                            }
                            $s .= "\n" . s($value, $i, $m[0]);
                            $i += $m[0] + $m[1];
                            continue;
                        }
                        $min += 2;
                        if (!r($value, $i + $min - 1, $limit) && ($w = d($value, $i + $min, $limit)[0]) < 4) {
                            $min += $w;
                        }
                        $s .= "\n" . x3 . s($value, $i, $m[0], $min - $w, $min);
                        $i += $m[0] + $m[1];
                        $current = $next;
                        continue;
                    }
                    if ($d[0] >= $min) {
                        $s .= "\n" . s($value, $i, $m[0], 0, $min);
                        $i += $m[0] + $m[1];
                        continue;
                    }
                    if ("" !== $s && "\n" !== $s[-1]) {
                        $b = rows($value, $lot, 0, $i, $i + $m[0])[0] ?? [];
                        // Current line is not a paragraph continuation text
                        if (!($b = \reset($b)) || !('p' === $b[0] || 'pre' === $b[0] && "" === $b[3][1] || false === $b[0] && 7 === $b[3][0])) {
                            break;
                        }
                        $s .= "\n" . s($value, $i, $m[0]);
                        $i += $m[0] + $m[1];
                        continue;
                    }
                    if ("\n" === ($s[-1] ?? 0)) {
                        ++$void;
                    }
                    break;
                }
                $a = 1 !== $start ? ['start' => $start] : [];
                if (false !== \strpos('AIai', $type)) {
                    $a['type'] = $type;
                }
                $rows[] = ['ol', \rtrim($s, "\n"), $a, [$c, false]];
                $s = "";
                continue;
            }
            // <https://spec.commonmark.org/0.31.2#bullet-list>
            if (false !== \strpos('*+-', $c = $value[$n = $d + $i]) && false !== \strpos(c3, $value[$n + 1] ?? c3[0])) {
                $min = $d + 2;
                if (!r($value, $i + $min - 1, $limit) && ($w = d($value, $i + $min, $limit)[0]) < 4) {
                    $min += $w;
                }
                if ("" !== $s) {
                    // <https://spec.commonmark.org/0.31.2#example-285>
                    if ("" === \trim(s($value, $i, $m[0], 0, $min))) {
                        $s .= s($value, $i, $m[0]) . "\n";
                        $i += $m[0] + $m[1];
                        continue;
                    }
                    $rows[] = ['p', \trim($s), []];
                    $s = "";
                }
                $s .= s($value, $i, $m[0], 0, $min);
                $i += $m[0] + $m[1];
                while ($i < $limit) {
                    $m = m($value, $i, $limit);
                    // A blank line continues the current block
                    if ($m[0] === \strspn($value, c1, $i, $m[0])) {
                        $s .= "\n";
                        $i += $m[0] + $m[1];
                        // <https://spec.commonmark.org/0.31.2#example-280>
                        if ("\n" === $s) {
                            break;
                        }
                        continue;
                    }
                    $d = d($value, $i, $limit);
                    if ($d[0] < \min($min, 4) && $c === ($value[$n = $d[1] + $i] ?? 0) && false !== \strpos(c3, $value[$n + 1] ?? c3[0])) {
                        $min = $d[0] + 2;
                        if (!r($value, $i + $min - 1, $limit) && ($w = d($value, $i + $min, $limit)[0]) < 4) {
                            $min += $w;
                        }
                        $b = rows($value, $lot, 0, $i, $i + $m[0])[0] ?? [];
                        // Current line is a thematic break
                        if (($b = \reset($b)) && 'hr' === $b[0]) {
                            break;
                        }
                        $s .= "\n" . x3 . s($value, $i, $m[0], $min - $w, $min);
                        $i += $m[0] + $m[1];
                        continue;
                    }
                    if ($d[0] >= $min) {
                        $s .= "\n" . s($value, $i, $m[0], 0, $min);
                        $i += $m[0] + $m[1];
                        continue;
                    }
                    if ("" !== $s && "\n" !== $s[-1]) {
                        $b = rows($value, $lot, 0, $i, $i + $m[0])[0] ?? [];
                        // Current line is not a paragraph continuation text
                        if (!($b = \reset($b)) || !('p' === $b[0] || 'pre' === $b[0] && "" === $b[3][1] || false === $b[0] && 7 === $b[3][0])) {
                            break;
                        }
                        $s .= "\n" . s($value, $i, $m[0]);
                        $i += $m[0] + $m[1];
                        continue;
                    }
                    if ("\n" === ($s[-1] ?? 0)) {
                        ++$void;
                    }
                    break;
                }
                $rows[] = ['ul', \rtrim($s, "\n"), [], [$c, false]];
                $s = "";
                continue;
            }
            // <https://spec.commonmark.org/0.31.2#atx-heading>
            if (($w = \strspn($value, '#', $d + $i)) && $w < 7 && false !== \strpos(c3, $value[$n = $d + $i + $w] ?? c3[0])) {
                "" !== $s && ($rows[] = ['p', \trim($s), []]);
                $s = \trim(s($value, $n + \strspn($value, c1, $n), $m[0] - $w));
                if ($max = \strlen($s)) {
                    $a = [];
                    if (false !== ($start = \strrpos($s, '{'))) {
                        for ($n = $start - 1, $o = 0; $n >= 0 && "\\" === $s[$n]; --$n, ++$o);
                        if ($n >= 0 && !(1 & $o) && false !== \strpos(c1 . '#', $s[$n])) {
                            if ('#' === $s[$n]) {
                                for ($h = 0; $n >= 0 && '#' === $s[$n]; ++$h, --$n);
                                for ($x = 0; $n >= 0 && "\\" === $s[$n]; --$n, ++$x);
                                if ((1 & $x) || ($n >= 0 && false === \strpos(c1, $s[$n]))) {
                                    $n = -1; // Invalid trailing hash(es)
                                }
                            }
                            if ($n >= 0 && ($a = a($s, $start, $max))) {
                                if ($max === $start + $a[1]) {
                                    $max = \strlen($s = \substr($s, 0, $start - 1));
                                }
                            }
                        }
                    }
                    if ('#' === $s[$max - 1]) {
                        for ($n = $max - 1; $n >= 0 && '#' === $s[$n]; --$n);
                        for ($x = 0; $n >= 0 && "\\" === $s[$n]; --$n, ++$x);
                        if (!(1 & $x) && ($n < 0 || false !== \strpos(c1, $s[$n]))) {
                            $s = \trim(\substr($s, 0, $n + 1));
                        }
                    }
                }
                $rows[] = ['h' . $w, \trim($s), $a[0] ?? [], [$w, '#']];
                $i += $m[0] + $m[1];
                $s = "";
                continue;
            }
            $s .= s($value, $i, $m[0]) . "\n";
            $i += $m[0] + $m[1];
        }
        if ("" !== $s) {
            $rows[] = ['p', \trim($s), []];
        }
        if ($deep > 0 && $rows) {
            // Sort the abbreviation list based on the length of the key(s), from longest to shortest. This ensures that
            // during parsing, the text “JavaScript” will be processed before the text “Java”, for example.
            $lot[1] && \uksort($lot[1], function ($a, $b) {
                return \strlen($b) <=> \strlen($a);
            });
            // Sort the note key(s)
            $lot[2] && \ksort($lot[2]);
            foreach ($rows as &$row) {
                // Container block(s)
                if ('blockquote' === $row[0]) {
                    $row[1] = rows($row[1], $lot, $deep - 1, 0, \strlen($row[1]))[0] ?: "";
                    continue;
                }
                if ('figure' === $row[0]) {
                    $part = \explode(x2, $row[1], 2);
                    $row[1] = [];
                    $row[1][0] = row($part[0], $lot, $deep - 1, \strspn($part[0], ' '), \strlen($part[0]))[0][0];
                    if (isset($part[1]) && "" !== $part[1]) {
                        // Image caption as a container block
                        if (false !== \strpos($part[1], "\n\n") && ($r = rows($part[1] = \trim($part[1], "\n"), $lot, $deep - 1, 0, \strlen($part[1]), 1)[0] ?: "")) {
                            $row[1][1] = ['figcaption', $r, []];
                        // Image caption as a leaf block
                        } else if ($r = row($part[1] = \trim($part[1]), $lot, $deep - 1, 0, \strlen($part[1]))[0] ?: "") {
                            $row[1][1] = ['figcaption', $r, []];
                        }
                    }
                    continue;
                }
                if ('table' === $row[0]) {
                    $part = \explode(x1, $row[1], 2);
                    $part[1] = \explode(x2, $part[1] ?? "", 2);
                    $body = \explode("\n", \trim($part[1][0]));
                    $cap = $part[1][1] ?? "";
                    $max = \count($style = \explode('|', \array_shift($body)));
                    foreach ($style as &$s) {
                        $s = \trim($s);
                        $to = "";
                        if (':' === $s[0]) {
                            $to = ':' === $s[-1] ? 'center' : 'left';
                        } else if (':' === $s[-1]) {
                            $to = 'right';
                        }
                        $s = $to ? ['style' => 'text-align: ' . $to . ';'] : [];
                    }
                    unset($s);
                    $row[1] = [];
                    if ("" !== ($head = \trim($part[0]))) {
                        $row[1][] = ['thead', [], []];
                        $r = ['tr', [], []];
                        foreach (s3t($head, $max) as $k => $v) {
                            $r[1][] = ['th', row($v = \trim($v), $lot, $deep - 1, 0, \strlen($v))[0] ?: "", $style[$k] ?? []];
                        }
                        $r[1] = \array_pad($r[1], $max, ['th', "", []]);
                        $row[1][0][1][] = $r;
                    }
                    if ($body) {
                        $at = "" !== $head ? 1 : 0;
                        $row[1][] = ['tbody', [], []];
                        foreach ($body as $b) {
                            $r = ['tr', [], []];
                            foreach (s3t($b, $max) as $k => $v) {
                                $r[1][] = ['td', row($v = \trim($v), $lot, $deep - 1, 0, \strlen($v))[0] ?: "", $style[$k] ?? []];
                            }
                            $r[1] = \array_pad($r[1], $max, ['td', "", []]);
                            $row[1][$at][1][] = $r;
                        }
                    }
                    if ("" !== \trim($cap)) {
                        // Table caption as a container block
                        if (false !== \strpos($cap, "\n\n")) {
                            $cap = rows($cap = \trim($cap), $lot, $deep - 1, 0, \strlen($cap))[0] ?: "";
                        // Table caption as a leaf block
                        } else {
                            $cap = row($cap = \trim($cap), $lot, $deep - 1, 0, \strlen($cap))[0] ?: "";
                        }
                        \array_unshift($row[1], ['caption', $cap, []]);
                    }
                    continue;
                }
                if ('dl' === $row[0]) {
                    $raws = \explode("\n" . x3, $row[1]);
                    if (!$lax = false !== \strpos($row[1], "\n\n" . x3)) {
                        foreach ($raws as $r) {
                            if (false !== \strpos($r, "\n\n") && rows($r, $lot, 0, 0, \strlen($r))[2]) {
                                $lax = true;
                                break;
                            }
                        }
                    }
                    $row[1] = [];
                    foreach ($raws as $r) {
                        $r = \trim($r, "\n");
                        if ("" !== $r && x1 === $r[0] && x2 === $r[-1]) {
                            foreach (\explode("\n", \trim(\substr($r, 1, -1), "\n")) as $v) {
                                $row[1][] = ['dt', row($v, $lot, $deep - 1, 0, \strlen($v))[0] ?: "", []];
                            }
                            continue;
                        }
                        $r = rows($r, $lot, $deep - 1, 0, \strlen($r))[0];
                        if (!$lax) {
                            if (1 === \count($r) && \is_array($r[0]) && 'p' === $r[0][0]) {
                                $r = $r[0][1];
                            } else {
                                foreach ($r as &$v) {
                                    if (\is_array($v) && 'p' === $v[0]) {
                                        $v[0] = null;
                                    }
                                }
                            }
                            unset($v);
                        }
                        $row[1][] = ['dd', $r ?: "", []];
                    }
                    $row[3][1] = $lax;
                    continue;
                }
                if (\in_array($row[0], ['ol', 'ul'], true)) {
                    $raws = \explode("\n" . x3, $row[1]);
                    // <https://spec.commonmark.org/0.31.2#loose>
                    // <https://spec.commonmark.org/0.31.2#tight>
                    if (!$lax = false !== \strpos($row[1], "\n\n" . x3)) {
                        foreach ($raws as $r) {
                            if (false !== \strpos($r, "\n\n") && rows($r, $lot, 0, 0, \strlen($r))[2]) {
                                $lax = true;
                                break;
                            }
                        }
                    }
                    $row[1] = [];
                    foreach ($raws as $r) {
                        $r = rows($r = \trim($r, "\n"), $lot, $deep - 1, 0, \strlen($r))[0];
                        if (!$lax) {
                            if (1 === \count($r) && \is_array($r[0]) && 'p' === $r[0][0]) {
                                $r = $r[0][1];
                            } else {
                                foreach ($r as &$v) {
                                    if (\is_array($v) && 'p' === $v[0]) {
                                        $v[0] = null;
                                    }
                                }
                            }
                            unset($v);
                        }
                        $row[1][] = ['li', $r ?: "", []];
                    }
                    $row[3][1] = $lax;
                    continue;
                }
                // Leaf block(s)
                if (false !== $row[0] && \is_string($row[1])) {
                    $row[1] = row($row[1], $lot, $deep - 1, 0, \strlen($row[1]))[0];
                }
            }
            unset($row);
        }
        if (deep === $deep && $lot[2]) {
            $keys = \array_flip(\array_keys($lot[2]));
            $notes = ['div', [
                ['hr', false, []],
                ['ol', [], []]
            ], ['role' => 'doc-endnotes']];
            foreach ($lot[2] as $k => $v) {
                $i = 0;
                $limit = \strlen($v = \trim($v, c1));
                $min = \PHP_INT_MAX;
                // Identify the shortest white-space(s) prefix and use it as the maximum indentation to remove
                while ($i < $limit) {
                    if (false === ($n = \strpos($v, "\n", $i))) {
                        $n = $limit;
                    }
                    $d = \strspn($v, ' ', $i, $n - $i);
                    if ($d < $n - $i) {
                        if ($d && $d < $min) {
                            $min = $d;
                        }
                    }
                    $i = ++$n;
                }
                if ($min && \PHP_INT_MAX !== $min) {
                    $i = 0;
                    $r = "";
                    // Iterate over each line of text to remove white-space(s) up to the shortest indentation
                    while ($i < $limit) {
                        if (false === ($n = \strpos($v, "\n", $i))) {
                            $n = $limit;
                        }
                        $d = \strspn($v, ' ', $i, $n - $i);
                        $max = \min($d, $min);
                        $r .= \substr($v, $i + $max, $n - $i - $max) . "\n";
                        $i = ++$n;
                    }
                    $v = $r;
                }
                $v = \trim($v, "\n");
                $r = rows($lot[2][$k] = $v, $lot, $deep - 1, 0, \strlen($v))[0] ?: "";
                if (\is_array($r)) {
                    if (\is_array($r[$last = \array_key_last($r)]) && 'p' === $r[$last][0]) {} else {
                        $r[++$last] = ['p', [], []];
                    }
                    $r[$last][1] = (array) $r[$last][1];
                    if (false !== ($max = $lot[x1][$k] ?? false)) {
                        for ($i = 0; $i <= $max; ++$i) {
                            if ($r[$last][1]) {
                                $r[$last][1][] = [false, $y = '&#xa0;', [], [3]];
                            }
                            $r[$last][1][] = ['a', [[false, $y = '&#x21a9;', [], [3]]], [
                                'href' => '#from:' . ($keys[$k] + 1) . (0 !== $i ? '.' . $i : ""),
                                'role' => 'doc-backlink'
                            ]];
                        }
                    }
                }
                $r && ($notes[1][1][1][] = ['li', $r, [
                    'id' => 'to:' . ($keys[$k] + 1),
                    'role' => 'doc-endnote'
                ], []]);
            }
            if (!empty($notes[1][1][1])) {
                $rows[] = $notes;
            }
            unset($lot[x1]);
        }
        return [$rows, $lot, $void];
    }
    function s(string $value, int $i, int $max, int $d = 0, int $eat = 0) {
        if (false === ($t = \strpos($value, "\t", $i)) || $t >= $i + $max) {
            $s = \substr($value, $i, $max);
            return $eat ? \substr($s, $eat) : $s;
        }
        $s = "";
        while ($max) {
            $w = \strcspn($value, "\t", $i);
            if ($w > $max) {
                $w = $max;
            }
            $s .= \substr($value, $i, $w);
            $d += $w;
            $i += $w;
            if ($max -= $w) {
                $s .= \str_repeat(' ', $w = 4 - ($d % 4));
                $d += $w;
                ++$i;
                --$max; // Consume 1 tab
            }
        }
        return $eat ? \substr($s, $eat) : $s;
    }
    function s3t(string $text, ?int $max = null) {
        $i = 0;
        $p = p($text, 0, \strlen($text), null !== $max ? $max - 1 : $max);
        $r = [];
        foreach ($p as $n) {
            $r[] = \substr($text, $i, $n - $i);
            $i = $n + 1;
        }
        $r[] = \substr($text, $i);
        return $r;
    }
    function tag($row, array $state, int $deep = 0) {
        if (!$row) {
            return "";
        }
        if (false === $row[0]) {
            return $row[1];
        }
        if (\is_string($row)) {
            return $row;
        }
        static $blocks = [
            'blockquote' => 1,
            'caption' => 2,
            'dd' => 2,
            'div' => 1, // for `<div role="doc-endnotes">`
            'dl' => 1,
            'figcaption' => 2,
            'figure' => 1,
            'li' => 2,
            'ol' => 1,
            'table' => 1,
            'tbody' => 1,
            'thead' => 1,
            'tr' => 1,
            'ul' => 1
        ];
        if (\is_int($tab = $state['tab'] ?? "")) {
            $tab = $tab > 0 ? \str_repeat(' ', $tab) : "";
        } else if (!\is_string($tab)) {
            $tab = null;
        }
        $block = null !== $tab && isset($blocks[$row[0]]);
        $tag = null !== $row[0];
        // The `caption`, `dd`, `figcaption`, and `li` can behave as a container or leaf block in the final result
        if ($block && 2 === ($blocks[$row[0]] ?? 0)) {
            if (\is_array($row[1])) {
                foreach ($row[1] as $r) {
                    if (\is_string($r)) {
                        $block = 0;
                        break;
                    }
                }
            } else if (\is_string($row[1])) {
                $block = 0;
            }
        }
        $tab = \str_repeat($tab ?? "", $deep);
        $s = $tab . ($tag ? '<' . $row[0] : "");
        if ($row[2]) {
            \ksort($row[2]);
            foreach ($row[2] as $k => $v) {
                if (false === $v || null === $v) {
                    continue;
                }
                $s .= ' ' . $k . (true === $v ? "" : '="' . \strtr(h($v), ['"' => '&quot;']) . '"');
            }
        }
        if (false === $row[1]) {
            return $s .= ' />';
        }
        $s .= ($tag ? '>' : "") . ($block && !empty($row[1]) ? "\n" : "");
        if (\is_array($row[1])) {
            foreach ($row[1] as $r) {
                $s .= (\is_string($r) ? $r : tag($r, $state, $block ? $deep + 1 : 0)) . ($block ? "\n" : "");
            }
        } else {
            $s .= $row[1];
        }
        return $s . ($block && !empty($row[1]) ? $tab : "") . ($tag ? '</' . $row[0] . '>' : "");
    }
    function tags($rows, array $state) {
        if (!$rows) {
            return "";
        }
        if (\is_string($rows)) {
            return \trim($rows);
        }
        $s = "";
        if (\is_int($tab = $state['tab'] ?? "")) {
            $tab = $tab > 0 ? \str_repeat(' ', $tab) : "";
        } else if (!\is_string($tab)) {
            $tab = null;
        }
        foreach ($rows as $row) {
            $s .= ("" === $s || null === $tab ? "" : "\n") . tag($row, $state);
        }
        return $s;
    }
    function u(string $text) {
        $limit = \strlen($text);
        $raw = c14 . c15;
        $s = "";
        for ($i = 0; $i < $limit; ++$i) {
            $c = $text[$i];
            if ('%' === $c && $i + 2 < $limit && false !== \strpos(c7, $text[$i + 1]) && false !== \strpos(c7, $text[$i + 2])) {
                $s .= $c . $text[$i + 1] . $text[$i + 2];
                $i += 2;
                continue;
            }
            $s .= false !== \strpos($raw, $c) ? $c : '%' . \strtoupper(\bin2hex($c));
        }
        return $s;
    }
    function v(string $text) {
        if ("" === $text || false === \strpos($text, "\\")) {
            return $text;
        }
        // <https://spec.commonmark.org/0.31.2#ascii-punctuation-character>
        // <https://spec.commonmark.org/0.31.2#example-12>
        $i = 0;
        $limit = \strlen($text);
        $s = "";
        while ($i < $limit) {
            if ("\\" === $text[$i] && $i + 1 < $limit && false !== \strpos(c16, $text[$i + 1])) {
                ++$i;
            }
            $s .= $text[$i++];
        }
        return $s;
    }
    function y($row, $flat = false) {
        if (\is_array($row)) {
            $limit = \count($row);
            $r = [];
            $s = "";
            for ($i = 0; $i < $limit; ++$i) {
                if (\is_string($c = $row[$i])) {
                    $s .= $flat ? \strtr($c, "\n", ' ') : $c;
                    continue;
                }
                "" !== $s && ($r[] = $s);
                $r[] = $c;
                $s = "";
            }
            if ("" !== $s) {
                $r[] = $s;
            }
            $row = $r;
        }
        return \is_array($row) && 1 === \count($row) && \is_string($row[$k = \array_key_first($row)]) ? $row[$k] : ($row ?: "");
    }
}