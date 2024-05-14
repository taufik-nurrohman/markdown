<?php

$chop = <<<TEST
asdf *asdf*

*(*

*)*
TEST;

function emphasize($chop) {
    $result = "";
    $can_open = false;
    $can_close = true;
    while ("" !== $chop) {
        $c_prev = substr($result, -1);
        $c_current = $chop[0];
        $c_next = $chop[1] ?? "";
        if ("" === $c_next) {
            if ('*' === $c_current) {
                $result .= '</em>';
                $chop = substr($chop, 1);
                continue;
            }
            $result .= $c_current;
            $chop = substr($chop, 1);
            break;
        }
        if ("\\" === $c_current) {
            $result .= $c_current . $c_next;
            $chop = substr($chop, 2);
            continue;
        }
        // Left flank
        if ('*' === $c_current && (
            // (1)
            !preg_match('/[\p{Zs}]/u', $c_next) && (
                // (2a)
                !preg_match('/[\p{P}]/u', $c_next) ||
                // (2b)
                ("" === $c_prev || preg_match('/[\p{P}\p{S}\p{Zs}]/u', $c_prev))
            )
        )) {
            if (!$can_close) {
                $result .= $c_current;
                $chop = substr($chop, 1);
                continue;
            }
            $can_open = true;
            if ('*' === $c_next) {
                $can_open = false;
                $result .= $c_current . $c_next;
                $chop = substr($chop, 2);
                continue;
            }
            $result .= '<em>';
            $chop = substr($chop, 1);
            continue;
        }
        // Right flank
        if ('*' === $c_current && (
            // (1)
            !preg_match('/[\p{Zs}]/u', $c_prev) && (
                // (2a)
                !preg_match('/[\p{P}]/u', $c_prev) ||
                // (2b)
                ("" === $c_next || preg_match('/[\p{P}\p{S}\p{Zs}]/u', $c_next))
            )
        )) {
            if (!$can_open) {
                $result .= $c_current;
                $chop = substr($chop, 1);
                continue;
            }
            $can_close = true;
            if ('*' === $c_prev) {
                $can_close = false;
                $result .= $c_current;
                $chop = substr($chop, 1);
                continue;
            }
            $result .= '</em>';
            $chop = substr($chop, 1);
            continue;
        }
        $result .= $c_current;
        $chop = substr($chop, 1);
    }
    return $result;
}

foreach ([
    '*(*',
    '*(*asdf',
    '*(**)',
    '*(**)*',
    '*(*asdf*)*',
    '*(* asdf *)*',
    '(**)*',
    'asdf*)*',
    '*)*',
] as $test) {
    echo '<pre style="border: 1px solid;">';
    echo htmlspecialchars(emphasize($test));
    echo '</pre>';
}