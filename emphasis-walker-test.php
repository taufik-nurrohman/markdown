<?php

function emphasize($chop) {
    $result = [];
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
            $can_close = $right_flank && (!$left_flank || $c_next_is_p);
            $can_open = $left_flank && (!$right_flank || $c_prev_is_p);
        } else if ('"' === $c_current || "'" === $c_current) {
            $can_close = $right_flank;
            $can_open = $left_flank && !$right_flank;
        } else if ('*' === $c_current) {
            $can_close = $right_flank;
            $can_open = $left_flank;
        } else {
            $can_close = false;
            $can_open = false;
        }
        if ($can_close || $can_open) {
            $result[] = [
                'close' => $can_close,
                'current' => $c_prev = $c_current,
                'open' => $can_open
            ];
            $chop = substr($chop, 1);
            continue;
        }
        $result[] = $c_prev = $c_current;
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
    '**asdf*asdf*',
    '*asdf*asdf**',
    '**asdf* asdf*',
    '*asdf *asdf**',
] as $test) {
    echo '<pre style="border: 1px solid;">';
    echo htmlspecialchars($test) . "\n\n";
    echo htmlspecialchars(json_encode(emphasize($test), JSON_PRETTY_PRINT));
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
    echo htmlspecialchars($test) . "\n\n";
    echo htmlspecialchars(json_encode(emphasize($test), JSON_PRETTY_PRINT));
    echo '</pre>';
}

echo '<hr/>';

foreach ([
    '**asdf*asdf*asdf**',
    '**asdf* asdf *asdf**',
    '**asdf *asdf* asdf**'
] as $test) {
    echo '<pre style="border: 1px solid;">';
    echo htmlspecialchars($test) . "\n\n";
    echo htmlspecialchars(json_encode(emphasize($test), JSON_PRETTY_PRINT));
    echo '</pre>';
}