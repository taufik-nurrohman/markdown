<?php namespace x\markdown;

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
        $pattern = '/([#.](?:\\\\[#.]|[\w:-])+|(?:[\w:.-]+(?:=(?:' . q('"') . '|' . q("'") . '|\S+)?)?))/';
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
        if ($class) {
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
        if ($out) {
            \sort($out);
            return ' ' . \implode(' ', $out);
        }
        return null;
    }
    foreach (\preg_split('/\s++|(?=[#.])/', $info, -1, \PREG_SPLIT_NO_EMPTY) as $v) {
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
    if ($class) {
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

// Takes out attribute(s) from its row
function at(string $row) {
    $at = [$row, []];
    if ("" === ($row = \rtrim($row))) {
        return $at;
    }
    // Must ends with `}` but not with `\}`
    if ('}' !== \substr($row, -1) || '\\}' === \substr($row, -2)) {
        return $at;
    }
    // Must contains `{` but not `\{`
    if (false === ($a = \strpos($row, '{')) || '\\' === \substr($row, $a - 1, 1)) {
        return $at;
    }
    if (\preg_match('/^(.+?)\s*(\{.+?\})\s*$/', $row, $m)) {
        if ("" === \trim(\substr($m[2], 1, -1))) {
            return $at;
        }
        $at[0] = $m[1];
        $at[1] = a($m[2], true);
    }
    return $at;
}

function convert(?string $content, array $lot = [], $block = true): ?string {
    $rows = rows($content, $lot);
    if (!$rows[0]) {
        return null;
    }
    if (!$block) {
        // TODO
    }
    foreach ($rows[0] as &$row) {
        $row = s($row);
    }
    $content = \implode("", $rows[0]);
    // Merge sequence of definition list into single definition list
    $content = \strtr($content, ['</dl><dl>' => ""]);
    return $content;
}

function data(?string $row): array {
    if ("" === ($row ?? "")) {
        return [null, $row, [], 0];
    }
    $dent = \strspn($row, ' ');
    $d = \str_repeat(' ', $dent);
    if ($dent >= 4) {
        $row = \substr(\strtr($row, ["\n" . $d => "\n"]), 4);
        return ['pre', $row, [], $dent];
    }
    // Remove indent(s)
    $row = \substr(\strtr($row, ["\n" . $d => "\n"]), $dent);
    if ("" === $row) {
        return [null, $row, [], $dent];
    }
    // `#…`
    if (0 === \strpos($row, '#')) {
        $n = \strspn($row, '#');
        // `#######…`
        if ($n > 6) {
            return ['p', $row, [], $dent];
        }
        // `# …`
        if ($n === \strpos($row, ' ')) {
            return ['h' . $n, $row, [], $dent, $n, '#'];
        }
        return ['p', $row, [], $dent];
    }
    // `*…`
    if ('*' === \rtrim($row)) {
        return ['ul', "", [], [$dent, 1], $row[0]];
    }
    if (0 === \strpos($row, '*')) {
        // `*[…`
        if (1 === \strpos($row, '[') && \strpos($row, ']:') > 2) {
            return [1, $row, [], $dent];
        }
        // `***`
        $test = \strtr($row, [' ' => ""]);
        if (\strspn($test, '*') === ($v = \strlen($test)) && $v > 2) {
            return ['hr', $row, [], $dent, '*'];
        }
        // `* …`
        if (1 === \strpos($row, ' ')) {
            return ['ul', $row, [], [$dent, 1 + \strspn($row, ' ', 1)], $row[0]];
        }
        return ['p', $row, [], $dent];
    }
    // `+`
    if ('+' === \rtrim($row)) {
        return ['ul', "", [], [$dent, 1], $row[0]];
    }
    // `+…`
    if (0 === \strpos($row, '+')) {
        // `+ …`
        if (1 === \strpos($row, ' ')) {
            return ['ul', $row, [], [$dent, 1 + \strspn($row, ' ', 1)], $row[0]];
        }
        return ['p', $row, [], $dent];
    }
    // `-`
    if ('-' === \rtrim($row)) {
        return ['ul', "", [], [$dent, 1], $row[0]];
    }
    // `--`
    if ('--' === \rtrim($row)) {
        return ['h2', $row, [], $dent, 2, '-']; // Look like a Setext-header level 2
    }
    // `-…`
    if (0 === \strpos($row, '-')) {
        // `---`
        $test = \strtr($row, [' ' => ""]);
        if (\strspn($test, '-') === ($v = \strlen($test)) && $v > 2) {
            return ['hr', $row, [], $dent, '-'];
        }
        // `- …`
        if (1 === \strpos($row, ' ')) {
            return ['ul', $row, [], [$dent, 1 + \strspn($row, ' ', 1)], $row[0]];
        }
        return ['p', $row, [], $dent];
    }
    // `:…`
    if (0 === \strpos($row, ':')) {
        // `: …`
        if (1 === \strpos($row, ' ')) {
            return ['dl', $row, [], $dent]; // Look like a part of a definition list
        }
        return ['p', $row, [], $dent];
    }
    // `<…`
    if (0 === \strpos($row, '<')) {
        // `<asdf…`
        if ($t = \rtrim(\strtok(\substr($row, 1), " \n\t>"), '/')) {
            // The `:` and `@` character is not a valid part of a HTML element name, so it must be a link syntax
            // <https://spec.commonmark.org/0.30#tag-name>
            if (\strpos($t, ':') > 0 || \strpos($t, '@') > 0) {
                return ['p', $row, [], $dent];
            }
            // `<![…`
            if (0 === \strpos($t, '![')) {
                $t = \substr($t, 0, \strrpos($t, '[') + 1); // `![CDATA[asdf` → `![CDATA[`
            }
            if (false !== \strpos('!?', $t[0])) {
                return [false, $row, [], $dent, $t];
            }
            // <https://spec.commonmark.org/0.30#html-blocks>
            if (false !== \strpos(',address,article,aside,base,basefont,blockquote,body,caption,center,col,colgroup,dd,details,dialog,dir,div,dl,dt,fieldset,figcaption,figure,footer,form,frame,frameset,h1,h2,h3,h4,h5,h6,head,header,hr,html,iframe,legend,li,link,main,menu,menuitem,nav,noframes,ol,optgroup,option,p,pre,param,script,section,source,style,summary,table,tbody,td,textarea,tfoot,th,thead,title,tr,track,ul,', ',' . \trim($t, '/') . ',')) {
                return [false, $row, [], $dent, $t];
            }
            // <https://spec.commonmark.org/0.30#example-163>
            if ('>' === \substr($test = \rtrim($row), -1)) {
                if ('/' === $t[0] && '<' . $t . '>' === $test) {
                    return [false, $row, [], $dent, $t];
                }
                if (\preg_match('/^<' . \preg_quote(\trim($t, '/'), '/') . '(\s[^>]*)?>$/', $test)) {
                    return [false, $row, [], $dent, $t];
                }
            }
            return ['p', $row, [], $dent, $t];
        }
        return ['p', $row, [], $dent];
    }
    // `=…`
    if (0 === \strpos($row, '=')) {
        if (\strspn($row, '=') === \strlen($row)) {
            return ['h1', $row, [], $dent, 1, '=']; // Look like a Setext-header level 1
        }
        return ['p', $row, [], $dent];
    }
    // `>…`
    if (0 === \strpos($row, '>')) {
        return ['blockquote', $row, [], $dent];
    }
    // `[…`
    if (0 === \strpos($row, '[')) {
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
        if (1 === \strpos($row, '^')) {
            return [2, $row, [], $dent];
        }
        return [0, $row, [], $dent];
    }
    // `_…`
    if (0 === \strpos($row, '_')) {
        // `___`
        $test = \strtr($row, [' ' => ""]);
        if (\strspn($test, '_') === ($v = \strlen($test)) && $v > 2) {
            return ['hr', $row, [], $dent, '_'];
        }
        return ['p', $row, [], $dent];
    }
    // ``…`
    if (0 === \strpos($row, '`')) {
        // ````…`
        if (0 === \strpos($row, '```')) {
            $fence = \substr($row, 0, $v = \strspn($row, '`'));
            $info = \trim(\substr($row, $v));
            // <https://spec.commonmark.org/0.30#example-145>
            if (false !== \strpos($info, '`')) {
                return ['p', $row, [], $dent];
            }
            return ['pre', $row, a($info, true), $dent, $fence];
        }
        return ['p', $row, [], $dent];
    }
    // `~…`
    if (0 === \strpos($row, '~')) {
        // `~~~…`
        if (0 === \strpos($row, '~~~')) {
            $fence = \substr($row, 0, $v = \strspn($row, '~'));
            $info = \trim(\substr($row, $v));
            return ['pre', $row, a($info, true), $dent, $fence];
        }
        return ['p', $row, [], $dent];
    }
    // `1…`
    $n = \strspn($row, '0123456789');
    // <https://spec.commonmark.org/0.30#example-266>
    if ($n > 9) {
        return ['p', $row, [], $dent];
    }
    // `1)` or `1.`
    if ($n && ($n + 1) === \strlen(\rtrim($row)) && false !== \strpos(').', \substr(\rtrim($row), -1))) {
        $start = (int) \substr($row, 0, $n);
        return ['ol', "", ['start' => 1 !== $start ? $start : null], [$dent, $n + 1], \substr($row, -1), $start];
    }
    // `1) …` or `1. …`
    if ($n === \strpos($row, ') ') || $n === \strpos($row, '. ')) {
        $start = (int) \substr($row, 0, $n);
        return ['ol', $row, ['start' => 1 !== $start ? $start : null], [$dent, $n + 1 + \strspn($row, ' ', $n + 1)], \substr($row, $n, 1), $start];
    }
    if ($n = \substr_count($row, '|')) {
        return ['table', $row, [], $dent, $n];
    }
    return ['p', $row, [], $dent];
}

function e(string $v, $as = \ENT_HTML5 | \ENT_QUOTES) {
    return \htmlspecialchars($v, $as, 'UTF-8');
}

// Apply stored reference(s), abbreviation(s), and foot-note(s) data to the row(s)
function lot(array $row, array $lot = [], $lazy = true): array {
    if (!$row) {
        return [];
    }
    $pattern = [];
    if (!empty($lot[1])) {
        foreach ($lot[1] as $k => $v) {
            $pattern[] = \preg_quote($k, '/');
        }
    }
    $pattern = $pattern ? '/\b(' . \implode('|', $pattern) . ')\b/' : 0;
    foreach ($row as &$v) {
        if ($pattern && false === $v[0] && \is_string($v[1])) {
            // Optimize if current chunk is a complete word boundary
            if (isset($lot[1][$v[1]])) {
                $title = $lot[1][$v[1]] ?? "";
                $v[1] = [['abbr', $v[1], ['title' => "" !== $title ? $title : null], -1]];
                continue;
            }
            // Else, chunk by word boundary
            $chops = [];
            foreach (\preg_split($pattern, $v[1], -1, \PREG_SPLIT_DELIM_CAPTURE | \PREG_SPLIT_NO_EMPTY) as $vv) {
                if (isset($lot[1][$vv])) {
                    $title = $lot[1][$vv] ?? "";
                    $chops[] = ['abbr', $vv, ['title' => "" !== $title ? $title : null], -1];
                    continue;
                }
                $chops[] = [false, $vv, [], -1];
            }
            $v[1] = $chops;
            continue;
        }
        if ('a' !== $v[0] && 'img' !== $v[0]) {
            continue;
        }
        if (!isset($v[4])) {
            continue; // Skip!
        }
        if (!isset($lot[0][$v[4]]) && $lazy) {
            // Restore the original syntax
            $v = [false, $v[5], [], -1];
            continue;
        }
        $attr = $v[2];
        if (!isset($attr[$k = 'a' === $v[0] ? 'href' : 'src'])) {
            unset($attr[$k]);
        }
        if (!isset($attr['title'])) {
            unset($attr['title']);
        }
        $v[2][$k] = $lot[0][$v[4]][0] ?? null;
        $v[2]['title'] = $lot[0][$v[4]][1] ?? null;
        $v[2] = \array_replace($v[2], $lot[0][$v[4]][2] ?? [], $attr);
        if ($lazy) {
            unset($v[4], $v[5]);
        }
    }
    unset($v);
    return $row;
}

function q(string $char = '"', $capture = false, string $before = ""): string {
    $a = \preg_quote($char[0], '/');
    $b = \preg_quote($char[1] ?? $char[0], '/');
    $c = $a . ($b === $a ? "" : $b);
    return '(?:' . $a . ($capture ? '(' : "") . '(?:' . ($before ? $before . '|' : "") . '\\\\.|[^' . $c . '\\\\])*' . ($capture ? ')' : "") . $b . ')';
}

function r(string $char = '[]', $capture = false, string $before = ""): string {
    $a = \preg_quote($char[0], '/');
    $b = \preg_quote($char[1] ?? $char[0], '/');
    $c = $a . ($b === $a ? "" : $b);
    return '(?:' . $a . ($capture ? '(' : "") . '(?:(?:' . ($before ? $before . '|' : "") . '\\\\.|[^' . $c . '\\\\])*|(?R))*' . ($capture ? ')' : "") . $b . ')';
}

function raw(?string $content): array {
    return rows($content);
}

function row(?string $content, array $lot = []): array {
    if ("" === \trim($content ?? "")) {
        return [[], $lot];
    }
    $chops = [];
    $prev = ""; // Capture the previous chunk
    while (false !== ($v = \strpbrk($content, '\\<`![*_&' . "\n"))) {
        if ("" !== ($prev = \substr($content, 0, \strlen($content) - \strlen($v)))) {
            $chops[] = [false, $prev, [], -1];
        }
        if (0 === \strpos($v, "\n")) {
            $prev = $chops[$last = \count($chops) - 1][1] ?? [];
            if (\is_string($prev) && '  ' === \substr($prev, -2)) {
                $chops[$last][1] = $prev = \rtrim($prev);
                $chops[] = ['br', false, [], -1];
                $content = \ltrim(\substr($v, 1));
                continue;
            }
            $chops[] = [false, $prev = ' ', [], -1];
            $content = \substr($v, 1);
            continue;
        }
        if (0 === \strpos($v, '![')) {
            $row = row(\substr($v, 1), $lot)[0][0];
            if ('a' === $row[0]) {
                $row[0] = 'img';
                $row[1] = false;
                $row[2]['alt'] = ""; // TODO
                $row[2]['src'] = $row[2]['href'];
                $row[5] = '!' . $row[5];
                unset($row[2]['href']);
                $chops[] = $row;
                $content = $v = \substr($v, \strlen($prev = $row[5]));
                continue;
            }
            $chops[] = [false, $prev = '!', [], -1];
            $content = $v = \substr($v, 1);
            continue;
        }
        if (0 === \strpos($v, '&')) {
            if (false === ($n = \strpos($v, ';')) || $n < 2 || !\preg_match('/^&(?:#x[a-f\d]{1,6}|#\d{1,7}|[a-z][a-z\d]{1,31});/i', $v, $m)) {
                $chops[] = [false, e($prev = '&'), [], -1];
                $content = \substr($v, 1);
                continue;
            }
            // <https://spec.commonmark.org/0.30#example-26>
            if ('&#0;' === $m[0]) {
                $m[0] = '&#xfffd;';
            }
            $chops[] = ['&', $m[0], [], -1];
            $content = \substr($v, \strlen($prev = $m[0]));
            continue;
        }
        if (\strlen($v) > 2 && false !== \strpos('*_', $v[0])) {
            // TODO
        }
        if (0 === \strpos($v, '<')) {
            $test = (string) \strstr($v, '>', true);
            // <https://github.com/commonmark/commonmark.js/blob/df3ea1e80d98fce5ad7c72505f9230faa6f23492/lib/inlines.js#L73>
            if (\strpos($test, '@') > 0 && \preg_match('/^<([a-z\d.!#$%&\'*+\/=?^_`{|}~-]+@[a-z\d](?:[a-z\d-]{0,61}[a-z\d])?(?:\.[a-z\d](?:[a-z\d-]{0,61}[a-z\d])?)*)>/i', $v, $m)) {
                // <https://spec.commonmark.org/0.30#example-605>
                if (false !== \strpos($email = $m[1], '\\')) {
                    $chops[] = [false, e($m[0]), [], -1];
                    $content = $v = \substr($v, \strlen($prev = $m[0]));
                    continue;
                }
                $chops[] = ['a', e($m[1]), ['href' => u('mailto:' . $email)], -1, null, $m[0]];
                $content = $v = \substr($v, \strlen($m[0]));
                continue;
            }
            // <https://github.com/commonmark/commonmark.js/blob/df3ea1e80d98fce5ad7c72505f9230faa6f23492/lib/inlines.js#L75>
            if (\strpos($test, ':') > 1 && \preg_match('/^<([a-z][a-z\d.+-]{1,31}:[^<>\x00-\x20]*)>/i', $v, $m)) {
                $chops[] = ['a', e($m[1]), ['href' => u($m[1])], -1, null, $m[0]];
                $content = $v = \substr($v, \strlen($prev = $m[0]));
                continue;
            }
            // <https://spec.commonmark.org/0.30#raw-html>
            if (\preg_match('/^<\/[a-z][a-z\d-]*\s*>/i', $v, $m)) {
                $chops[] = [false, $m[0], [], -1];
                $content = $v = \substr($v, \strlen($prev = $m[0]));
                continue;
            }
            // <https://spec.commonmark.org/0.30#raw-html>
            if (\preg_match('/^<[a-z][a-z\d-]*(\s[a-z_:][\w.:-]*(\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s"\'=<>`]+)?)?)*\s*\/?>/i', $v, $m)) {
                $chops[] = [false, $m[0], [], -1];
                $content = $v = \substr($v, \strlen($prev = $m[0]));
                continue;
            }
            $chops[] = [false, e($prev = '<'), [], -1];
            $content = $v = \substr($v, 1);
            continue;
        }
        if (0 === \strpos($v, '[')) {
            $attr = $key = $link = $title = null;
            // `[asdf]…`
            if (\preg_match('/' . r('[]', true) . '/', $v, $m, \PREG_OFFSET_CAPTURE)) {
                $prev = $m[0][0];
                if ($m[0][1] > 0) {
                    $chops[] = [false, e(\substr($v, 0, $m[0][1])), [], -1];
                    $content = $v = \substr($v, $m[0][1]);
                }
                $row = row($m[1][0], $lot)[0];
                if ($row && false !== \strpos($m[1][0], '[')) {
                    $deep = false;
                    foreach ($row as $vv) {
                        if (\is_array($vv) && 'a' === $vv[0]) {
                            // Found recursive link syntax!
                            $deep = true;
                            break;
                        }
                    }
                    // <https://spec.commonmark.org/0.30#example-517>
                    if ($deep) {
                        $chops[] = [false, '[', [], -1];
                        foreach ($row as $vv) {
                            $chops[] = $vv;
                        }
                        $chops[] = [false, ']', [], -1];
                        $content = $v = \substr($v, \strlen($m[0][0]));
                        continue;
                    }
                }
                $content = $v = \substr($v, \strlen($m[0][0]));
                // `…(asdf)`
                if (0 === \strpos($v, '(') && \preg_match('/' . r('()', true, q('<>')) . '/', $v, $n, \PREG_OFFSET_CAPTURE)) {
                    $prev = $n[0][0];
                    // `[asdf]()`
                    if ("" === ($n[1][0] = \trim($n[1][0] ?? ""))) {
                        $chops[] = ['a', $row, ['href' => ""], -1, null, $m[0][0] . $n[0][0]];
                        $content = $v = \substr($v, \strlen($n[0][0]));
                        continue;
                    }
                    // `[asdf](<>)`
                    if ('<>' === $n[1][0]) {
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
                        $chops[] = [false, e(v(\strtr($m[0][0] . $n[0][0], "\n", ' '))), [], -1];
                        $content = $v = \substr($v, \strlen($n[0][0]));
                        continue;
                    }
                    if (\is_string($title) && "" !== $title) {
                        // `[asdf](asdf "asdf")` or `[asdf](asdf 'asdf')` or `[asdf](asdf (asdf))`
                        $a = $title[0];
                        $b = \substr($title, -1);
                        if ((
                            '"' === $a && '"' === $b ||
                            "'" === $a && "'" === $b ||
                            '(' === $a && ')' === $b
                        ) && \preg_match('/^' . q($a . $b) . '$/', $title)) {
                            $title = v(\html_entity_decode(\substr($title, 1, -1), \ENT_HTML5 | \ENT_QUOTES, 'UTF-8'));
                        // `[asdf](asdf asdf)`
                        // <https://spec.commonmark.org/0.30#example-487>
                        } else {
                            $chops[] = [false, e(v(\strtr($m[0][0] . $n[0][0], "\n", ' '))), [], -1];
                            $content = $v = \substr($v, \strlen($n[0][0]));
                            continue;
                        }
                    }
                    $key = 0;
                    $content = $v = \substr($v, \strlen($n[0][0]));
                // `…[]` or `…[asdf]`
                } else if (0 === \strpos($v, '[') && \preg_match('/' . r('[]', true) . '/', $v, $n, \PREG_OFFSET_CAPTURE)) {
                    $prev = $n[0][0];
                    // `[asdf][]`
                    if ("" === $n[1][0]) {
                        $of = $lot[0][$key = \trim(\strtolower($m[1][0]))] ?? [];
                    // `[asdf][asdf]`
                    } else {
                        $of = $lot[0][$key = \trim(\strtolower($n[1][0]))] ?? [];
                    }
                    $attr = $of[2] ?? [];
                    $link = $of[0] ?? null;
                    $title = $of[1] ?? null;
                    $content = $v = \substr($v, \strlen($n[0][0]));
                }
                // …{asdf}
                if (0 === \strpos(\trim($v), '{') && \preg_match('/^\s*(' . q('{}', false, q('"') . '|' . q("'")) . ')/', $v, $n)) {
                    if ("" !== \trim(\substr($n[1], 1, -1))) {
                        $attr = \array_replace($attr ?? [], a($n[1], true));
                        $content = $v = \substr($v, \strlen($n[0]));
                    }
                }
                $chops[] = ['a', $row, \array_replace([
                    'href' => null !== $link ? u(v($link)) : null,
                    'title' => $title
                ], $attr ?? []), -1, 0 === $key ? null : ($key ?? $m[1][0]), $m[0][0] . ($n[0][0] ?? "")];
                continue;
            }
            $chops[] = [false, $prev = '[', [], -1];
            $content = $v = \substr($v, 1);
            continue;
        }
        if ('\\' === $v) {
            $chops[] = [false, $prev = $v, [], -1];
            $content = $v = "";
            break;
        }
        if (0 === \strpos($v, '\\') && isset($v[1])) {
            // <https://spec.commonmark.org/0.30#example-644>
            if ("\n" === $v[1]) {
                $chops[] = ['br', false, [], -1];
                $content = \ltrim(\substr($v, 2));
                $prev = '\\';
                continue;
            }
            $chops[] = [false, e($prev = \substr($v, 1, 1)), [], -1];
            $content = $v = \substr($v, 2);
            continue;
        }
        if (0 === \strpos($v, '`')) {
            $c = \str_repeat('`', $n = \strspn($v, '`'));
            if (\preg_match('/^' . $c . '((?:\\\\`|[^`]|' . (1 === $n ? '``+' : '`' . (2 === $n ? "" : '{1,' . ($n - 1) . '}')) . ')+)' . $c . '/', $v, $m)) {
                // <https://spec.commonmark.org/0.30#code-span>
                $raw = \strtr($m[1], "\n", ' ');
                if (' ' !== $raw && '  ' !== $raw && ' ' === $raw[0] && ' ' === \substr($raw, -1)) {
                    $raw = \substr($raw, 1, -1);
                }
                $chops[] = ['code', e($raw, \ENT_NOQUOTES), [], -1];
                $content = $v = \substr($v, \strlen($prev = $m[0]));
                continue;
            }
            $chops[] = [false, $prev = $c, [], -1];
            $content = $v = \substr($v, $n);
            continue;
        }
        $chops[] = [false, e($prev = $v), [], -1];
        $content = $v = "";
    }
    if ("" !== $content) {
        $chops[] = [false, e($prev = $content), [], -1];
        $content = "";
    }
    return [lot($chops, $lot, false), $lot];
}

function rows(?string $content, array $lot = []): array {
    // List of reference(s), abbreviation(s), and foot-note(s)
    $lot = \array_replace([[], [], []], $lot);
    $lot_of_content = [[], [], []];
    if ("" === \trim($content ?? "")) {
        return [[], $lot];
    }
    // Normalize line break(s)
    $content = \trim(\strtr($content, [
        "\r\n" => "\n",
        "\r" => "\n"
    ]), "\n");
    $block = 0;
    $blocks = [];
    $rows = \explode("\n", $content);
    foreach ($rows as $row) {
        // <https://spec.commonmark.org/0.30#tabs>
        $before = \strstr($row, "\t", true);
        if (false !== $before) {
            $row = $before . \str_repeat(' ', 4 - \strlen($before) % 4) . \substr($row, \strlen($before) + 1);
        }
        $current = data($row); // `[$type, $row, $data, $dent, …]`
        // If a block is available in the index `$block`, it indicates that we have a previous block.
         if ($prev = $blocks[$block] ?? 0) {
            // Raw HTML
            if (false === $prev[0]) {
                if ('!--' === $prev[4]) {
                    if (false !== \strpos($prev[1], '-->')) {
                        if (null === $current[0]) {
                            continue;
                        }
                        $blocks[++$block] = $current;
                        continue;
                    }
                    if (false !== \strpos($row, '-->')) {
                        $blocks[$block++][1] .= "\n" . $row;
                        continue;
                    }
                    $blocks[$block][1] .= "\n" . $row;
                    continue;
                }
                if ('![CDATA[' === $prev[4]) {
                    if (false !== \strpos($prev[1], ']]>')) {
                        if (null === $current[0]) {
                            continue;
                        }
                        $blocks[++$block] = $current;
                        continue;
                    }
                    if (false !== \strpos($row, ']]>')) {
                        $blocks[$block++][1] .= "\n" . $row;
                        continue;
                    }
                    $blocks[$block][1] .= "\n" . $row;
                    continue;
                }
                if ('!' === $prev[4][0]) {
                    if (false !== \strpos($prev[1], '>')) {
                        if (null === $current[0]) {
                            continue;
                        }
                        $blocks[++$block] = $current;
                        continue;
                    }
                    if (false !== \strpos($row, '>')) {
                        $blocks[$block++][1] .= "\n" . $row;
                        continue;
                    }
                    $blocks[$block][1] .= "\n" . $row;
                    continue;
                }
                if (false !== \strpos(',pre,script,style,textarea,', ',' . $prev[4] . ',')) {
                    if (false !== \strpos($prev[1], '</' . $prev[4] . '>')) {
                        if (null === $current[0]) {
                            continue;
                        }
                        $blocks[++$block] = $current;
                        continue;
                    }
                    if (false !== \strpos($row, '</' . $prev[4] . '>')) {
                        $blocks[$block++][1] .= "\n" . $row;
                        continue;
                    }
                    $blocks[$block][1] .= "\n" . $row;
                    continue;
                }
                if ('?' === $prev[4][0]) {
                    if (false !== \strpos($prev[1], '?' . '>')) {
                        if (null === $current[0]) {
                            continue;
                        }
                        $blocks[++$block] = $current;
                        continue;
                    }
                    if (false !== \strpos($row, '?' . '>')) {
                        $blocks[$block++][1] .= "\n" . $row;
                        continue;
                    }
                    $blocks[$block][1] .= "\n" . $row;
                    continue;
                }
                // CommonMark is not concerned with HTML tag balancing. It only concerned about blank line(s). Any
                // non-blank line that sits right next to or below the opening/closing tag other than `<pre>`,
                // `<script>`, `<style>`, and `<textarea> tag(s) will be interpreted as raw HTML. From that point
                // forward, no Markdown processing will be performed.
                // <https://spec.commonmark.org/0.30#example-161>
                if ("" !== $current[1]) {
                    $blocks[$block][1] .= "\n" . $row;
                    continue;
                }
            }
            // Reference, abbreviation, or foot-note
            if (\is_int($prev[0]) && "" !== $current[1]) {
                if (\is_int($current[0])) {
                    $blocks[++$block] = $current;
                    continue;
                }
                $blocks[$block][1] .= "\n" . $row;
                continue;
            }
            if ('p' === $prev[0]) {
                // <https://spec.commonmark.org/0.30#example-285>
                // <https://spec.commonmark.org/0.30#example-304>
                if ('ol' === $current[0] && ("" === $current[1] || 1 !== $current[5])) {
                    $blocks[$block][1] .= "\n" . $row;
                    continue;
                }
                // <https://spec.commonmark.org/0.30#example-285>
                if ('ul' === $current[0] && "" === $current[1]) {
                    if ('-' === $current[4]) {
                        $blocks[$block][0] = 'h2';
                        $blocks[$block][1] .= "\n" . $row;
                        $blocks[$block][4] = 2;
                        $blocks[$block][5] = '-';
                        $block += 1;
                        continue;
                    }
                    $blocks[$block][1] .= "\n" . $row;
                    continue;
                }
                // Probably a definition list with gap(s) between the term(s) and their definition data. Check if the
                // current paragraph is followed by one or more blank line(s) and a definition data. If so, convert the
                // current paragraph to a part of the definition list.
                if (null === $current[0]) {
                    $back = 0;
                    // Move the array pointer forward until reaching a non-blank row
                    while (false !== ($next = \next($rows))) {
                        ++$back;
                        if (0 === \strpos($next, ': ')) {
                            // If the next non-blank row appears to be a definition data row, consider the current blank
                            // row as part of the definition list
                            $current[0] = 'dl';
                            break;
                        }
                        if ("" !== $next) {
                            break;
                        }
                    }
                    // Reset the array pointer to the normal pointer
                    while (--$back >= 0) {
                        \prev($rows);
                    }
                }
            }
            // Verify that the current block has a type of `dl`, and verify that the previous block has a type of
            // `dl` or `p`. If so, merge the current block with the previous block, then change the type of the
            // previous block to `dl`.
            if ('dl' === $current[0] && ('dl' === $prev[0] || 'p' === $prev[0])) {
                $blocks[$block][0] = 'dl';
                $blocks[$block][1] .= "\n" . $row;
                continue;
            }
            // Verify that the current block has already been converted to a definition list, then verify that the
            // next row is a paragraph with initial indentation, then merge it with the previous block.
            if ('dl' === $prev[0] && \strspn($row, ' ') >= 2) {
                $row = \substr($row, 2); // Length of `: ` character(s)
                $blocks[$block][1] .= "\n" . $row;
                continue;
            }
            // List block is so complex that I decided to blindly concatenate all of the remaining line(s) until the
            // very end of the stream by default when the first list marker is found. To exit the list, we will do so
            // manually while we are in the list block.
            if ('ol' === $prev[0]) {
                // <https://spec.commonmark.org/0.30#example-99>
                if ('h1' === $current[0] && '=' === $current[5]) {
                    $blocks[++$block] = ['p', $current[1], [], $current[3]];
                    continue;
                }
                // <https://spec.commonmark.org/0.30#example-278> but with indent that is less than the minimum required
                if ('p' === $current[0] && "" === $prev[1] && $current[3] < $prev[3][1]) {
                    $current[1] = $prev[5] . $prev[4] . "\n" . $current[1];
                    $blocks[$block] = $current;
                    continue;
                }
                // To exit the list, either start a new list marker with a lower number than the previous list number or
                // use a different number suffix. For example, use `1)` to separate the previous list that was using
                // `1.` as the list marker.
                if ('ol' === $current[0] && $current[3][0] === $prev[3][0] && ($current[4] !== $prev[4] || $current[5] < $prev[5])) {
                    // Remove final line break
                    $blocks[$block][1] = \rtrim($prev[1], "\n");
                    $blocks[++$block] = $current;
                    continue;
                }
                if (null !== $current[0]) {
                    $n = \is_int($current[3]) ? $current[3] : $current[3][0];
                    if ('ol' !== $current[0] && $n < $prev[3][1]) {
                        if ('p' === $current[0] && "\n" !== \substr($prev[1], -1)) {
                            $blocks[$block][1] .= "\n" . $row; // Lazy list
                            continue;
                        }
                        // Remove final line break
                        $blocks[$block][1] = \rtrim($prev[1], "\n");
                        // Exit the list using block(s) other than the paragraph block
                        $blocks[++$block] = $current;
                        continue;
                    }
                }
                // Update final number list to track the current highest number
                if (isset($current[5]) && $current[3][0] === $prev[3][0]) {
                    $blocks[$block][5] = $current[5];
                }
                // Continue as part of the list item content
                $row = \substr($row, $prev[3][0]);
                $blocks[$block][1] .= "\n" . $row;
                continue;
            }
            // Here goes the bullet list block
            if ('ul' === $prev[0]) {
                // <https://spec.commonmark.org/0.30#example-99>
                if ('h1' === $current[0] && '=' === $current[5]) {
                    $blocks[++$block] = ['p', $current[1], [], $current[3]];
                    continue;
                }
                // <https://spec.commonmark.org/0.30#example-278> but with indent that is less than the minimum required
                if ('p' === $current[0] && "" === $prev[1] && $current[3] < $prev[3][1]) {
                    $current[1] = $prev[4] . "\n" . $current[1];
                    $blocks[$block] = $current;
                    continue;
                }
                // To exit the list, use a different list marker.
                if ('ul' === $current[0] && $current[3][0] === $prev[3][0] && $current[4] !== $prev[4]) {
                    // Remove final line break
                    $blocks[$block][1] = \rtrim($prev[1], "\n");
                    $blocks[++$block] = $current;
                    continue;
                }
                if (null !== $current[0]) {
                    $n = \is_int($current[3]) ? $current[3] : $current[3][0];
                    if ('ul' !== $current[0] && $n < $prev[3][1]) {
                        if ('p' === $current[0] && "\n" !== \substr($prev[1], -1)) {
                            $blocks[$block][1] .= "\n" . $row; // Lazy list
                            continue;
                        }
                        // Remove final line break
                        $blocks[$block][1] = \rtrim($prev[1], "\n");
                        // Exit the list using block(s) other than the paragraph block
                        $blocks[++$block] = $current;
                        continue;
                    }
                }
                // Continue as part of the list item content
                $row = \substr($row, $prev[3][0]);
                $blocks[$block][1] .= "\n" . $row;
                continue;
            }
            // Fenced code block
            if ('pre' === $prev[0] && isset($prev[4])) {
                // Exit fenced code block
                if ('pre' === $current[0] && isset($current[4]) && $prev[4] === $current[1]) {
                    $blocks[$block++][1] .= "\n" . $row;
                    continue;
                }
                // Continue fenced code block
                $blocks[$block][1] .= "" !== $prev[1] ? "\n" . $row : $row;
                continue;
            }
            // Indented code block
            if ('pre' === $current[0] && $current[3] >= 4) {
                $row = \substr($row, 4);
                // Continue indented code block
                $blocks[$block][1] .= "\n" . $row;
                continue;
            }
            if ('blockquote' === $prev[0]) {
                if ('p' === $current[0]) {
                    $blocks[$block][1] .= "\n" . $row; // Lazy quote block
                    continue;
                }
            }
            // Found Setext-header marker level 1 right below a paragraph or quote block
            if ('h1' === $current[0] && '=' === $current[5]) {
                // <https://spec.commonmark.org/0.30#example-93>
                if ('blockquote' === $prev[0]) {
                    $blocks[$block][1] .= ' ' . $current[1];
                } else if ('p' === $prev[0]) {
                    $blocks[$block][0] = $current[0]; // Treat the previous block as Setext-header level 1
                    $blocks[$block][1] .= "\n" . $current[1];
                    $blocks[$block][5] = $current[5];
                }
                $block += 1; // Start a new block after this
                continue;
            }
            // Found Setext-header marker level 2 right below a paragraph block
            if ('h2' === $current[0] && '-' === $current[5] && 'p' === $prev[0]) {
                $blocks[$block][0] = $current[0]; // Treat the previous block as Setext-header level 2
                $blocks[$block][1] .= "\n" . $current[1];
                $blocks[$block][5] = $current[5];
                $block += 1; // Start a new block after this
                continue;
            }
            // Found thematic break that sits right below a paragraph block
            if ('hr' === $current[0] && '-' === $current[4] && 'p' === $prev[0] && \strspn($current[1], $current[4]) === \strlen($current[1])) {
                $blocks[$block][0] = 'h2'; // Treat the previous block as Setext-header level 2
                $blocks[$block][1] .= "\n" . $current[1];
                $blocks[$block][4] = 2;
                $blocks[$block][5] = '-';
                $block += 1; // Start a new block after this
                continue;
            }
            // Default action is to join current block with the previous block that has the same type
            if ($current[0] === $prev[0]) {
                $row = \substr($row, $current[3]);
                $blocks[$block][1] .= "\n" . $row;
                continue;
            }
        }
        // Any other named block(s) will be processed from here
        if (\is_string($current[0])) {
            // Enter fenced code block
            if ('pre' === $current[0] && isset($current[4])) {
                $blocks[++$block] = $current;
                continue;
            }
            // Enter quote block
            if ('blockquote' === $current[0]) {
                // Start a new quote block
                $blocks[++$block] = $current;
                continue;
            }
            // Look like a Setext-header level 1, but preceded by a blank line, treat it as a paragraph block
            // <https://spec.commonmark.org/0.30#example-97>
            if ('h1' === $current[0] && '=' === $current[5] && (!$prev || null === $prev[0])) {
                $blocks[++$block] = ['p', $current[1], [], $current[3]];
                continue;
            }
            // An ATX-header block or thematic break
            if ('h' === $current[0][0]) {
                $blocks[++$block] = $current;
                $block += 1; // Start a new block after this
                continue;
            }
        }
        // A blank line
        if (null === $current[0]) {
            // Continue definition list
            if ($prev && 'dl' === $prev[0]) {
                $blocks[$block][1] .= "\n";
                continue;
            }
            // Continue code block
            if ($prev && 'pre' === $prev[0]) {
                $blocks[$block][1] .= "\n";
                continue;
            }
            // Default action is to start a new block after a blank line
            $block += 1;
            continue;
        }
        $blocks[++$block] = $current;
    }
    $blocks = \array_values($blocks);
    foreach ($blocks as $k => &$v) {
        if (false === $v[0]) {
            continue;
        }
        if (\is_int($v[0]) && \strpos($v[1], ']:') > 1) {
            // Match an abbreviation
            if (\preg_match('/^[*]' . q('[]', true) . ':([\s\S]*?)$/', $v[1], $m)) {
                // Remove abbreviation block from the structure
                unset($blocks[$k]);
                // Abbreviation is not part of the CommonMark specification, but I will just assume it to behave similar
                // to the reference specification.
                $m[1] = \trim(\preg_replace('/\s+/', ' ', $m[1]));
                // Queue the abbreviation data to be used later
                $title = \trim($m[2] ?? "");
                $lot_of_content[$v[0]][$m[1]] = $lot[$v[0]][$m[1]] = $title;
                continue;
            }
            // Match a reference
            if (\preg_match('/^' . q('[]', true) . ':(?:\s*(' . q('<>') . '|\S+?)(?:\s+(' . q('"') . '|' . q("'") . '|' . q('()') . ')\s*)?)(?:\s*(\{.*?\}))?$/', $v[1], $m)) {
                // Remove reference block from the structure
                unset($blocks[$k]);
                // <https://spec.commonmark.org/0.30#matches>
                $m[1] = \trim(\strtolower(\preg_replace('/\s+/', ' ', $m[1])));
                // Pre-defined reference data from the `$lot` variable can be overridden, but not if it is from the data
                // that is embedded in the `$content` variable to conform to the CommonMark specification about
                // reference priority.
                // <https://spec.commonmark.org/0.30#example-204>
                if (isset($lot_of_content[$v[0]][$m[1]])) {
                    continue;
                }
                if ($link = $m[2] ?? "") {
                    if ('<' === $link[0] && '>' === \substr($link, -1)) {
                        $link = \substr($link, 1, -1);
                        // <https://spec.commonmark.org/0.30#example-490>
                        if (false !== \strpos($link, "\n")) {
                            $chops[] = [false, $m[0], [], -1];
                            $content = \substr($content, \strlen($prev = $m[0]));
                            continue;
                        }
                    }
                }
                if ($title = $m[3] ?? null) {
                    if (
                        "'" === $title[0] && "'" === \substr($title, -1) ||
                        '"' === $title[0] && '"' === \substr($title, -1) ||
                        '(' === $title[0] && ')' === \substr($title, -1)
                    ) {
                        $title = v(\html_entity_decode(\substr($title, 1, -1), \ENT_HTML5 | \ENT_QUOTES, 'UTF-8'));
                    }
                }
                if ($attr = $m[4] ?? []) {
                    $attr = a($attr, true);
                }
                // Queue the reference data to be used later
                $lot_of_content[$v[0]][$m[1]] = $lot[$v[0]][$m[1]] = [u(v($link)), $title, $attr];
                continue;
            }
        }
        if ('blockquote' === $v[0]) {
            $v[1] = \substr(\strtr($v[1], ["\n>" => "\n"]), 1);
            if (0 === \strpos($v[1], ' ')) {
                $v[1] = \substr(\strtr($v[1], ["\n " => "\n"]), 1);
            }
            $v[1] = rows($v[1], $lot)[0];
            continue;
        }
        if ('dl' === $v[0]) {
            [$a, $b] = \preg_split('/\n++(?=:[ \t])/', $v[1], 2);
            $a = \explode("\n", $a);
            $b = \preg_split('/\n++(?=:[ \t])/', $b);
            $list_is_tight = false === \strpos($v[1], "\n\n");
            foreach ($a as &$vv) {
                $vv = ['dt', $vv];
            }
            unset($vv);
            foreach ($b as &$vv) {
                $vv = \substr($vv, 2); // Length of `: ` character(s)
                $vv = rows($vv, $lot)[0];
                if ($list_is_tight && $vv) {
                    foreach ($vv as &$vvv) {
                        if ('p' === $vvv[0]) {
                            $vvv[0] = false;
                        }
                    }
                    unset($vvv);
                }
                $vv = ['dd', $vv];
            }
            unset($vv);
            $v[1] = \array_merge($a, $b);
            continue;
        }
        if ('hr' === $v[0]) {
            $v[1] = false;
            continue;
        }
        if ('h' === $v[0][0]) {
            if ('#' === $v[5]) {
                $v[1] = \trim(\substr($v[1], \strspn($v[1], '#')));
                if ('#' === \substr($v[1], -1)) {
                    $vv = \substr($v[1], 0, \strpos($v[1], '#'));
                    if (' ' === \substr($vv, -1)) {
                        $v[1] = \substr($vv, 0, -1);
                    }
                }
            } else if ('-' === $v[5] || '=' === $v[5]) {
                $v[1] = \substr($v[1], 0, \strpos($v[1], "\n" . $v[5]));
            }
            // Late attribute parsing
            $at = at($v[1]);
            if ($at[1]) {
                $v[1] = $at[0];
                $v[2] = \array_replace($v[2], $at[1]);
            }
            $v[1] = row($v[1], $lot)[0];
            continue;
        }
        if ('ol' === $v[0]) {
            $list = \preg_split('/\n(?=\d++[).]\s)/', $v[1]);
            $list_is_tight = false === \strpos($v[1], "\n\n");
            foreach ($list as &$vv) {
                $vv = \substr(\strtr($vv, ["\n" . \str_repeat(' ', $v[3][1]) => "\n"]), $v[3][1]); // Remove indent(s)
                $vv = rows($vv, $lot)[0];
                if ($list_is_tight && $vv) {
                    foreach ($vv as &$vvv) {
                        if ('p' === $vvv[0]) {
                            $vvv[0] = false;
                        }
                    }
                    unset($vvv);
                }
                $vv = ['li', $vv];
            }
            unset($vv);
            $v[1] = $list;
            continue;
        }
        if ('pre' === $v[0]) {
            $v[1] = e($v[1], \ENT_NOQUOTES);
            if (isset($v[4])) {
                $v[1] = [['code', \substr(\strstr($v[1], "\n"), 1, -(\strlen($v[4]) + 1)), $v[2]]];
                $v[2] = [];
                continue;
            }
            $v[1] = [['code', $v[1], $v[2]]];
            $v[2] = [];
            continue;
        }
        if ('ul' === $v[0]) {
            $list = \preg_split('/\n(?=[*+-]\s)/', $v[1]);
            $list_is_tight = false === \strpos($v[1], "\n\n");
            foreach ($list as &$vv) {
                $vv = \substr(\strtr($vv, ["\n" . \str_repeat(' ', $v[3][1]) => "\n"]), $v[3][1]); // Remove indent(s)
                $vv = rows($vv, $lot)[0];
                if ($list_is_tight && $vv) {
                    foreach ($vv as &$vvv) {
                        if ('p' === $vvv[0]) {
                            $vvv[0] = false;
                        }
                    }
                    unset($vvv);
                }
                $vv = ['li', $vv];
            }
            unset($vv);
            $v[1] = $list;
            continue;
        }
        if (\is_string($v[1])) {
            $v[1] = row(\rtrim($v[1]), $lot)[0];
        }
    }
    unset($v);
    foreach ($blocks as &$v) {
        if (!\is_array($v[1])) {
            continue;
        }
        $v[1] = lot($v[1], $lot);
    }
    unset($v);
    return [$blocks, $lot];
}

function s(array $data): string {
    if (false === $data[0]) {
        if (\is_array($data[1])) {
            $out = "";
            foreach ($data[1] as $v) {
                $out .= \is_array($v) ? s($v) : $v;
            }
            return $out;
        }
        return $data[1];
    }
    if (\is_int($data[0])) {
        return "";
    }
    if ('&' === $data[0]) {
        return e(\html_entity_decode($data[1], \ENT_HTML5 | \ENT_QUOTES, 'UTF-8'));
    }
    $out = '<' . $data[0];
    if (!empty($data[2])) {
        foreach ($data[2] as $k => $v) {
            if (false === $v || null === $v) {
                continue;
            }
            $out .= ' ' . $k . (true === $v ? "" : '="' . e($v) . '"');
        }
    }
    if (false !== $data[1]) {
        $out .= '>';
        if (\is_array($data[1])) {
            foreach ($data[1] as $v) {
                $out .= \is_array($v) ? s($v) : $v;
            }
        } else {
            $out .= $data[1];
        }
        $out .= '</' . $data[0] . '>';
    } else {
        $out .= ' />';
    }
    return $out;
}

function u(string $v): string {
    \preg_match('/^([^?#]*)?([?][^#]*)?([#].*)?$/', \html_entity_decode($v, \ENT_HTML5 | \ENT_QUOTES, 'UTF-8'), $m);
    return \strtr(\rawurlencode($m[1]), [
        '%25' => '%',
        '%2B' => '+',
        '%2C' => ',',
        '%2F' => '/',
        '%3A' => ':',
        '%3B' => ';',
        '%40' => '@'
    ]) . (isset($m[2]) && "" !== $m[2] && '?' !== $m[2] ? '?' . \strtr(\rawurlencode(\substr($m[2], 1)), [
        '%25' => '%',
        '%26' => '&',
        '%3D' => '='
    ]) : "") . (isset($m[3]) && "" !== $m[3] && '#' !== $m[3] ? '#' . \strtr(\rawurlencode(\substr($m[3], 1)), [
        '%25' => '%'
    ]) : "");
}

// <https://spec.commonmark.org/0.30#example-12>
function v(string $content): string {
    return $content ? \strtr($content, [
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
    ]) : $content;
}