<?php

require __DIR__ . '/../from.php';

$strike = static function (array $rows) use (&$strike) {
    if (!$rows) {
        return $rows;
    }
    foreach ($rows as $k => $row) {
        if (!is_array($row)) {
            continue;
        }
        if (false !== $row[0] && is_string($row[1] ?? 0)) {
            $row[1] = [$row[1]];
        }
        if (!is_array($row[1] ?? 0)) {
            continue;
        }
        $children = [];
        foreach ($row[1] as $child) {
            if (is_array($child) && false === ($child[0] ?? 0)) {
                $children[] = $child;
                continue;
            }
            if (is_array($child) && isset($child[1]) && 'code' !== ($child[0] ?? 0)) {
                if (is_array($child[1])) {
                    $child[1] = $strike($child[1]);
                } else if (is_string($child[1])) {
                    $child = $strike([$child])[0];
                }
            }
            $children[] = $child;
        }
        $tokens = [];
        foreach ($children as $child) {
            if (!is_string($child)) {
                $tokens[] = $child;
                continue;
            }
            // <https://github.github.com/gfm#example-491>
            foreach (preg_split('/(~{1,2}(?!\s)|(?<!\s)~{1,2})/', $child, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) as $part) {
                $tokens[] = $part;
            }
        }
        $i = 0;
        $max = count($tokens);
        $r = [];
        while ($i < $max) {
            $c = $tokens[$i];
            if ('~' === $c || '~~' === $c) {
                $end = false;
                $v = [];
                for ($j = $i + 1; $j < $max; ++$j) {
                    if ($c === $tokens[$j]) {
                        $end = $j;
                        break;
                    }
                    $v[] = $tokens[$j];
                }
                if (false !== $end) {
                    $i = $end + 1;
                    $r[] = ['del', 1 === count($v) && is_string($v[0]) ? $v[0] : $v, [], [strlen($c), $c]];
                    continue;
                }
            }
            $r[] = $c;
            $i++;
        }
        $rows[$k][1] = $r;
        continue;
    }
    return $rows;
};

echo '<!DOCTYPE html>' . "\n";
echo '<html dir="ltr">' . "\n";
echo '<head>' . "\n";
echo '<meta content="width=device-width" name="viewport">' . "\n";
echo '<meta charset="utf-8">' . "\n";
echo '<title>Strike Extension</title>' . "\n";
echo '</head>' . "\n";
echo '<body>' . "\n";

$value = file_get_contents(__DIR__ . '/strike.md');

// Save escaped `~` to allow literal use of `~` character(s) in the HTML result with `\~`
$value = preg_replace_callback('/(?<!\\\\)(?:\\\\\\\\)*\K\\\\~/', static function () {
    return "\x1a";
}, $value);

$value = x\markdown\from($value, [
    'tab' => 0,
    'with' => [$strike]
]);

// Restore escaped `~`
$value = strtr($value, ["\x1a" => '~']);

echo $value . "\n";

echo '</body>' . "\n";
echo '</html>';