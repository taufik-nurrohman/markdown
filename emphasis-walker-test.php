<?php

/*

(?>(?>(?<=^|[\p{P}\p{S}\p{Zs}])[*]{2}(?=[\p{P}\p{S}\p{Zs}])|[*]{2}(?![\p{P}\p{S}\p{Zs}]))(?>`[^`]+`|[^*\n\\\\]|\\.|(?R))+?(?>(?<![\p{P}\p{S}\p{Zs}])[*]{2}(?![*]+[^\p{Zs}])|(?<=[\p{P}\p{S}\p{Zs}])[*]{2}(?![*])(?=[\p{P}\p{S}\p{Zs}]|$))|(?>(?<=^|[\p{P}\p{S}\p{Zs}])[*](?=[\p{P}\p{S}\p{Zs}])|[*](?![\p{P}\p{S}\p{Zs}]))(?>`[^`]+`|[^*\n\\\\]|\\.|(?R))+?(?>(?<![\p{P}\p{S}\p{Zs}])[*](?![*]+[^\p{Zs}])|(?<=[\p{P}\p{S}\p{Zs}])[*](?![*])(?=[\p{P}\p{S}\p{Zs}]|$)))

*/

function emphasize($chop) {
    $result = [];
    $stack = 0;
    $c_prev = "";
    while ("" !== $chop) {
        $c_current = $chop[0];
        $c_next = $chop[1] ?? "";
        $c_next_is_p = preg_match('/[\p{P}\p{S}]/u', $c_next);
        $c_next_is_w = "" === $c_next || preg_match('/[\p{Zs}]/u', $c_next);
        $c_prev_is_p = preg_match('/[\p{P}\p{S}]/u', $c_prev);
        $c_prev_is_w = "" === $c_prev || preg_match('/[\p{Zs}]/u', $c_prev);
        $left_flank = !$c_next_is_w && (!$c_next_is_p || $c_prev_is_w || $c_prev_is_p);
        $right_flank = !$c_prev_is_w && (!$c_prev_is_p || $c_next_is_w || $c_next_is_p);
        if ('_' === $c_current) {
            $can_open = $left_flank && (!$right_flank || $c_prev_is_p);
            $can_close = $right_flank && (!$left_flank || $c_next_is_p);
        } else if ('"' === $c_current || "'" === $c_current) {
            $can_open = $left_flank && !$right_flank;
            $can_close = $right_flank;
        } else if ('*' === $c_current) {
            $can_open = $left_flank;
            $can_close = $right_flank;
        } else {
            $can_open = false;
            $can_close = false;
        }
        if ($c_current === $c_prev) {
            $result[count($result) - 1][0] = '<strong>';
            $result[count($result) - 1][1] .= $c_current;
            $chop = substr($chop, 1);
            continue;
        }
        if ($c_next === $c_current) {
            $c_current .= $c_next;
            $chop = substr($chop, 1);
            continue;
        }
        if ($can_open) {
            $result[] = ['<em>', $c_prev = $c_current];
            $chop = substr($chop, 1);
            $stack += 1;
            continue;
        }
        if ($can_close) {
            $result[] = ['</em>', $c_prev = $c_current];
            $chop = substr($chop, 1);
            $stack -= 1;
            continue;
        }
        $result[] = $c_prev = $c_current;
        $chop = substr($chop, 1);
    }
    echo $stack . '<br>';
    if (0 !== $stack) {
        foreach ($result as &$r) {
            if (is_array($r)) {
                $r = $r[0];
            }
        }
        return implode("", $result);
    }
    foreach ($result as &$r) {
        if (is_array($r)) {
            $r = $r[1];
        }
    }
    return implode("", $result);
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
    '**asdf*asdf*',
    '*asdf*asdf**',
    '**asdf* asdf*',
    '*asdf *asdf**',
] as $test) {
    echo '<pre style="border: 1px solid;">';
    echo htmlspecialchars(emphasize($test));
    echo '</pre>';
}

echo '<hr>';

foreach ([
    '_(_',
    '_(_asdf',
    '_(__)',
    '_(__)_',
    '_(_asdf_)_',
    '_(_ asdf _)_',
    '(__)_',
    'asdf_)_',
    '_)_',
    '__asdf_asdf_',
    '_asdf_asdf__',
    '__asdf_ asdf_',
    '_asdf _asdf__',
] as $test) {
    echo '<pre style="border: 1px solid;">';
    echo htmlspecialchars(emphasize($test));
    echo '</pre>';
}