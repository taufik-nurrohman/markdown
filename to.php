<?php

namespace x\markdown {
    function to(?string $value, $block = true): ?string {
        if (!$block) {
            [$row] = to\row($value);
            if (!$row) {
                return null;
            }
            if (\is_string($row)) {
                $value = \trim(\preg_replace('/\s+/', ' ', $row));
                return "" !== $value ? $value : null;
            }
            foreach ($row as &$v) {
                $v = \is_array($v) ? to\s($v) : $v;
            }
            $value = \trim(\preg_replace('/\s+/', ' ', \implode("", $row)));
            return "" !== $value ? $value : null;
        }
        [$rows, $lot] = to\rows($value);
        if (!$rows) {
            return null;
        }
        foreach ($rows as &$row) {
            $row = \is_array($row) ? to\s($row) : $row;
        }
        $value = \rtrim(\implode("", $rows), "\n");
        if (!empty($lot[1])) {
            $value .= "\n";
            foreach ($lot[1] as $k => $v) {
                $value .= "\n*[" . $k . ']:' . ("" !== $v ? ' ' . $v : "");
            }
        }
        $value = \strtr($value, [
            "\0" => ""
        ]);
        $value = \preg_replace('/[ ]{2,}\n[ ]*/', "  \n", $value);
        return "" !== $value ? $value : null;
    }
}

