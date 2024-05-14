<?php

$chop = <<<TEST
asdf *asdf*

*(*

*)*
TEST;

function emphasize($chop) {
    $result = "";
    while ("" !== $chop) {
        $c_prev = substr($result, -1);
        $c_current = $chop[0];
        $c_next = $chop[1] ?? "";
        $c_next_is_p = preg_match('/[\p{P}\p{S}]/u', $c_next);
        $c_next_is_w = "" === $c_next || preg_match('/[\p{Zs}]/u', $c_next);
        $c_prev_is_p = preg_match('/[\p{P}\p{S}]/u', $c_prev);
        $c_prev_is_w = "" === $c_prev || preg_match('/[\p{Zz}]/u', $c_prev);
        $left_flank = !$c_next_is_w && (!$c_next_is_p || $c_prev_is_w || $c_prev_is_p);
        $right_flank = !$c_prev_is_w && (!$c_prev_is_p || $c_next_is_w || $c_next_is_p);
        if ('_' === $c_current) {
            $can_open = $left_flank && (!$right_flank || $c_prev_is_p);
            $can_close = $right_flank && (!$left_flank || $c_next_is_p);
        } else if ('"' === $c_current || "'" === $c_current) {
            $can_open = $left_flank && !$right_flank;
            $can_close = $right_flank;
        } else {
            $can_open = $left_flank;
            $can_close = $right_flank;
        }
        // // Left flank
        // if ('*' === $c_current && (
        //     // (1)
        //     !preg_match('/[\p{Zs}]/u', $c_next) && (
        //         // (2a)
        //         !preg_match('/[\p{P}]/u', $c_next) ||
        //         // (2b)
        //         ("" === $c_prev || preg_match('/[\p{P}\p{S}\p{Zs}]/u', $c_prev))
        //     )
        // )) {
        //     $result .= '<em>';
        //     $chop = substr($chop, 1);
        //     continue;
        // }
        // // Right flank
        // if ('*' === $c_current && (
        //     // (1)
        //     !preg_match('/[\p{Zs}]/u', $c_prev) && (
        //         // (2a)
        //         !preg_match('/[\p{P}]/u', $c_prev) ||
        //         // (2b)
        //         ("" === $c_next || preg_match('/[\p{P}\p{S}\p{Zs}]/u', $c_next))
        //     )
        // )) {
        //     $result .= '</em>';
        //     $chop = substr($chop, 1);
        //     continue;
        // }
        if ('*' === $c_current && $can_open) {
            $result .= '<em>';
            $chop = substr($chop, 1);
            continue;
        }
        if ('*' === $c_current && $can_close) {
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