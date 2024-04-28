<?php

namespace x\markdown {
    function from(?string $value, $block = true): ?string {
        if (!$block) {
            [$row] = from\row($value);
            if (!$row) {
                return null;
            }
            if (\is_string($row)) {
                $value = \trim(\preg_replace('/\s+/', ' ', $row));
                return "" !== $value ? $value : null;
            }
            foreach ($row as &$v) {
                $v = \is_array($v) ? from\s($v) : $v;
            }
            $value = \trim(\preg_replace('/\s+/', ' ', \implode("", $row)));
            return "" !== $value ? $value : null;
        }
        [$rows] = from\rows($value);
        if (!$rows) {
            return null;
        }
        foreach ($rows as &$row) {
            $row = \is_array($row) ? from\s($row) : $row;
        }
        if ("" === ($value = \implode("", $rows))) {
            return null;
        }
        return \strtr($value, ['</dl><dl>' => ""]);
    }
}

namespace x\markdown\from {
    function a(?string $info, $raw = false) {
        if ("" === ($info = \trim($info ?? ""))) {
            return $raw ? [] : null;
        }
        $a = [];
        $class = [];
        $id = null;
        if ('{' === $info[0] && '}' === \substr($info, -1)) {
            if ("" === ($info = \trim(\substr($info, 1, -1)))) {
                return $raw ? [] : null;
            }
            $pattern = '/([#.](?>\\\\.|[\w:-])+|(?>[\w:.-]+(?>=(?>' . q('"') . '|' . q("'") . '|\S+)?)?))/';
            foreach (\preg_split($pattern, $info, -1, \PREG_SPLIT_DELIM_CAPTURE | \PREG_SPLIT_NO_EMPTY) as $v) {
                if ("" === \trim($v)) {
                    continue; // Skip the space(s)
                }
                // `{#a}`
                if ('#' === $v[0]) {
                    $id = $id ?? \substr($v, 1);
                    continue;
                }
                // `{.a}`
                if ('.' === $v[0]) {
                    $class[] = \substr($v, 1);
                    continue;
                }
                // `{a=b}`
                if (false !== \strpos($v, '=')) {
                    $v = \explode('=', $v, 2);
                    // `{a=}`
                    if ("" === $v[1]) {
                        $a[$v[0]] = "";
                        continue;
                    }
                    // `{a="b"}` or `{a='b'}`
                    if ('"' === $v[1][0] || "'" === $v[1][0]) {
                        $v[1] = v(\substr($v[1], 1, -1));
                    // `{a=false}`
                    } else if ('false' === $v[1]) {
                        $v[1] = false;
                    // `{a=true}`
                    } else if ('true' === $v[1]) {
                        $v[1] = true;
                    // `{a=null}`
                    } else if ('null' === $v[1]) {
                        $v[1] = null;
                    }
                    if ('class' === $v[0]) {
                        $class[] = $v[1]; // Merge class value(s)
                        continue;
                    }
                    $a[$v[0]] = $v[1];
                    continue;
                }
                // `{a}`
                $a[$v] = true;
            }
            if ($class = \array_unique($class)) {
                \sort($class);
                $a['class'] = \implode(' ', $class);
            }
            if ($id) {
                $a['id'] = $id;
            }
            $a && \ksort($a);
            if ($raw) {
                return $a;
            }
            $out = [];
            foreach ($a as $k => $v) {
                $out[] = true === $v ? $k : $k . '="' . e($v) . '"';
            }
            return $out ? ' ' . \implode(' ', $out) : null;
        }
        foreach (\preg_split('/\s+|(?=[#.])/', $info, -1, \PREG_SPLIT_NO_EMPTY) as $v) {
            if ('#' === $v[0]) {
                $id = $id ?? \substr($v, 1);
                continue;
            }
            if ('.' === $v[0]) {
                $class[] = \substr($v, 1);
                continue;
            }
            $class[] = 'language-' . $v;
        }
        if ($class = \array_unique($class)) {
            \sort($class);
            $a['class'] = \implode(' ', $class);
        }
        if ($id) {
            $a['id'] = $id;
        }
        $a && \ksort($a);
        if ($raw) {
            return $a;
        }
        $out = [];
        foreach ($a as $k => $v) {
            $out[] = $k . '="' . e($v) . '"';
        }
        if ($out) {
            \sort($out);
            return ' ' . \implode(' ', $out);
        }
        return null;
    }
    function abbr(string $row, array &$lot = []) {
        // Optimize if current row is an abbreviation
        if (isset($lot[1][$row])) {
            $title = $lot[1][$row];
            return [['abbr', e($row), ['title' => "" !== $title ? $title : null], -1]];
        }
        // Else, chunk current row by abbreviation
        $abbr = [];
        if (!empty($lot[1])) {
            foreach ($lot[1] as $k => $v) {
                $abbr[] = \preg_quote($k, '/');
            }
        }
        if ($abbr) {
            $chops = [];
            foreach (\preg_split('/\b(' . \implode('|', $abbr) . ')\b/', $row, -1, \PREG_SPLIT_DELIM_CAPTURE | \PREG_SPLIT_NO_EMPTY) as $v) {
                if (isset($lot[1][$v])) {
                    $title = $lot[1][$v];
                    $chops[] = ['abbr', e($v), ['title' => "" !== $title ? $title : null], -1];
                    continue;
                }
                $chops[] = e($v);
            }
            return $chops;
        }
        return $row;
    }
    function d(?string $v, $as = \ENT_HTML5 | \ENT_QUOTES) {
        return \html_entity_decode($v ?? "", $as, 'UTF-8');
    }
    function e(?string $v, $as = \ENT_HTML5 | \ENT_QUOTES) {
        return \htmlspecialchars($v ?? "", $as, 'UTF-8');
    }
    function l(?string $link) {
        // ``
        if (!$link) {
            return true;
        }
        // `asdf` or `../asdf` or `/asdf` or `?asdf` or `#asdf`
        if (0 !== \strpos($link, '//') && (false === \strpos($link, '://') || false !== \strpos('./?#', $link[0]))) {
            return true;
        }
        // `//127.0.0.1` or `*://127.0.0.1`
        if (\parse_url($link, \PHP_URL_HOST) === ($_SERVER['HTTP_HOST'] ?? 0)) {
            return true;
        }
        return false;
    }
    function m($row) {
        if (!$row || !\is_array($row)) {
            return $row;
        }
        if (1 === ($count = \count($row))) {
            if (\is_string($v = \reset($row))) {
                return $v;
            }
            if (\is_array($v) && false === $v[0] && \is_string($v[1])) {
                return $v[1];
            }
        }
        // Concatenate a series of string(s) into one string
        foreach ($row = \array_values($row) as $k => $v) {
            if (\is_string($row[$k - 1] ?? 0) && \is_string($v)) {
                $row[$k - 1] .= $v;
                unset($row[$k]);
                continue;
            }
        }
        if (1 === \count($row = \array_values($row))) {
            if (\is_string($v = \reset($row))) {
                return $v;
            }
            if (\is_array($v) && false === $v[0] && \is_string($v[1])) {
                return $v[1];
            }
        }
        return $row;
    }
    function of(?string $row): array {
        if ("" === ($row ?? "")) {
            return [null, $row, [], 0];
        }
        $dent = \strspn($row, ' ');
        if ($dent >= 4) {
            return ['pre', \substr($row, 4), [], $dent, [1]];
        }
        // Remove indent(s)
        $row = \substr($row, $dent);
        if ("" === $row) {
            return [null, $row, [], $dent];
        }
        if (false !== \strpos($row, '|')) {
            return ['table', $row, [], $dent];
        }
        // `!…`
        if (0 === \strpos($row, '!')) {
            if (
                // `![asdf](…`
                \strpos($row, '](') > 0 ||
                // `![asdf][…`
                \strpos($row, '][') > 0 ||
                // `![asdf]` not followed by a `:`
                false !== ($n = \strpos($row, ']')) && ':' !== \substr($row, $n + 1, 1)
            ) {
                // `…)` or `…]` or `…}`
                if (false !== \strpos(')]}', \substr($row, -1))) {
                    return ['figure', $row, [], $dent];
                }
            }
            return ['p', $row, [], $dent];
        }
        // `#…`
        if (0 === \strpos($row, '#')) {
            $n = \strspn($row, '#');
            // `#######…`
            if ($n > 6) {
                return ['p', $row, [], $dent];
            }
            // `# …`
            if ($n === \strlen($row) || ' ' === \substr($row, $n, 1)) {
                return ['h' . $n, $row, [], $dent, ['#', $n]];
            }
            return ['p', $row, [], $dent];
        }
        // `*…`
        if ('*' === \rtrim($row)) {
            return ['ul', $row, [], $dent, [$row[0], 2]];
        }
        if (0 === \strpos($row, '*')) {
            // `*[…`
            if (1 === \strpos($row, '[') && false === \strpos($row, '](') && false === \strpos($row, '][')) {
                return [1, $row, [], $dent];
            }
            // `***`
            $test = \strtr($row, [' ' => ""]);
            if (\strspn($test, '*') === ($n = \strlen($test)) && $n > 2) {
                return ['hr', $row, [], $dent, ['*', $n]];
            }
            // `* …`
            if (' ' === \substr($row, 1, 1)) {
                $r = \strspn($row, ' ', 1);
                return ['ul', $row, [], $dent, [$row[0], 1 + ($r > 3 ? 3 : $r)]];
            }
            return ['p', $row, [], $dent];
        }
        // `+`
        if ('+' === \rtrim($row)) {
            return ['ul', $row, [], $dent, [$row[0], 2]];
        }
        // `+…`
        if (0 === \strpos($row, '+')) {
            // `+ …`
            if (' ' === \substr($row, 1, 1)) {
                $r = \strspn($row, ' ', 1);
                return ['ul', $row, [], $dent, [$row[0], 1 + ($r > 3 ? 3 : $r)]];
            }
            return ['p', $row, [], $dent];
        }
        // `-`
        if ('-' === \rtrim($row)) {
            return ['ul', $row, [], $dent, [$row[0], 2]];
        }
        // `--`
        if ('--' === \rtrim($row)) {
            return ['h2', $row, [], $dent, ['-', 2]]; // Look like a Setext-header level 2
        }
        // `-…`
        if (0 === \strpos($row, '-')) {
            // `---`
            $test = \strtr($row, [' ' => ""]);
            if (\strspn($test, '-') === ($n = \strlen($test)) && $n > 2) {
                return ['hr', $row, [], $dent, ['-', $n]];
            }
            // `- …`
            if (' ' === \substr($row, 1, 1)) {
                $r = \strspn($row, ' ', 1);
                return ['ul', $row, [], $dent, [$row[0], 1 + ($r > 3 ? 3 : $r)]];
            }
            return ['p', $row, [], $dent];
        }
        // `:…`
        if (0 === \strpos($row, ':')) {
            // `: …`
            if (' ' === \substr($row, 1, 1)) {
                return ['dl', $row, [], $dent, [$row[0], 1 + \strspn($row, ' ', 1)]]; // Look like a definition list
            }
            return ['p', $row, [], $dent];
        }
        // `<…`
        if (0 === \strpos($row, '<') && ' ' !== ($row[1] ?? 0)) {
            // <https://spec.commonmark.org/0.31.2#html-blocks>
            if ($t = \rtrim(\strtok(\substr($row, 1), " \n>"), '/')) {
                // `<!--…`
                if (0 === \strpos($t, '!--')) {
                    return [false, $row, [], $dent, [2, \substr($t, 0, 3)]]; // `!--asdf` → `!--`
                }
                // `<![CDATA[…`
                if (0 === \strpos($t, '![CDATA[')) {
                    return [false, $row, [], $dent, [5, \substr($t, 0, 8)]]; // `![CDATA[asdf` → `![CDATA[`
                }
                if ('!' === $t[0]) {
                    return \preg_match('/^[a-z]/i', \substr($t, 1)) ? [false, $row, [], $dent, [4, $t]] : ['p', $row, [], $dent];
                }
                if ('?' === $t[0]) {
                    return [false, $row, [], $dent, [3, '?' . \trim($t, '?')]];
                }
                // The `:` and `@` character is not a valid part of a HTML element name, so it must be a link syntax
                // <https://spec.commonmark.org/0.30#tag-name>
                if (false !== \strpos($t, ':') || false !== \strpos($t, '@')) {
                    return ['p', $row, [], $dent];
                }
                if (false !== \stripos(',pre,script,style,textarea,', ',' . \trim($t, '/') . ',')) {
                    return [false, $row, [], $dent, [1, $t]];
                }
                if (false !== \stripos(',address,article,aside,base,basefont,blockquote,body,caption,center,col,colgroup,dd,details,dialog,dir,div,dl,dt,fieldset,figcaption,figure,footer,form,frame,frameset,h1,h2,h3,h4,h5,h6,head,header,hr,html,iframe,legend,li,link,main,menu,menuitem,nav,noframes,ol,optgroup,option,p,param,search,section,source,summary,table,tbody,td,tfoot,th,thead,title,tr,track,ul,', ',' . \trim($t, '/') . ',')) {
                    return [false, $row, [], $dent, [6, $t]];
                }
                // <https://spec.commonmark.org/0.31.2#example-163>
                if ('>' === \substr($test = \rtrim($row), -1)) {
                    // <https://spec.commonmark.org/0.31.2#closing-tag>
                    if ('/' === $test[1] && \preg_match('/^<\/[a-z][a-z\d-]*\s*>$/i', $test)) {
                        return [false, $row, [], $dent, [7, $t]];
                    }
                    // <https://spec.commonmark.org/0.31.2#open-tag>
                    if (\preg_match('/^<[a-z][a-z\d-]*(\s+[a-z:_][\w.:-]*(\s*=\s*(?>"[^"]*"|\'[^\']*\'|[^\s"\'<=>`]+)?)?)*\s*\/?>$/i', $test)) {
                        return [false, $row, [], $dent, [7, $t]];
                    }
                    return ['p', $row, [], $dent];
                }
            }
            return ['p', $row, [], $dent];
        }
        // `=…`
        if (0 === \strpos($row, '=')) {
            if (\strspn($row, '=') === \strlen($row)) {
                return ['h1', $row, [], $dent, ['=', 1]]; // Look like a Setext-header level 1
            }
            return ['p', $row, [], $dent];
        }
        // `>…`
        if (0 === \strpos($row, '>')) {
            return ['blockquote', $row, [], $dent];
        }
        // `[…`
        if (0 === \strpos($row, '[')) {
            if (1 === \strpos($row, '^')) {
                return [2, $row, [], $dent];
            }
            if (
                // `[asdf](…`
                \strpos($row, '](') > 0 ||
                // `[asdf][…`
                \strpos($row, '][') > 0 ||
                // `[asdf]` not followed by a `:`
                false !== ($n = \strpos($row, ']')) && ':' !== \substr($row, $n + 1, 1)
            ) {
                return ['p', $row, [], $dent];
            }
            return [0, $row, [], $dent];
        }
        // `_…`
        if (0 === \strpos($row, '_')) {
            // `___`
            $test = \strtr($row, [' ' => ""]);
            if (\strspn($test, '_') === ($n = \strlen($test)) && $n > 2) {
                return ['hr', $row, [], $dent, ['_', $n]];
            }
            return ['p', $row, [], $dent];
        }
        // ``…`
        if (0 === \strpos($row, '`') && ($n = \strspn($row, '`')) >= 3) {
            $info = \trim(\substr($row, $n));
            // <https://spec.commonmark.org/0.30#example-145>
            if (false !== \strpos($info, '`')) {
                return ['p', $row, [], $dent];
            }
            return ['pre', $row, a($info, true), $dent, [2, $n]];
        }
        // `~…`
        if (0 === \strpos($row, '~') && ($n = \strspn($row, '~')) >= 3) {
            $info = \trim(\substr($row, $n));
            return ['pre', $row, a($info, true), $dent, [3, $n]];
        }
        // `1…`
        $n = \strspn($row, '0123456789');
        // <https://spec.commonmark.org/0.30#example-266>
        if ($n > 9) {
            return ['p', $row, [], $dent];
        }
        // `1)` or `1.`
        if ($n && ($n + 1) === \strlen($v = \rtrim($row)) && false !== \strpos(').', \substr($v, -1))) {
            $start = (int) \substr($row, 0, $n);
            return ['ol', $row, ['start' => 1 !== $start ? $start : null], $dent, [\substr($row, -1), $n + 2, $start]];
        }
        // `1) …` or `1. …`
        if (false !== \strpos(').', \substr($row, $n, 1)) && ' ' === \substr($row, $n + 1, 1)) {
            $r = \strspn($row, ' ', $n + 1);
            $start = (int) \substr($row, 0, $n);
            return ['ol', $row, ['start' => 1 !== $start ? $start : null], $dent, [\substr($row, $n, 1), $n + 1 + ($r > 3 ? 3 : $r), $start]];
        }
        return ['p', $row, [], $dent];
    }
    function q(string $char = '"', $capture = false, string $before = "", string $x = ""): string {
        $a = \preg_quote($char[0], '/');
        $b = \preg_quote($char[1] ?? $char[0], '/');
        $c = $a . ($b === $a ? "" : $b);
        return '(?>' . $a . ($capture ? '(' : "") . '(?>' . ($before ? $before . '|' : "") . '[^' . $c . $x . '\\\\]|\\\\.)*+' . ($capture ? ')' : "") . $b . ')';
    }
    function r(string $char = '[]', $capture = false, string $before = "", string $x = ""): string {
        $a = \preg_quote($char[0], '/');
        $b = \preg_quote($char[1] ?? $char[0], '/');
        $c = $a . ($b === $a ? "" : $b);
        return '(?>' . $a . ($capture ? '(' : "") . '(?>' . ($before ? $before . '|' : "") . '[^' . $c . $x . '\\\\]|\\\\.|(?R))*+' . ($capture ? ')' : "") . $b . ')';
    }
    function raw(?string $value, $block = true): array {
        return $block ? rows($value) : row($value);
    }
    function row(?string $value, array &$lot = []) {
        if ("" === \trim($value ?? "")) {
            return [[], $lot];
        }
        $chops = [];
        $prev = ""; // Capture the previous chunk
        $is_image = isset($lot['is']['image']);
        $is_table = isset($lot['is']['table']);
        $notes = $lot['notes'] ?? [];
        while (false !== ($chop = \strpbrk($value, "\\" . '<`' . ($is_table ? '|' : "") . '*_![&' . "\n"))) {
            if ("" !== ($prev = \strstr($value, $chop[0], true))) {
                if (\is_array($abbr = abbr($prev, $lot))) {
                    $chops = \array_merge($chops, $abbr);
                } else {
                    $chops[] = e($prev);
                }
                $value = $chop;
            }
            if (0 === \strpos($chop, "\\")) {
                if ("\\" === \trim($chop)) {
                    $chops[] = $prev = "\\";
                    $value = "";
                    break;
                }
                // <https://spec.commonmark.org/0.30#example-644>
                if ("\n" === \substr($chop, 1, 1)) {
                    $chops[] = ['br', false, [], -1, ["\\", 1]];
                    $value = \ltrim(\substr($chop, 2));
                    $prev = "\\\n";
                    continue;
                }
                $chops[] = e($prev = \substr($chop, 1, 1));
                $value = \substr($chop, 2);
                continue;
            }
            if (0 === \strpos($chop, "\n")) {
                $prev = $chops[$last = \count($chops) - 1] ?? [];
                if (\is_string($prev) && '  ' === \substr($prev, -2)) {
                    $chops[$last] = $prev = \rtrim($prev);
                    $chops[] = ['br', false, [], -1, [' ', 2]];
                    $value = \ltrim(\substr($chop, 1));
                    continue;
                }
                $chops[] = ' ';
                $value = \substr($chop, 1);
                continue;
            }
            if (0 === \strpos($chop, '!')) {
                if (1 === \strpos($chop, '[')) {
                    $lot['is']['image'] = 1;
                    $row = row(\substr($chop, 1), $lot)[0][0];
                    unset($lot['is']['image']);
                    if (\is_array($row) && 'a' === $row[0]) {
                        $row[0] = 'img';
                        if (\is_array($row[1])) {
                            $alt = "";
                            foreach ($row[1] as $v) {
                                // <https://spec.commonmark.org/0.30#example-573>
                                if (\is_array($v) && 'img' === $v[0]) {
                                    $alt .= $v[2]['alt'] ?? "";
                                    continue;
                                }
                                $alt .= \is_array($v) ? s($v) : $v;
                            }
                        } else {
                            $alt = $row[1];
                        }
                        $row[1] = false;
                        // <https://spec.commonmark.org/0.30#example-572>
                        $row[2]['alt'] = \trim(\strip_tags($alt));
                        $row[2]['src'] = $row[2]['href'];
                        $row[4][1] = '!' . $row[4][1];
                        unset($row[2]['href'], $row[2]['rel'], $row[2]['target']);
                        $chops[] = $row;
                        $value = \substr($chop, \strlen($prev = $row[4][1]));
                        continue;
                    }
                }
                $chops[] = $prev = '!';
                $value = \substr($chop, 1);
                continue;
            }
            if (0 === \strpos($chop, '&')) {
                if (false === ($n = \strpos($chop, ';')) || $n < 2 || !\preg_match('/^&(?>#x[a-f\d]{1,6}|#\d{1,7}|[a-z][a-z\d]{1,31});/i', $chop, $m)) {
                    $chops[] = e($prev = '&');
                    $value = \substr($chop, 1);
                    continue;
                }
                // <https://spec.commonmark.org/0.30#example-26>
                if ('&#0;' === $m[0]) {
                    $m[0] = '&#xfffd;';
                }
                $chops[] = ['&', $m[0], [], -1];
                $value = \substr($chop, \strlen($prev = $m[0]));
                continue;
            }
            // <https://spec.commonmark.org/0.30#emphasis-and-strong-emphasis>
            if (\strlen($chop) > 2 && false !== \strpos('*_', $c = $chop[0])) {
                $contains = '`[^`]+`|[^' . $c . ($is_table ? '|' : "") . '\\\\]|\\\\.';
                if ('*' === $c) {
                    // Inner strong and emphasis
                    $b0 = '(?>[*]{2}(?![\p{P}\p{S}\s])|(?<=[\p{P}\p{S}\s])[*]{2}(?=[\p{P}\p{S}]))(?>' . $contains . ')+?(?>(?<![\p{P}\p{S}\s])[*]{2}|(?<=[\p{P}\p{S}])[*]{2}(?=[\p{P}\p{S}\s]))';
                    $i0 = '(?>[*](?![\p{P}\p{S}\s])|(?<=[\p{P}\p{S}\s])[*](?=[\p{P}\p{S}]))(?>' . $contains . ')+?(?>(?<![\p{P}\p{S}\s])[*]|(?<=[\p{P}\p{S}])[*](?=[\p{P}\p{S}\s]))';
                    // Outer strong and emphasis (strict)
                    $b1 = '(?>[*]{2}(?![\p{P}\p{S}\s])|(?<=^|[\p{P}\p{S}\s])[*]{2}(?=[\p{P}\p{S}]))(?>' . $contains . '|' . $b0 . '|' . $i0 . ')+?(?>(?<![\p{P}\p{S}\s])[*]{2}(?![*]+[^\p{P}\p{S}\s])|(?<=[\p{P}\p{S}])[*]{2}(?![*])(?=[\p{P}\p{S}\s]|$))';
                    $i1 = '(?>[*](?![\p{P}\p{S}\s])|(?<=^|[\p{P}\p{S}\s])[*](?=[\p{P}\p{S}]))(?>' . $contains . '|' . $b0 . '|' . $i0 . ')+?(?>(?<![\p{P}\p{S}\s])[*](?![*]+[^\p{P}\p{S}\s])|(?<=[\p{P}\p{S}])[*](?![*])(?=[\p{P}\p{S}\s]|$))';
                } else {
                    // Outer strong and emphasis (strict)
                    $b1 = '(?<=^|[\p{P}\p{S}\s])[_]{2}(?!\s)(?>' . $contains . '|(?<![\p{P}\p{S}\s])[_]+(?![\p{P}\p{S}\s])|(?R))+?(?<!\s)[_]{2}(?![_]+[^\p{P}\p{S}\s])(?=[\p{P}\p{S}\s]|$)';
                    $i1 = '(?<=^|[\p{P}\p{S}\s])[_](?!\s)(?>' . $contains . '|(?<![\p{P}\p{S}\s])[_]+(?![\p{P}\p{S}\s])|(?R))+?(?<!\s)[_](?![_]+[^\p{P}\p{S}\s])(?=[\p{P}\p{S}\s]|$)';
                }
                $n = \strlen($before = \substr($prev, -1)); // Either `0` or `1`
                // Test this pattern against the current chop plus the previous character that came before it to verify
                // if this chop is a left flank or not <https://spec.commonmark.org/0.30#left-flanking-delimiter-run>
                if (\preg_match('/(?>' . $b1 . '|' . $i1 . ')/u', $before . $chop, $m, \PREG_OFFSET_CAPTURE)) {
                    $current = $m[0][0];
                    if ($m[0][1] > $n) {
                        $chops[] = e($prev = \substr($chop, 0, $m[0][1] - $n));
                        $value = $chop = \substr($chop, $m[0][1] - $n);
                    }
                    // <https://spec.commonmark.org/0.30#example-520>
                    if (false !== ($n = \strpos($current, '[')) && (false === \strpos($current, ']') || !\preg_match('/' . r('[]') . '/', $current))) {
                        $chops[] = e($prev = \substr($chop, 0, $n));
                        $value = \substr($chop, $n);
                        continue;
                    }
                    $x = \min($n = \strspn($current, $c), \strspn(\strrev($current), $c));
                    if (0 === $x % 2) {
                        $v = row(\substr($current, 2, -2), $lot)[0];
                        // Hot fix for case `****asdf**asdf**` (case `**asdf**asdf****` works just fine)
                        if (isset($v[0][0], $v[1][0]) && 'strong' === $v[0][0] && \is_string($v[1][0]) && !\preg_match('/[\p{P}\p{S}\s]/u', $v[1][0])) {
                            $chops[] = $prev = \substr($chop, 0, $n);
                            $value = \substr($chop, $n);
                            continue;
                        }
                        $chops[] = ['strong', $v, [], -1, [$c, 2]];
                        $value = \substr($chop, \strlen($current));
                        continue;
                    }
                    $v = row(\substr($current, 1, -1), $lot)[0];
                    // Hot fix for case `**asdf*asdf*` (case `*asdf*asdf**` works just fine)
                    if (isset($v[0][0], $v[1][0]) && 'em' === $v[0][0] && \is_string($v[1][0]) && !\preg_match('/[\p{P}\p{S}\s]/u', $v[1][0])) {
                        $chops[] = $prev = \substr($chop, 0, $n);
                        $value = \substr($chop, $n);
                        continue;
                    }
                    $chops[] = ['em', $v, [], -1, [$c, 1]];
                    $value = \substr($chop, \strlen($current));
                    continue;
                }
                $chops[] = $prev = $c;
                $value = \substr($chop, 1);
                continue;
            }
            if (0 === \strpos($chop, '<')) {
                // <https://spec.commonmark.org/0.31.2#processing-instruction>
                if (0 === \strpos($chop, '<' . '?') && ($n = \strpos($chop, '?' . '>')) > 1) {
                    $v = \strtr($prev = \substr($chop, 0, $n += 2), "\n", ' ');
                    $chops[] = [false, $v, [], -1, [3, '?' . \trim(\strtok(\substr($v, 1), ' >'), '?')]];
                    $value = \substr($chop, $n);
                    continue;
                }
                // <https://spec.commonmark.org/0.31.2#html-comment>
                if (0 === \strpos($chop, '<!--') && ($n = \strpos($chop, '-->')) > 1) {
                    $chops[] = [false, \strtr($prev = \substr($chop, 0, $n += 3), "\n", ' '), [], -1, [2, '!--']];
                    $value = \substr($chop, $n);
                    continue;
                }
                // <https://spec.commonmark.org/0.31.2#cdata-section>
                if (0 === \strpos($chop, '<![CDATA[') && ($n = \strpos($chop, ']]>')) > 8) {
                    $chops[] = [false, \strtr($prev = \substr($chop, 0, $n += 3), "\n", ' '), [], -1, [5, '![CDATA[']];
                    $value = \substr($chop, $n);
                    continue;
                }
                // <https://spec.commonmark.org/0.31.2#declaration>
                if (0 === \strpos($chop, '<!') && ($n = \strpos($chop, '>')) > 2 && \preg_match('/^[a-z]/i', \substr($chop, 2))) {
                    $v = \strtr($prev = \substr($chop, 0, $n += 1), "\n", ' ');
                    $chops[] = [false, $v, [], -1, [4, \rtrim(\strtok(\substr($v, 1), ' >'), '/')]];
                    $value = \substr($chop, $n);
                    continue;
                }
                $test = (string) \strstr($chop, '>', true);
                // <https://github.com/commonmark/commonmark.js/blob/df3ea1e80d98fce5ad7c72505f9230faa6f23492/lib/inlines.js#L73>
                if (\strpos($test, '@') > 0 && \preg_match('/^<([a-z\d!#$%&\'*+.\/=?^_`{|}~-]+@[a-z\d](?>[a-z\d-]{0,61}[a-z\d])?(?>\.[a-z\d](?>[a-z\d-]{0,61}[a-z\d])?)*)>/i', $chop, $m)) {
                    $prev = $m[0];
                    // <https://spec.commonmark.org/0.30#example-605>
                    if (false !== \strpos($email = $m[1], '\\')) {
                        $chops[] = e($prev);
                        $value = \substr($chop, \strlen($prev));
                        continue;
                    }
                    $chops[] = ['a', e($m[1]), ['href' => u('mailto:' . $email)], -1, [false, $prev]];
                    $value = \substr($chop, \strlen($prev));
                    continue;
                }
                // <https://github.com/commonmark/commonmark.js/blob/df3ea1e80d98fce5ad7c72505f9230faa6f23492/lib/inlines.js#L75>
                if (\strpos($test, ':') > 1 && \preg_match('/^<([a-z][a-z\d.+-]{1,31}:[^<>' . ($is_table ? '|' : "") . '\x00-\x20]*)>/i', $chop, $m)) {
                    if (l($m[1])) {
                        $rel = $target = null;
                    } else {
                        $rel = 'nofollow';
                        $target = '_blank';
                    }
                    $chops[] = ['a', e($m[1]), [
                        'href' => u($m[1]),
                        'rel' => $rel,
                        'target' => $target
                    ], -1, [false, $prev = $m[0]]];
                    $value = \substr($chop, \strlen($prev));
                    continue;
                }
                // <https://spec.commonmark.org/0.31.2#closing-tag>
                if ('/' === $chop[1] && \preg_match('/^<(\/[a-z][a-z\d-]*)\s*>/i', $chop, $m)) {
                    $chops[] = [false, $m[0], [], -1, [7, $m[1]]];
                    $value = \substr($chop, \strlen($prev = $m[0]));
                    continue;
                }
                // <https://spec.commonmark.org/0.31.2#open-tag>
                if (\preg_match('/^<([a-z][a-z\d-]*)(\s+[a-z:_][\w.:-]*(\s*=\s*(?>"[^"]*"|\'[^\']*\'|[^\s"\'<=>`]+)?)?)*\s*\/?>/i', $chop, $m)) {
                    $chops[] = [false, $m[0], [], -1, [7, $m[1]]];
                    $value = \substr($chop, \strlen($prev = $m[0]));
                    continue;
                }
                $chops[] = e($prev = '<');
                $value = \substr($chop, 1);
                continue;
            }
            if (0 === \strpos($chop, '[')) {
                $data = $key = $link = $title = null;
                // <https://spec.commonmark.org/0.30#example-342>
                $contains = '`[^`]+`';
                // `[asdf]…`
                if (\preg_match('/' . r('[]', true, $contains, $is_table ? '|' : "") . '/', $chop, $m, \PREG_OFFSET_CAPTURE)) {
                    $prev = $m[0][0];
                    if ($m[0][1] > 0) {
                        $chops[] = e(\substr($chop, 0, $m[0][1]));
                        $value = $chop = \substr($chop, $m[0][1]);
                    }
                    // <https://spec.commonmark.org/0.30#example-517>
                    if (!$is_image && \preg_match('/(?<!!)' . q('[]', true, $contains, $is_table ? '|' : "") . '/', $prev, $n, \PREG_OFFSET_CAPTURE) && $n[0][1] > 0) {
                        if (false !== \strpos($prev, '](') || false !== \strpos($prev, '][') || isset($lot[0][\trim(\strtolower($n[1][0]))])) {
                            $chops[] = e($prev = \substr($prev, 0, $n[0][1]));
                            $value = \substr($chop, \strlen($prev));
                            continue;
                        }
                    }
                    $value = $chop = \substr($chop, \strlen($prev));
                    // `[^asdf]`
                    if (0 === \strpos($m[1][0], '^') && ("" === $chop || false === \strpos('([', $chop[0]))) {
                        if (!isset($lot[2][$key = \trim(\substr($m[1][0], 1))])) {
                            $chops[] = e($prev);
                            continue;
                        }
                        $notes[$key] = ($notes[$key] ?? 0) + 1;
                        $chops[] = ['sup', [['a', (string) \count($notes), [
                            'href' => '#to:' . $key,
                            'role' => 'doc-noteref'
                        ], -1, [false, $prev]]], [
                            'id' => 'from:' . $key . ($notes[$key] > 1 ? '.' . $notes[$key] : "")
                        ], -1, [$key, $prev]];
                        $lot['notes'] = $notes;
                        continue;
                    }
                    $row = row($m[1][0], $lot)[0];
                    // `…(asdf)`
                    if (0 === \strpos($chop, '(') && \preg_match('/' . r('()', true, q('<>'), $is_table ? '|' : "") . '/', $chop, $n, \PREG_OFFSET_CAPTURE)) {
                        $prev = $n[0][0];
                        // `[asdf]()`
                        if ("" === ($n[1][0] = \trim($n[1][0] ?? ""))) {
                            $link = "";
                        // `[asdf](<>)`
                        } else if ('<>' === $n[1][0]) {
                            $link = "";
                        // `[asdf](<asdf>)`
                        } else if ('<' === $n[1][0][0]) {
                            if (false !== ($v = \strstr(\substr($n[1][0], 1), '>', true))) {
                                $link = $v;
                                $title = \trim(\substr($n[1][0], \strlen($v) + 2));
                                $title = "" !== $title ? $title : null;
                            } else {
                                $link = $n[1][0];
                            }
                        // `[asdf](asdf …)`
                        } else if (\strpos($n[1][0], ' ') > 0 || \strpos($n[1][0], "\n") > 0) {
                            $link = \trim(\strtok($n[1][0], " \n"));
                            $title = \trim(\strpbrk($n[1][0], " \n"));
                        // `[asdf](asdf)`
                        } else {
                            $link = $n[1][0];
                        }
                        // <https://spec.commonmark.org/0.30#example-490>
                        // <https://spec.commonmark.org/0.30#example-492>
                        // <https://spec.commonmark.org/0.30#example-493>
                        if (false !== \strpos($link, "\n") || '\\' === \substr($link, -1) || 0 === \strpos($link, '<')) {
                            $chops[] = e(v(\strtr($m[0][0] . $n[0][0], "\n", ' ')));
                            $value = \substr($chop, \strlen($n[0][0]));
                            continue;
                        }
                        if (\is_string($title) && "" !== $title) {
                            // `[asdf](asdf "asdf")` or `[asdf](asdf 'asdf')` or `[asdf](asdf (asdf))`
                            $a = $title[0];
                            $b = \substr($title, -1);
                            if (('"' === $a && '"' === $b || "'" === $a && "'" === $b || '(' === $a && ')' === $b) && \preg_match('/^' . q($a . $b) . '$/', $title)) {
                                $title = v(d(\substr($title, 1, -1)));
                            // `[asdf](asdf asdf)`
                            // <https://spec.commonmark.org/0.30#example-487>
                            } else {
                                $chops[] = e(v(\strtr($m[0][0] . $n[0][0], "\n", ' ')));
                                $value = \substr($chop, \strlen($n[0][0]));
                                continue;
                            }
                        }
                        $key = false;
                        $value = $chop = \substr($chop, \strlen($n[0][0]));
                    // `…[]` or `…[asdf]`
                    } else if (0 === \strpos($chop, '[') && \preg_match('/' . r('[]', true, "", $is_table ? '|' : "") . '/', $chop, $n, \PREG_OFFSET_CAPTURE)) {
                        $key = \trim(\strtolower("" === $n[1][0] ? $m[1][0] : $n[1][0]));
                        $value = $chop = \substr($chop, \strlen($prev = $n[0][0]));
                        if (!isset($lot[0][$key])) {
                            $chops[] = e($prev = $m[0][0] . $n[0][0]);
                            continue;
                        }
                    }
                    $key = $key ?? \trim(\strtolower($m[1][0]));
                    if (\is_string($key) && !isset($lot[0][$key])) {
                        $chops[] = e($prev);
                        continue;
                    }
                    // …{asdf}
                    if (0 === \strpos(\trim($chop), '{') && \preg_match('/^\s*(' . q('{}', false, q('"') . '|' . q("'"), $is_table ? '|' : "") . ')/', $chop, $o)) {
                        if ("" !== \trim(\substr($o[1], 1, -1))) {
                            $data = \array_replace($data ?? [], a($o[1], true));
                            $value = \substr($chop, \strlen($o[0]));
                        }
                    }
                    if (false !== $key) {
                        $data = \array_replace($lot[0][$key][2] ?? [], $data ?? []);
                        $link = $lot[0][$key][0] ?? null;
                        $title = $lot[0][$key][1] ?? null;
                    }
                    if (!l($link)) {
                        $data['rel'] = $data['rel'] ?? 'nofollow';
                        $data['target'] = $data['target'] ?? '_blank';
                    }
                    $chops[] = ['a', $row, \array_replace([
                        'href' => u(v($link)),
                        'title' => $title
                    ], $data ?? []), -1, [$key, $prev = $m[0][0] . ($n[0][0] ?? "") . ($o[0] ?? "")]];
                    continue;
                }
                $chops[] = $prev = '[';
                $value = \substr($chop, 1);
                continue;
            }
            if (0 === \strpos($chop, $c = '`')) {
                if (\preg_match('/^(`+)(?![`])(.+?)(?<![`])\1(?![`])/s', $chop, $m)) {
                    // <https://spec.commonmark.org/0.31.2#code-spans>
                    $raw = \strtr($m[2], "\n", ' ');
                    if ("" !== \trim($raw) && ' ' === $raw[0] && ' ' === \substr($raw, -1)) {
                        $raw = \substr($raw, 1, -1);
                    }
                    $chops[] = ['code', e($raw), [], -1, [$c, \strlen($m[1])]];
                    $value = \substr($chop, \strlen($prev = $m[0]));
                    continue;
                }
                $chops[] = $prev = \str_repeat($c, $n = \strspn($chop, $c));
                $value = \substr($chop, $n);
                continue;
            }
            if ($is_table && 0 === \strpos($chop, '|')) {
                $chops[] = [false, '|', [], -1];
                $value = \substr($chop, 1);
                continue;
            }
            if (\is_array($abbr = abbr($prev = $chop, $lot))) {
                $chops = \array_merge($chops, $abbr);
            } else {
                $chops[] = e($prev);
            }
            $value = "";
        }
        if ("" !== $value) {
            if (\is_array($abbr = abbr($prev = $value, $lot))) {
                $chops = \array_merge($chops, $abbr);
            } else {
                $chops[] = e($prev);
            }
        }
        return [m($chops), $lot];
    }
    function rows(?string $value, array &$lot = [], int $level = 0): array {
        // List of reference(s), abbreviation(s), and note(s)
        $lot = \array_replace([[], [], []], $lot);
        if ("" === \trim($value ?? "")) {
            return [[], $lot];
        }
        // Normalize line break(s)
        $value = \trim(\strtr($value, [
            "\r\n" => "\n",
            "\r" => "\n"
        ]), "\n");
        $block = -1;
        $blocks = [];
        $rows = \explode("\n", $value);
        foreach ($rows as $row) {
            // Normalize tab(s) to pad(s)
            $row = \rtrim($row, "\t");
            while (false !== ($pad = \strstr($row, "\t", true))) {
                $row = $pad . \str_repeat(' ', 4 - ($v = \strlen($pad)) % 4) . \substr($row, $v + 1);
            }
            $current = of($row);
            if ($prev = $blocks[$block] ?? 0) {
                // Previous block is a code block
                if ('pre' === $prev[0]) {
                    if (2 === $prev[4][0] || 3 === $prev[4][0]) {
                        // End of the code block
                        if ('pre' === $current[0] && $prev[4][0] === $current[4][0] && $prev[4][1] === $current[4][1]) {
                            $fence = \rtrim(\strstr($current[1], "\n", true) ?: $current[1]);
                            // End of the code block cannot have an info string
                            if (\strlen($fence) !== $current[4][1]) {
                                $blocks[$block][1] .= "\n" . $current[1];
                                continue;
                            }
                            $blocks[$block++][1] .= "\n" . $current[1];
                            continue;
                        }
                        // Continue the code block…
                        $blocks[$block][1] .= "\n" . $row;
                        continue;
                    }
                    // End of the code block
                    if (null !== $current[0] && 'pre' !== $current[0]) {
                        if ("\n" === \substr($v = \rtrim($prev[1], ' '), -1)) {
                            $blocks[$block][1] = \substr($v, 0, -1);
                        }
                        $blocks[++$block] = $current;
                        continue;
                    }
                    // Continue the code block…
                    $blocks[$block][1] .= "\n" . $current[1];
                    continue;
                }
                // Previous block is a raw block
                if (false === $prev[0]) {
                    if (1 === $prev[4][0]) {
                        if (false !== \strpos($prev[1], '</' . $prev[4][1] . '>')) {
                            if (null === $current[0]) {
                                $block += 1;
                                continue;
                            }
                            $blocks[++$block] = $current;
                            continue;
                        }
                        if (null !== $current[0] && false !== \strpos($current[1], '</' . $prev[4][1] . '>')) {
                            $blocks[$block++][1] .= "\n" . $row;
                            continue;
                        }
                        $blocks[$block][1] .= "\n" . $row;
                        continue;
                    }
                    if (2 === $prev[4][0]) {
                        if (false !== \strpos($prev[1], '-->')) {
                            if (null === $current[0]) {
                                $block += 1;
                                continue;
                            }
                            $blocks[++$block] = $current;
                            continue;
                        }
                        if (null !== $current[0] && false !== \strpos($current[1], '-->')) {
                            $blocks[$block++][1] .= "\n" . $row;
                            continue;
                        }
                        $blocks[$block][1] .= "\n" . $row;
                        continue;
                    }
                    if (3 === $prev[4][0]) {
                        if (false !== \strpos($prev[1], '?' . '>')) {
                            if (null === $current[0]) {
                                $block += 1;
                                continue;
                            }
                            $blocks[++$block] = $current;
                            continue;
                        }
                        if (null !== $current[0] && false !== \strpos($current[1], '?' . '>')) {
                            $blocks[$block++][1] .= "\n" . $row;
                            continue;
                        }
                        $blocks[$block][1] .= "\n" . $row;
                        continue;
                    }
                    if (4 === $prev[4][0]) {
                        if (false !== \strpos($prev[1], '>')) {
                            if (null === $current[0]) {
                                $block += 1;
                                continue;
                            }
                            $blocks[++$block] = $current;
                            continue;
                        }
                        if (null !== $current[0] && false !== \strpos($current[1], '>')) {
                            $blocks[$block++][1] .= "\n" . $row;
                            continue;
                        }
                        $blocks[$block][1] .= "\n" . $row;
                        continue;
                    }
                    if (5 === $prev[4][0]) {
                        if (false !== \strpos($prev[1], ']]>')) {
                            if (null === $current[0]) {
                                $block += 1;
                                continue;
                            }
                            $blocks[++$block] = $current;
                            continue;
                        }
                        if (null !== $current[0] && false !== \strpos($current[1], ']]>')) {
                            $blocks[$block++][1] .= "\n" . $row;
                            continue;
                        }
                        $blocks[$block][1] .= "\n" . $row;
                        continue;
                    }
                    if (null === $current[0]) {
                        $block += 1;
                        continue;
                    }
                    $blocks[$block][1] .= "\n" . $row;
                    continue;
                }
                // Previous block is an abbreviation, note, or reference
                if (\is_int($prev[0])) {
                    if (2 === $prev[0]) {
                        // Must have at least 1 white-space after the `]:`
                        if (false !== ($n = \strpos($prev[1], ']:')) && "\\" !== \substr($prev[1], $n - 1, 1) && false === \strpos(" \n", \substr($prev[1], $n + 2, 1))) {
                            $blocks[$block][0] = 'p';
                            if (null === $current[0]) {
                                $block += 1;
                                continue;
                            }
                            $blocks[$block][1] .= "\n" . $current[1];
                            continue;
                        }
                        // End of the note block
                        if (null !== $current[0] && $current[3] <= $prev[3]) {
                            if ("\n" === \substr($v = \rtrim($prev[1], ' '), -1)) {
                                $blocks[$block][1] = $v = \substr($v, 0, -1);
                            } else {
                                // Lazy note block?
                                if ('p' === $current[0]) {
                                    $blocks[$block][1] .= "\n" . $current[1];
                                    continue;
                                }
                            }
                            // Note block must not be empty
                            if (']:' === \substr($v, -2) && "\\" !== \substr($v, -3, 1)) {
                                $blocks[$block++][0] = 'p';
                            }
                            $blocks[++$block] = $current;
                            continue;
                        }
                        // Continue the note block…
                        $blocks[$block][1] .= "\n" . $row;
                        continue;
                    }
                    if (\is_int($current[0])) {
                        $blocks[++$block] = $current;
                        continue;
                    }
                    if (null === $current[0]) {
                        if (false === ($n = \strpos($prev[1], ']:')) || "\\" === \substr($prev[1], $n - 1, 1)) {
                            $blocks[$block][0] = 'p';
                        }
                        $block += 1;
                        continue;
                    }
                    $blocks[$block][1] .= "\n" . $row;
                    continue;
                }
                // Previous block is a figure block
                if ('figure' === $prev[0]) {
                    // End of the figure block
                    if (null !== $current[0] && $current[3] <= $prev[3]) {
                        $blocks[++$block] = $current;
                        continue;
                    }
                    if ($current[3] > $prev[3] || "" === $current[1]) {
                        $row = \substr($row, 1);
                        $blocks[$block][4] = isset($prev[4]) ? $prev[4] . "\n" . $row : $row;
                        continue;
                    }
                    // Continue the figure block…
                    $blocks[$block][1] .= "\n" . $current[1];
                    continue;
                }
                // Previous block is a table block
                if ('table' === $prev[0]) {
                    if (null === $current[0] || $current[3] > $prev[3]) {
                        $blocks[$block][1] .= "\n"; // End of the table block, prepare to exit the table block
                        $blocks[$block][4] = isset($prev[4]) ? $prev[4] . "\n" . $row : $row;
                        continue;
                    }
                    // Continue the table block if previous table block does not end with a blank line
                    if ('table' === $current[0] && "\n" !== \substr(\rtrim($prev[1], ' '), -1)) {
                        $blocks[$block][1] .= "\n" . $current[1];
                        continue;
                    }
                    // End of the table block
                    $blocks[++$block] = $current;
                    continue;
                }
                // Previous block is a header block or a rule block
                if ('h' === $prev[0][0]) {
                    if (null === $current[0]) {
                        $block += 1;
                        continue;
                    }
                    $blocks[++$block] = $current;
                    continue;
                }
                // Previous block is a definition list block
                if ('dl' === $prev[0]) {
                    // Continue the list block…
                    if (null === $current[0] || $current[3] >= $prev[3] + $prev[4][1]) {
                        $blocks[$block][1] .= "\n" . $row;
                        continue;
                    }
                    // <https://spec.commonmark.org/0.31.2#example-312>
                    if ($current[3] > 3) {
                        $blocks[$block][1] .= ' ' . \ltrim($current[1]);
                        continue;
                    }
                    if ('dl' === $current[0]) {
                        $blocks[$block][1] .= "\n" . \substr($row, $current[3]);
                        $blocks[$block][3] = $current[3];
                        continue;
                    }
                    // Lazy definition list?
                    if ("\n" !== \substr($v = \rtrim($prev[1], ' '), -1)) {
                        if ('h1' === $current[0] && '=' === $current[4][0]) {
                            $blocks[$block][1] .= ' ' . $current[1];
                            continue;
                        }
                        if ('p' === $current[0] && $v !== $prev[4][0]) {
                            $blocks[$block][1] .= "\n" . $row;
                            continue;
                        }
                    } else {
                        $blocks[$block][1] = \substr($v, 0, -1);
                    }
                    // End of the definition list block
                    $blocks[++$block] = $current;
                    continue;
                }
                // Previous block is a list block
                if ('ol' === $prev[0]) {
                    // Continue the list block…
                    if (null === $current[0] || $current[3] >= $prev[3] + $prev[4][1]) {
                        $blocks[$block][1] .= "\n" . $row;
                        continue;
                    }
                    // <https://spec.commonmark.org/0.31.2#example-312>
                    if ($current[3] > 3) {
                        $blocks[$block][1] .= ' ' . \ltrim($current[1]);
                        continue;
                    }
                    if ('ol' === $current[0]) {
                        // End of the list block
                        if ($current[4][0] !== $prev[4][0]) {
                            $blocks[++$block] = $current;
                            continue;
                        }
                        // Continue the list block…
                        if ($current[4][2] >= $prev[4][2]) {
                            $blocks[$block][1] .= "\n" . \substr($row, $current[3]);
                            $blocks[$block][3] = $current[3];
                            $blocks[$block][4][2] = $current[4][2];
                            continue;
                        }
                        // End of the list block
                        $blocks[++$block] = $current;
                        continue;
                    }
                    // Lazy list?
                    if ("\n" !== \substr($v = \rtrim($prev[1], ' '), -1)) {
                        if ('h1' === $current[0] && '=' === $current[4][0]) {
                            $blocks[$block][1] .= ' ' . $current[1];
                            continue;
                        }
                        if ('p' === $current[0] && $v !== $prev[4][2] . $prev[4][0]) {
                            $blocks[$block][1] .= "\n" . $row;
                            continue;
                        }
                    } else {
                        $blocks[$block][1] = \substr($v, 0, -1);
                    }
                    // End of the list block
                    $blocks[++$block] = $current;
                    continue;
                }
                // Previous block is a list block
                if ('ul' === $prev[0]) {
                    if (null === $current[0] || $current[3] >= $prev[3] + $prev[4][1]) {
                        $blocks[$block][1] .= "\n" . $row;
                        continue;
                    }
                    // <https://spec.commonmark.org/0.31.2#example-312>
                    if ($current[3] > 3) {
                        $blocks[$block][1] .= ' ' . \ltrim($current[1]);
                        continue;
                    }
                    if ('ul' === $current[0]) {
                        // End of the list block
                        if ($current[4][0] !== $prev[4][0]) {
                            $blocks[++$block] = $current;
                            continue;
                        }
                        $blocks[$block][1] .= "\n" . \substr($row, $current[3]);
                        $blocks[$block][3] = $current[3];
                        continue;
                    }
                    // Lazy list?
                    if ("\n" !== \substr($v = \rtrim($prev[1], ' '), -1)) {
                        if ('h1' === $current[0] && '=' === $current[4][0]) {
                            $blocks[$block][1] .= ' ' . $current[1];
                            continue;
                        }
                        if ('p' === $current[0] && $v !== $prev[4][0]) {
                            $blocks[$block][1] .= "\n" . $row;
                            continue;
                        }
                    } else {
                        $blocks[$block][1] = \substr($v, 0, -1);
                    }
                    // End of the list block
                    $blocks[++$block] = $current;
                    continue;
                }
                // Start a new tight raw block…
                if (false === $current[0] && 7 !== $current[4][0]) {
                    $blocks[++$block] = $current;
                    continue;
                }
                // Start a new tight block…
                if (\is_string($prev[0]) && \is_string($current[0]) && $prev[0] !== $current[0]) {
                    // Lazy quote?
                    if ('blockquote' === $prev[0] && '>' !== \rtrim($prev[1]) && 'p' === $current[0]) {
                        $blocks[$block][1] .= ' ' . $current[1];
                        continue;
                    }
                    if ('hr' === $current[0] && '-' === $current[4][0] && \strlen($current[1]) === \strspn($current[1], '-')) {
                        if ('p' !== $prev[0]) {
                            $blocks[++$block] = $current;
                            continue;
                        }
                        $blocks[$block][0] = 'h2';
                        $blocks[$block][1] .= "\n" . $current[1];
                        $blocks[$block][4] = ['-', 2];
                        continue;
                    }
                    if ('h1' === $current[0] && '=' === $current[4][0]) {
                        if ('p' !== $prev[0]) {
                            // <https://spec.commonmark.org/0.31.2#example-93>
                            $blocks[$block][1] .= ' ' . $current[1];
                            continue;
                        }
                        $blocks[$block][0] = 'h1';
                        $blocks[$block][1] .= "\n" . $current[1];
                        $blocks[$block][4] = ['=', 1];
                        continue;
                    }
                    if ('h2' === $current[0] && '-' === $current[4][0]) {
                        if ('p' !== $prev[0]) {
                            $blocks[$block][1] .= ' ' . $current[1];
                            continue;
                        }
                        $blocks[$block][0] = 'h2';
                        $blocks[$block][1] .= "\n" . $current[1];
                        $blocks[$block][4] = ['-', 2];
                        continue;
                    }
                    if ('pre' === $current[0] && 1 === $current[4][0] && 'p' === $prev[0]) {
                        // Contains a hard-break syntax, keep!
                        if ('  ' === \substr($prev[1], -2)) {
                            $blocks[$block][1] = \rtrim($prev[1]) . "  \n" . $current[1];
                            continue;
                        }
                        // Contains a hard-break syntax, keep!
                        if ("\\" === \substr($v = \rtrim($prev[1]), -1)) {
                            $blocks[$block][1] = $v . "\n" . $current[1];
                            continue;
                        }
                        $blocks[$block][1] .= ' ' . $current[1];
                        continue;
                    }
                    if ('dl' === $current[0] && 'p' === $prev[0]) {
                        $blocks[$block][0] = 'dl';
                        $blocks[$block][1] .= "\n" . $row;
                        $blocks[$block][4] = $current[4];
                        continue;
                    }
                    if ('ol' === $current[0]) {
                        if (\rtrim($current[1]) === $current[4][2] . $current[4][0]) {
                            $blocks[$block][1] .= "\n" . $row;
                            continue;
                        }
                        // <https://spec.commonmark.org/0.31.2#example-304>
                        if (1 !== $current[4][2]) {
                            $blocks[$block][1] .= "\n" . $row;
                            continue;
                        }
                    }
                    if ('ul' === $current[0] && \rtrim($current[1]) === $current[4][0]) {
                        if ('p' === $prev[0] && '-' === $current[4][0]) {
                            $blocks[$block][0] = 'h2';
                            $blocks[$block][1] .= "\n-";
                            $blocks[$block][4] = ['-', 2];
                            continue;
                        }
                        $blocks[$block][1] .= "\n" . $row;
                        continue;
                    }
                    $blocks[++$block] = $current;
                    continue;
                }
                // Current block is empty, start a new block…
                if (null === $current[0]) {
                    $block += 1;
                    continue;
                }
                // Continue the previous block…
                $blocks[$block][1] .= "\n" . $current[1];
                continue;
            }
            // Current block is empty, skip!
            if (null === $current[0]) {
                continue;
            }
            // Start a new block…
            $blocks[++$block] = $current;
        }
        foreach ($blocks as $k => &$v) {
            if (!\is_int($v[0])) {
                continue;
            }
            // `*[asdf]:`
            if (0 === \strpos($v[1], '*[') && \preg_match('/' . r('[]', true) . '/', \substr($v[1], 1), $m) && ':' === \substr($v[1], \strlen($m[0]) + 1, 1)) {
                // Remove abbreviation block from the structure
                unset($blocks[$k]);
                // Abbreviation is not a part of the CommonMark specification, but I will just assume it to behave
                // similar to the reference specification
                $key = \trim(\preg_replace('/\s+/', ' ', $m[1]));
                // Queue the abbreviation data to be used later
                $title = \trim(\substr($v[1], \strlen($m[0]) + 2));
                if (isset($lot[$v[0]][$key])) {
                    continue;
                }
                $lot[$v[0]][$key] = $title;
                continue;
            }
            // `[asdf]:` or `[^asdf]:`
            if (0 === \strpos($v[1], '[') && \preg_match('/' . r('[]', true) . '/', $v[1], $m) && ':' === \substr($v[1], \strlen($m[0]), 1)) {
                // `[^asdf]:`
                if (0 === \strpos($m[1], '^')) {
                    // Remove note block from the structure
                    unset($blocks[$k]);
                    $key = \trim(\strtolower(\preg_replace('/\s+/', ' ', \substr($m[1], 1))));
                    $note = \substr($v[1], \strlen($m[0]) + 1);
                    $d = \str_repeat(' ', \strlen($m[0]) + 1 + \strspn($note, ' '));
                    if (isset($lot[$v[0]][$key])) {
                        continue;
                    }
                    if ("" === \trim($note)) {
                        $v = ['p', row($v[1], $lot)[0], [], $v[3]];
                        continue;
                    }
                    if (false !== \strpos(" \n", $note[0])) {
                        $note = \substr($note, 1);
                    }
                    // Remove indent(s)
                    [$a, $b] = \explode("\n", $note . "\n", 2);
                    $note = \trim(\strtr("\n" . $a . "\n" . $b, [
                        "\n" . \str_repeat(' ', \strspn(\trim($b, "\n"), ' ')) => "\n"
                    ]), "\n");
                    // Queue the note data to be used later
                    $lot_of_note = [$lot[0], $lot[1]];
                    $lot[$v[0]][$key] = rows($note, $lot_of_note, $level + 1)[0];
                    continue;
                }
                $data = $key = $link = $title = null;
                if (\preg_match('/^\s*(' . q('<>') . '|\S+?)(?>\s+(' . q('"') . '|' . q("'") . '|' . q('()') . '))?(?>\s*(' . q('{}', false, q('"') . '|' . q("'")) . '))?\s*$/', \substr($v[1], \strlen($m[0]) + 1), $n)) {
                    // Remove reference block from the structure
                    unset($blocks[$k]);
                    // <https://spec.commonmark.org/0.30#matches>
                    $key = \trim(\strtolower(\preg_replace('/\s+/', ' ', $m[1])));
                    // <https://spec.commonmark.org/0.30#example-204>
                    if (isset($lot[$v[0]][$key])) {
                        continue;
                    }
                    if ($link = $n[1] ?? "") {
                        if ('<' === $link[0] && '>' === \substr($link, -1)) {
                            $link = \substr($link, 1, -1);
                            // <https://spec.commonmark.org/0.30#example-490>
                            // <https://spec.commonmark.org/0.30#example-492>
                            // <https://spec.commonmark.org/0.30#example-493>
                            if (false !== \strpos($link, "\n") || '\\' === \substr($link, -1) || 0 === \strpos($link, '<')) {
                                $v[0] = 'p';
                                $v[1] = row($v[1], $lot)[0];
                                continue;
                            }
                        }
                    }
                    if ("" !== ($title = $n[2] ?? "")) {
                        $a = $title[0];
                        $b = \substr($title, -1);
                        if (('"' === $a && '"' === $b || "'" === $a && "'" === $b || '(' === $a && ')' === $b) && \preg_match('/^' . q($a . $b) . '$/', $title)) {
                            $title = v(d(\substr($title, 1, -1)));
                        } else {
                            $v[0] = 'p';
                            $v[1] = row($v[1], $lot)[0];
                            continue;
                        }
                    } else {
                        $title = null;
                    }
                    if ($data = $n[3] ?? []) {
                        $data = a($n[3], true);
                    }
                    if (!l($link)) {
                        $data['rel'] = $data['rel'] ?? 'nofollow';
                        $data['target'] = $data['target'] ?? '_blank';
                    }
                    // Queue the reference data to be used later
                    $lot[$v[0]][$key] = [u(v($link)), $title, $data];
                    continue;
                }
                $v[0] = 'p';
                $v[1] = row($v[1], $lot)[0];
                continue;
            }
        }
        $blocks = \array_values($blocks);
        foreach ($blocks as $k => &$v) {
            if (false === $v[0]) {
                continue;
            }
            $next = $blocks[$k + 1] ?? 0;
            if ('p' === $v[0] && \is_array($next) && 'dl' === $next[0] && \strlen($next[1]) > 2 && ':' === $next[1][0] && ' ' === $next[1][1]) {
                $v[0] = 'dl';
                $v[1] .= "\n\n" . $next[1];
                $v[4] = $next[4];
                unset($blocks[$k + 1]);
                // Parse the definition list later
                continue;
            }
            if ('dl' === $v[0]) {
                // Must be a definition value without its term(s). Fall it back to the default block type!
                if (\strlen($v[1]) > 2 && ':' === $v[1][0] && ' ' === $v[1][1]) {
                    $v = ['p', $v[1], [], $v[3]];
                }
                // Parse the definition list later
                continue;
            }
            if ('blockquote' === $v[0]) {
                $v[1] = \substr(\strtr($v[1], ["\n>" => "\n"]), 1);
                $v[1] = \substr(\strtr("\n" . $v[1], ["\n " => "\n"]), 1); // Remove space
                $v[1] = rows($v[1], $lot, $level + 1)[0];
                continue;
            }
            if ('figure' === $v[0]) {
                $row = row(\trim($v[1], "\n"), $lot)[0];
                // The image syntax doesn’t seem to appear alone on a single line
                if (\is_array($row) && \count($row) > 1) {
                    if (!empty($v[4])) {
                        [$a, $b] = \explode("\n\n", $v[4] . "\n\n", 2);
                        $v = [false, \array_merge([['p', row(\trim($v[1] . "\n" . $a, "\n"), $lot)[0], [], 0]], rows(\trim($b, "\n"), $lot, $level + 1)[0]), [], $v[3]];
                        continue;
                    }
                    $v = ['p', $row, [], $v[3]];
                    continue;
                }
                if (!empty($v[4])) {
                    $b = \rtrim($v[4], "\n");
                    $caption = rows($b, $lot, $level + 1)[0];
                    if (0 !== \strpos($b, "\n") && false === \strpos($b, "\n\n") && \is_array($test = \reset($caption)) && 'p' === $test[0]) {
                        $caption = $test[1];
                    }
                    $row[] = ['figcaption', $caption, [], 0];
                }
                if (isset($row[0][0]) && 'img' === $row[0][0]) {
                    $row[0][3] = 0; // Mark as block
                }
                $v[1] = $row;
                unset($v[4]);
                continue;
            }
            if ('hr' === $v[0]) {
                $v[1] = false;
                continue;
            }
            if ('h' === $v[0][0]) {
                if ('#' === $v[4][0]) {
                    $v[1] = \trim(\substr($v[1], $v[4][1]));
                    // `# asdf {asdf=asdf}`
                    // `# asdf ## {asdf=asdf}`
                    if (\strpos($v[1], '{') > 0 && \preg_match('/' . q('{}', true, q('"') . '|' . q("'")) . '\s*$/', $v[1], $m, \PREG_OFFSET_CAPTURE)) {
                        if ("" !== \trim($m[1][0]) && '\\' !== \substr($v[1], $m[0][1] - 1, 1)) {
                            $v[1] = \rtrim(\substr($v[1], 0, $m[0][1]));
                            $v[2] = \array_replace($v[2], a(\rtrim($m[0][0]), true));
                        }
                    }
                    if ("" !== $v[1]) {
                        // Remove all decorative `#` at the end of the header text, but consider them as part of the
                        // header text if they are not preceded by a space, right after the actual header text
                        while ('#' === \substr($v[1], -1)) {
                            if (false === \strpos(' #', \substr($v[1], -2, 1))) {
                                break;
                            }
                            $v[1] = \substr($v[1], 0, -1);
                        }
                        $v[1] = \rtrim($v[1]);
                    }
                    // `# asdf {asdf=asdf} ##`
                    if (\strpos($v[1], '{') > 0 && \preg_match('/' . q('{}', true, q('"') . '|' . q("'")) . '\s*$/', $v[1], $m, \PREG_OFFSET_CAPTURE)) {
                        if ("" !== \trim($m[1][0]) && '\\' !== \substr($v[1], $m[0][1] - 1, 1)) {
                            $v[1] = \rtrim(\substr($v[1], 0, $m[0][1]));
                            $v[2] = \array_replace($v[2], a(\rtrim($m[0][0]), true));
                        }
                    }
                } else if (false !== \strpos('-=', $v[4][0])) {
                    if (false !== ($n = \strpos($v[1], "\n" . $v[4][0]))) {
                        $v[1] = \substr($v[1], 0, $n);
                        // `asdf {asdf=asdf}\n====`
                        if (\strpos($v[1], '{') > 0 && \preg_match('/' . q('{}', true, q('"') . '|' . q("'")) . '\s*$/', $v[1], $m, \PREG_OFFSET_CAPTURE)) {
                            if ("" !== \trim($m[1][0]) && '\\' !== \substr($v[1], $m[0][1] - 1, 1)) {
                                $v[1] = \rtrim(\substr($v[1], 0, $m[0][1]));
                                $v[2] = \array_replace($v[2], a(\rtrim($m[0][0]), true));
                            }
                        }
                    } else {
                        $v = ['p', $v[1], [], $v[3]];
                    }
                }
                $v[1] = row($v[1], $lot)[0];
                continue;
            }
            if ('ol' === $v[0]) {
                $list = \preg_split('/\n+(?=\d+[).](\s|$))/', $v[1]);
                $list_is_tight = false === \strpos($v[1], "\n\n");
                foreach ($list as &$vv) {
                    $vv = \substr(\strtr($vv, ["\n" . \str_repeat(' ', $v[4][1]) => "\n"]), \strlen($v[4][2] . $v[4][0]) + 1); // Remove indent(s)
                    $vv = rows($vv, $lot, $level + 1)[0];
                    if ($list_is_tight && \is_array($vv)) {
                        foreach ($vv as &$vvv) {
                            if (\is_array($vvv) && 'p' === $vvv[0]) {
                                $vvv[0] = false;
                            }
                        }
                        unset($vvv);
                    }
                    $vv = ['li', m($vv), [], 0];
                }
                unset($vv);
                $v[1] = $list;
                $v[4][] = !$list_is_tight;
                continue;
            }
            if ('pre' === $v[0]) {
                if (2 === $v[4][0] || 3 === $v[4][0]) {
                    $v[1] = \substr(\strstr($v[1], "\n"), 1);
                    if ("" !== ($fence = \trim(\strrchr($v[1], "\n") ?: $v[1], "\n"))) {
                        if (['`' => 2, '~' => 3][$fence[0]] === $v[4][0] && \strlen($fence) === $v[4][1]) {
                            $v[1] = \substr($v[1], 0, -$v[4][1]);
                        }
                    }
                    if ("\n" === \substr($v[1], -1) && "" !== \trim($v[1])) {
                        $v[1] = \substr($v[1], 0, -1);
                    }
                }
                $v[1] = [['code', e($v[1]), $v[2]]];
                $v[2] = [];
                continue;
            }
            if ('table' === $v[0]) {
                $table = [
                    ['thead', [['tr', [], [], 0]], 0],
                    ['tbody', [], [], 0]
                ];
                $rows = \explode("\n", \trim($v[1], "\n"));
                $headers = \trim(\array_shift($rows) ?? "", ' |');
                $styles = \trim(\array_shift($rows) ?? "", ' |');
                // Header-less table
                if (\strspn($headers, ' -:|') === \strlen($headers)) {
                    \array_unshift($rows, $styles);
                    $styles = $headers;
                    $headers = "";
                }
                // Missing table header line
                if ("" === $styles) {
                    $v = ['p', row($v[1], $lot)[0], [], $v[3]];
                    continue;
                }
                // Invalid table header line
                if (\strspn($styles, ' -:|') !== \strlen($styles)) {
                    $v = ['p', row($v[1], $lot)[0], [], $v[3]];
                    continue;
                }
                $styles = \explode('|', $styles);
                $styles_count = \count($styles);
                foreach ($styles as &$vv) {
                    $vv = \trim($vv);
                    if (':' === $vv[0] && ':' === \substr($vv, -1)) {
                        $vv = 'center';
                        continue;
                    }
                    if (':' === $vv[0]) {
                        $vv = 'left';
                        continue;
                    }
                    if (':' === \substr($vv, -1)) {
                        $vv = 'right';
                        continue;
                    }
                    $vv = null;
                }
                unset($vv);
                $lot['is']['table'] = 1;
                if ("" !== $headers) {
                    $th = [];
                    if (\is_array($headers = row($headers, $lot)[0])) {
                        $i = 0;
                        foreach ($headers as $vv) {
                            $th[$i] = $th[$i] ?? ['th', [], [], 0];
                            if (\is_array($vv)) {
                                if (false === $vv[0] && '|' === $vv[1]) {
                                    $i += 1;
                                    continue;
                                }
                                $th[$i][1][] = $vv;
                                continue;
                            }
                            $th[$i][1][] = $vv;
                        }
                        foreach ($th as $kk => &$vv) {
                            $vv[1] = m($vv[1]);
                            if (\is_array($vv[1])) {
                                if (\is_string(\reset($vv[1]))) {
                                    $vv[1][$kk = \key($vv[1])] = \ltrim($vv[1][$kk]);
                                }
                                if (\is_string(\end($vv[1]))) {
                                    $vv[1][$kk = \key($vv[1])] = \rtrim($vv[1][$kk]);
                                }
                            } else if (\is_string($vv[1])) {
                                $vv[1] = \trim($vv[1]);
                            }
                            if (isset($styles[$kk])) {
                                $vv[2]['style'] = 'text-align: ' . $styles[$kk] . ';';
                            }
                        }
                        unset($vv);
                    } else {
                        $th[] = ['th', \trim($headers), [], 0];
                    }
                    $table[0][1][0][1] = \array_pad(\array_slice($th, 0, $styles_count), $styles_count, ['th', "", [], 0]);
                }
                foreach ($rows as $row) {
                    $td = [];
                    if (\is_array($row = row(\trim($row, ' |'), $lot)[0])) {
                        $i = 0;
                        foreach ($row as $vv) {
                            $td[$i] = $td[$i] ?? ['td', [], [], 0];
                            if (\is_array($vv)) {
                                if (false === $vv[0] && '|' === $vv[1]) {
                                    $i += 1;
                                    continue;
                                }
                                $td[$i][1][] = $vv;
                                continue;
                            }
                            $td[$i][1][] = $vv;
                        }
                        foreach ($td as $kk => &$vv) {
                            $vv[1] = m($vv[1]);
                            if (\is_array($vv[1])) {
                                if (\is_string(\reset($vv[1]))) {
                                    $vv[1][$kk = \key($vv[1])] = \ltrim($vv[1][$kk]);
                                }
                                if (\is_string(\end($vv[1]))) {
                                    $vv[1][$kk = \key($vv[1])] = \rtrim($vv[1][$kk]);
                                }
                            } else if (\is_string($vv[1])) {
                                $vv[1] = \trim($vv[1]);
                            }
                            if (isset($styles[$kk])) {
                                $vv[2]['style'] = 'text-align: ' . $styles[$kk] . ';';
                            }
                        }
                        unset($vv);
                    } else {
                        $td[] = ['td', \trim($row), [], 0];
                    }
                    $table[1][1][] = ['tr', \array_pad(\array_slice($td, 0, $styles_count), $styles_count, ['td', "", [], 0]), [], 0];
                }
                unset($lot['is']['table']);
                // Remove empty `<thead>`
                if (empty($table[0][1][0][1])) {
                    unset($table[0]);
                }
                // Remove empty `<tbody>`
                if (empty($table[1][1])) {
                    unset($table[1]);
                }
                if (!empty($v[4])) {
                    $b = \rtrim($v[4], "\n");
                    $caption = rows($b, $lot, $level + 1)[0];
                    if (0 !== \strpos($b, "\n") && false === \strpos($b, "\n\n") && \is_array($test = \reset($caption)) && 'p' === $test[0]) {
                        $caption = $test[1];
                    }
                    \array_unshift($table, ['caption', $caption, [], 0]);
                }
                $v[1] = $table;
                unset($v[4]);
                continue;
            }
            if ('ul' === $v[0]) {
                $list = \preg_split('/\n+(?=[*+-](\s|$))/', $v[1]);
                $list_is_tight = false === \strpos($v[1], "\n\n");
                foreach ($list as &$vv) {
                    $vv = \substr(\strtr($vv, ["\n" . \str_repeat(' ', $v[4][1]) => "\n"]), 2); // Remove indent(s)
                    $vv = rows($vv, $lot, $level + 1)[0];
                    if ($list_is_tight && \is_array($vv)) {
                        foreach ($vv as &$vvv) {
                            if (\is_array($vvv) && 'p' === $vvv[0]) {
                                $vvv[0] = false;
                            }
                        }
                        unset($vvv);
                    }
                    $vv = ['li', m($vv), [], 0];
                }
                unset($vv);
                $v[1] = $list;
                $v[4][] = !$list_is_tight;
                continue;
            }
            if (\is_string($v[1])) {
                $v[1] = row(\rtrim($v[1]), $lot)[0];
            }
        }
        foreach ($blocks as &$v) {
            // Late definition list parsing
            if ('dl' === $v[0]) {
                $list = \preg_split('/\n+(?=:\s|[^:\s])/', $v[1]);
                $list_is_tight = false === \strpos($v[1], "\n\n");
                foreach ($list as &$vv) {
                    if (\strlen($vv) > 2 && ':' === $vv[0] && ' ' === $vv[1]) {
                        $vv = \substr(\strtr($vv, ["\n" . \str_repeat(' ', $v[4][1]) => "\n"]), 2); // Remove indent(s)
                        $vv = rows($vv, $lot, $level + 1)[0];
                        if ($list_is_tight && \is_array($vv)) {
                            foreach ($vv as &$vvv) {
                                if (\is_array($vvv) && 'p' === $vvv[0]) {
                                    $vvv[0] = false;
                                }
                            }
                            unset($vvv);
                        }
                        $vv = ['dd', m($vv), [], 0];
                        continue;
                    }
                    $vv = ['dt', row($vv)[0], [], 0];
                }
                unset($vv);
                $v[1] = $list;
                $v[4][] = !$list_is_tight;
            }
        }
        unset($v);
        $blocks = \array_values($blocks);
        if (!empty($lot[2]) && 0 === $level) {
            $notes = ['div', [
                ['hr', false, [], 0, '-'],
                ['ol', [], [], 0, [0, 1, '.']]
            ], [
                'role' => 'doc-endnotes'
            ], 0];
            foreach ($lot[2] as $k => $v) {
                if (!isset($lot['notes'][$k])) {
                    continue;
                }
                if (\is_array($v) && \is_array($last = \array_pop($v))) {
                    if ('p' === $last[0]) {
                        $last[1] = (array) $last[1];
                        for ($i = 0, $j = $lot['notes'][$k]; $i < $j; ++$i) {
                            $last[1][] = ['&', '&#160;', [], -1];
                            $last[1][] = ['a', [['&', '&#8617;', [], -1]], [
                                'href' => '#from:' . $k . ($i > 0 ? '.' . ($i + 1) : ""),
                                'role' => 'doc-backlink'
                            ], -1, [false, ""]];
                        }
                        $v[] = $last;
                    } else {
                        $v[] = $last;
                        $p = ['p', [], [], 0];
                        for ($i = 0, $j = $lot['notes'][$k]; $i < $j; ++$i) {
                            if ($i > 0) {
                                $p[1][] = ['&', '&#160;', [], -1];
                            }
                            $p[1][] = ['a', [['&', '&#8617;', [], -1]], [
                                'href' => '#from:' . $k . ($i > 0 ? '.' . ($i + 1) : ""),
                                'role' => 'doc-backlink'
                            ], -1, [false, ""]];
                        }
                        $v[] = $p;
                    }
                }
                $notes[1][1][1][] = ['li', $v, [
                    'id' => 'to:' . $k,
                    'role' => 'doc-endnote'
                ], 0];
            }
            if ($notes[1][1][1]) {
                $blocks['notes'] = $notes;
            }
            unset($lot['notes']);
        }
        unset($lot['is']);
        return [$blocks, $lot];
    }
    function s(array $data): ?string {
        [$t, $c, $a] = $data;
        if (false === $t) {
            if (\is_array($c)) {
                $out = "";
                foreach ($c as $v) {
                    $out .= \is_array($v) ? s($v) : $v;
                }
                return $out;
            }
            return $c;
        }
        if (\is_int($t)) {
            return "";
        }
        if ('&' === $t) {
            return e(d($c));
        }
        $out = '<' . $t;
        if (!empty($a) && \is_array($a)) {
            \ksort($a);
            foreach ($a as $k => $v) {
                if (false === $v || null === $v) {
                    continue;
                }
                $out .= ' ' . $k . (true === $v ? "" : '="' . e($v) . '"');
            }
        }
        if (false !== $c) {
            $out .= '>';
            if (\is_array($c)) {
                foreach ($c as $v) {
                    $out .= \is_array($v) ? s($v) : $v;
                }
            } else {
                $out .= $c;
            }
            $out .= '</' . $t . '>';
        } else {
            $out .= ' />';
        }
        return "" !== $out ? $out : null;
    }
    // <https://stackoverflow.com/a/6059053/1163000>
    function u(?string $v): string {
        return \strtr(\rawurlencode(d($v)), [
            '%21' => '!',
            '%23' => '#',
            '%24' => '$',
            '%26' => '&',
            '%27' => "'",
            '%28' => '(',
            '%29' => ')',
            '%2A' => '*',
            '%2B' => '+',
            '%2C' => ',',
            '%2D' => '-',
            '%2E' => '.',
            '%2F' => '/',
            '%3A' => ':',
            '%3B' => ';',
            '%3D' => '=',
            '%3F' => '?',
            '%40' => '@',
            '%5B' => '[',
            '%5D' => ']',
            '%5F' => '_',
            '%7E' => '~'
        ]);
    }
    // <https://spec.commonmark.org/0.30#example-12>
    function v(?string $value): string {
        return null !== $value ? \strtr($value, [
            "\\'" => "'",
            "\\\\" => "\\",
            '\!' => '!',
            '\"' => '"',
            '\#' => '#',
            '\$' => '$',
            '\%' => '%',
            '\&' => '&',
            '\(' => '(',
            '\)' => ')',
            '\*' => '*',
            '\+' => '+',
            '\,' => ',',
            '\-' => '-',
            '\.' => '.',
            '\/' => '/',
            '\:' => ':',
            '\;' => ';',
            '\<' => '<',
            '\=' => '=',
            '\>' => '>',
            '\?' => '?',
            '\@' => '@',
            '\[' => '[',
            '\]' => ']',
            '\^' => '^',
            '\_' => '_',
            '\`' => '`',
            '\{' => '{',
            '\|' => '|',
            '\}' => '}',
            '\~' => '~'
        ]) : "";
    }
}