namespace x\markdown\to {
    function a(?string $info) {
        if ("" === ($info = \trim($info ?? ""))) {
            return [];
        }
        $out = [];
        if (!\preg_match_all('/(^|\s)([^"\'\/<=>\s]+)(?>=("[^"]*"|\'[^\']*\'|[^\/<>\s]+)?)?/', $info, $m)) {
            return $out;
        }
        foreach ($m[2] as $k => $v) {
            if (!isset($m[3][$k])) {
                $out[$v] = true;
                continue;
            }
            if ("" === $m[3][$k]) {
                $out[$v] = "";
                continue;
            }
            if ('"' === $m[3][$k][0] && '"' === \substr($m[3][$k], -1)) {
                $m[3][$k] = \substr($m[3][$k], 1, -1);
            } else if ("'" === $m[3][$k][0] && "'" === \substr($m[3][$k], -1)) {
                $m[3][$k] = \substr($m[3][$k], 1, -1);
            }
            $out[$v] = d($m[3][$k]);
        }
        $out && \ksort($out);
        return $out;
    }
    function attr(array $data): ?string {
        if (!$data) {
            return null;
        }
        foreach ($data as $k => &$v) {
            if ('class' === $k) {
                $v = '.' . \trim(\preg_replace('/\s+/', '.', $v));
                continue;
            }
            if ('id' === $k) {
                $v = '#' . $v;
                continue;
            }
            if (true === $v) {
                $v = $k;
                continue;
            } else {
                $v = $k . "='" . \strtr($v, ["'" => "\\'"]) . "'";
                continue;
            }
        }
        unset($v);
        \sort($data);
        return '{' . \strtr(\trim(\implode(' ', $data)), [
            ' #' => '#',
            ' .' => '.'
        ]) . '}';
    }
    function d(?string $v, $as = \ENT_HTML5 | \ENT_QUOTES) {
        return \htmlspecialchars_decode($v ?? "", $as);
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
            $v = \reset($row);
            if (\is_array($v) && false === $v[0] && \is_string($v[1])) {
                return $v[1];
            }
            if (\is_string($v)) {
                return $v;
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
            $v = \reset($row);
            if (\is_array($v) && false === $v[0] && \is_string($v[1])) {
                return $v[1];
            }
            if (\is_string($v)) {
                return $v;
            }
        }
        return $row;
    }
    function p(array $tags, $deep = null): string {
        foreach ($tags as &$tag) {
            if (false === $deep) {
                $tag = '<' . $tag . '(?>\s+(?>"[^"]*"|\'[^\']*\'|[^\/>])*)?\/?>';
                continue;
            }
            $tag = '<' . $tag . '(?>\s+(?>"[^"]*"|\'[^\']*\'|[^\/>])*)?>' . ($deep ? '(?>(?R)|[\s\S])*?' : '[\s\S]*?') . '<\/' . $tag . '>';
        }
        return \implode('|', $tags);
    }
    function row(?string $value, array &$lot = []) {
        if ("" === \trim($value ?? "")) {
            return [[], $lot];
        }
        $chops = [];
        $pattern_1 = p(['a', 'abbr', 'b', 'code', 'em', 'i', 'strong']);
        $pattern_2 = p(['br', 'img'], false);
        $pattern_3 = '<(?>"[^"]*"|\'[^\']*\'|[^>])+>';
        foreach (\preg_split('/(' . $pattern_1 . '|' . $pattern_2 . '|' . $pattern_3 . '|\s+)/', $value, -1, \PREG_SPLIT_DELIM_CAPTURE | \PREG_SPLIT_NO_EMPTY) as $v) {
            if ("" === \trim($v)) {
                $chops[] = ' ';
                continue;
            }
            if ('<' !== $v[0] || '>' !== \substr($v, -1)) {
                $chops[] = $v;
                continue;
            }
            if (!\preg_match('/^<([^\s"\'\/<=>]+)(\s+(?>"[^"]*"|\'[^\']*\'|[^\/>])*)?(?:>[ \t]*([\s\S]*?)[ \t]*<\/\1>|\/?>)$/', $v, $m)) {
                $chops[] = $v;
                continue;
            }
            $t = $m[1];
            $a = a($m[2] ?? "");
            if ('a' === $t) {
                $chops[] = ['a', row($m[3], $lot)[0], $a, -1];
                continue;
            }
            if ('abbr' === $t) {
                $chops[] = ['abbr', $key = d($m[3]), $a, -1];
                $lot[1][$key] = \trim(\preg_replace('/\s+/', ' ', $a['title'] ?? ""));
                continue;
            }
            if ('b' === $t || 'strong' === $t) {
                $chops[] = ['strong', row(\strtr($m[3], ['*' => '\*']), $lot)[0], $a, -1, '**'];
                continue;
            }
            if ('br' === $t) {
                $chops[] = ['br', false, $a, -1];
                continue;
            }
            if ('code' === $t) {
                $chops[] = ['code', $m[3], $a, -1];
                continue;
            }
            if ('em' === $t || 'i' === $t) {
                $chops[] = ['em', row(\strtr($m[3], ['*' => '\*']), $lot)[0], $a, -1, '*'];
                continue;
            }
            if ('img' === $t) {
                $chops[] = ['img', false, $a, -1];
                continue;
            }
        }
        return [m($chops), $lot];
    }
    function rows(?string $value, array &$lot = []): array {
        $lot = \array_replace([[], [], []], $lot);
        if ("" === \trim($value ?? "") || false === \strpos($value, '<')) {
            return [[], $lot];
        }
        $pattern_1 = p(['blockquote', 'dl', 'dd', 'figure', 'figcaption', 'ol', 'table', 'ul', 'li'], true);
        $pattern_2 = p(['dt', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'hr', 'p', 'pre', 'script', 'style', 'textarea']);
        $pattern_3 = p(['hr', 'img'], false);
        $pattern_4 = '<(?>"[^"]*"|\'[^\']*\'|[^>])+>';
        $blocks = [];
        foreach (\preg_split('/(' . $pattern_1 . '|' . $pattern_2 . '|' . $pattern_3 . '|' . $pattern_4 . '|\s+)/', $value, -1, \PREG_SPLIT_DELIM_CAPTURE | \PREG_SPLIT_NO_EMPTY) as $v) {
            if ("" === \trim($v)) {
                continue;
            }
            if ('<' !== $v[0] || '>' !== \substr($v, -1)) {
                $blocks[] = $v;
                continue;
            }
            if (!\preg_match('/^<([^\s"\'\/<=>]+)(\s+(?>"[^"]*"|\'[^\']*\'|[^\/>])*)?(?:>[ \t]*([\s\S]*?)[ \t]*<\/\1>|\/?>)$/', $v, $m)) {
                $blocks[] = [false, $v, [], 0];
                continue;
            }
            $t = $m[1];
            $c = $m[3] ?? false;
            $a = a($m[2] ?? "");
            if (false !== \strpos(',blockquote,dd,dl,dt,figure,figcaption,li,ol,ul,', ',' . $t . ',')) {
                $c = rows($c, $lot)[0] ?: $c;
                if ('dd' === $t) {
                    $blocks[] = [$t, $c, $a, 0, [2, ':', "", \is_array($c)]];
                    continue;
                }
                if ('ol' === $t) {
                    $blocks[] = [$t, $c, $a, 0, [3, (int) ($a['start'] ?? 1), '.', \is_array($c)]];
                    continue;
                }
                if ('ul' === $t) {
                    $blocks[] = [$t, $c, $a, 0, [2, '-', "", \is_array($c)]];
                    continue;
                }
                $blocks[] = [$t, $c, $a, 0];
                continue;
            }
            if ('hr' === $t) {
                $blocks[] = [$t, $c, $a, 0, '-'];
                continue;
            }
            if ('h' === $t[0] && false !== \strpos('123456', $t[1])) {
                $n = (int) $t[1];
                $blocks[] = [$t, row($c, $lot)[0], $a, 0, [$n, 1 === $n ? '=' : (2 === $n ? '-' : '#')]];
                continue;
            }
            if ('img' === $t) {
                $blocks[] = [$t, $c, $a, 0];
                continue;
            }
            if ('p' === $t) {
                $blocks[] = [$t, row($c, $lot)[0], $a, 0];
                continue;
            }
            $blocks[] = [false, $v, [], 0];
        }
        return [$blocks, $lot];
    }
    function s(array $data): ?string {
        [$t, $c, $a] = $data;
        $out = "";
        $x = "\0";
        if (\is_array($c)) {
            foreach ($c as &$v) {
                if (!\is_array($v)) {
                    $v = (string) $v;
                    continue;
                }
                [$tt, $cc, $aa] = $v;
                if (false === $tt) {
                    $v = $x . $cc;
                    continue;
                }
                if ('a' === $tt || 'img' === $tt) {
                    $attr = (array) ($aa ?? []);
                    $value = 'a' === $tt ? (\is_array($cc) ? s($cc) : \strtr(d($cc), [
                        '[' => '\[',
                        ']' => '\]'
                    ])) : \strtr($attr['alt'], [
                        '[' => '\[',
                        ']' => '\]'
                    ]);
                    $link = $attr['a' === $tt ? 'href' : 'src'] ?? "";
                    $title = $attr['title'] ?? null;
                    unset($attr['a' === $tt ? 'href' : 'src'], $attr['title']);
                    if ('a' !== $tt) {
                        unset($attr['alt']);
                    }
                    if (!l($link)) {
                        if (isset($attr['rel']) && 'nofollow' === $attr['rel']) {
                            unset($attr['rel']);
                        }
                        if (isset($attr['target']) && '_blank' === $attr['target']) {
                            unset($attr['target']);
                        }
                    }
                    $attr = attr($attr);
                    if ('a' === $tt && false !== \strpos($link, '://') && $value === $link && !$attr && !$title) {
                        $v = $x . '<' . $link . '>';
                        continue;
                    }
                    if ("" === $link || false !== \strpos($link, ' ')) {
                        $link = '<' . $link . '>';
                    }
                    if (null !== $title) {
                        if ("" === $title) {
                            $title = ' ""';
                        } else if (false !== \strpos($title, '"') && false === \strpos($title, "'")) {
                            $title = " '" . $title . "'";
                        } else if (false !== \strpos($title, "'") && false === \strpos($title, '"')) {
                            $title = ' "' . $title . '"';
                        } else if (false !== \strpos($title, '"') && false !== \strpos($title, "'")) {
                            if (false !== \strpos($title, '(')) {
                                // <https://stackoverflow.com/a/35271017/1163000>
                                $test = \preg_replace('/\([^()]*+((?R)[^()]*)*+\)/', "", $title);
                                if (false !== \strpos($test, '(') || false !== \strpos($test, ')')) {
                                    $title = \strtr($title, ['(' => '\(', ')' => '\)']);
                                }
                            }
                            $title = ' (' . $title . ')';
                        } else {
                            $title = " '" . $title . "'";
                        }
                    }
                    $v = ($v[3] < 0 ? $x : "") . ('a' === $tt ? "" : '!') . '[' . $value . '](' . $link . $title . ')' . (null !== $attr ? ' ' . $attr : "");
                    continue;
                }
                if ('abbr' === $tt) {
                    $v = $cc;
                    continue;
                }
                if ('br' === $tt) {
                    $v = "  \n";
                    continue;
                }
                if ('code' === $tt) {
                    if (false !== \strpos($cc, '`')) {
                        if (false === \strpos($cc, '``')) {
                            $v = $x . '`` ' . d($cc) . ' ``';
                            continue;
                        }
                        $v = $x . '` ' . d($cc) . ' `';
                        continue;
                    }
                    $v = $x . '`' . d($cc) . '`';
                    continue;
                }
                if ('em' === $tt) {
                    $v = $x . '*' . (\is_array($cc) ? s($cc) : d($cc)) . '*';
                    continue;
                }
                if ('strong' === $tt) {
                    $v = $x . '**' . (\is_array($cc) ? s($cc) : d($cc)) . '**';
                    continue;
                }
                $v = s($v);
            }
            $out = \implode("", $c);
        } else {
            $out = (string) $c;
        }
        if (false === $t) {
        } else if ('blockquote' === $t) {
            $out = '> ' . \strtr(\trim($out, "\n"), [
                "\n" => "\n> "
            ]);
            $out = \preg_replace('/^>[ ]$/m', '>', $out) . "\n\n";
        } else if ('dd' === $t) {
            $out = ': ' . \strtr(\trim($test = $out, "\n"), ["\n" => "\n  "]);
            $out = \preg_replace('/^[ ]+$/m', "", $out) . "\n";
            if ("\n\n" === \substr($test, -2)) {
                $out = "\n" . $out;
            }
        } else if ('dl' === $t) {
            $out .= "\n";
        } else if ('dt' === $t) {
            $out .= "\n";
        } else if ('figcaption' === $t) {
            $out = "\n " . \strtr(\trim($test = $out, "\n"), ["\n" => "\n "]);
            $out = \preg_replace('/^[ ]+$/m', "", $out);
            if ("\n\n" === \substr($test, -2)) {
                $out = "\n" . $out;
            }
        } else if ('figure' === $t) {
            $out .= "\n\n";
        } else if ('hr' === $t) {
            $out = \str_repeat($data[4], 3) . "\n\n";
        } else if ('h' === $t[0] && isset($data[4][1])) {
            if ($attr = attr($a)) {
                $out .= ' ' . $attr;
            }
            if ("" === \trim($out)) {
                $out = \str_repeat('#', $data[4][0]);
            } else if (false !== \strpos('-=', $data[4][1])) {
                $out .= "\n" . \str_repeat($data[4][1], \strlen($out));
            } else {
                $out = \str_repeat($data[4][1], $data[4][0]) . ' ' . $out;
            }
            $out .= "\n\n";
        } else if ('ol' === $t) {
            $out = $x . $out . "\n";
        } else if ('li' === $t) {
            $out = '- ' . \strtr(\trim($test = $out, "\n"), [
                "\n" => "\n  ",
                $x . '- ' => "\n  - "
            ]);
            $out = \preg_replace('/^[ ]+$/m', "", $out) . "\n";
            if ("\n\n" === \substr($test, -2) && false === \strpos($out, "\n  - ")) {
                $out = "\n" . $out;
            }
        } else if ('p' === $t) {
            if ($out && false !== \strpos('#*+-:>`~', $out[0])) {
                $out = "\\" . $out;
            } else {
                $n = \strspn($out, '0123456789');
                if (false !== \strpos(').', \substr($out, $n, 1))) {
                    $out = \substr($out, 0, $n) . "\\" . \substr($out, $n);
                }
            }
            $out .= "\n\n";
        } else if ('ul' === $t) {
            $out = $x . $out . "\n";
        } else {
            $out = e($out);
        }
        return $out;
    }
    function x(?string $v): string {}
}