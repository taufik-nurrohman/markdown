<?php

require __DIR__ . '/../from.php';

$strike = [
    // Pre-parse
    static function (?string $value) {
        // Save escaped `~` to allow literal use of `~` character(s) in the HTML result with `\~`
        return $value ? preg_replace('/(?<!\\\\)((?:\\\\\\\\)*)\\\\~/', '$1' . "\x1a", $value) : $value;
    },
    // Parse
    static function (array $rows) use (&$strike) {
        if (!$rows) {
            return $rows;
        }
        foreach ($rows as $k => $row) {
            if (!is_array($row)) {
                continue;
            }
            if (false !== $row[0] && is_string($row[1] ?? 0)) {
                // Normalize string body to array
                $row[1] = [$row[1]];
            }
            if (!is_array($row[1] ?? 0)) {
                continue;
            }
            $tokens = [];
            foreach ($row[1] as $r) {
                if (is_array($r)) {
                    // Keep raw HTML as it is
                    if (false === ($r[0] ?? 0)) {
                        $tokens[] = $r;
                        continue;
                    }
                    if (isset($r[1]) && 'code' !== ($r[0] ?? 0)) {
                        if (is_array($r[1])) {
                            $r[1] = $strike[1]($r[1]);
                        } elseif (is_string($r[1])) {
                            $r = $strike[1]([$r])[0];
                        }
                    }
                    $tokens[] = $r;
                    continue;
                }
                // <https://github.github.com/gfm#example-491>
                // <https://github.github.com/gfm#example-493>
                foreach (preg_split('/((?<!~)~{1,2}(?![~\s])|(?<![~\s])~{1,2}(?!~))/', $r, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) as $part) {
                    $tokens[] = $part;
                }
            }
            $count = count($tokens);
            $i = 0;
            $r = [];
            while ($i < $count) {
                $c = $tokens[$i];
                if ('~' === $c || '~~' === $c) {
                    $end = false;
                    $v = [];
                    for ($j = $i + 1; $j < $count; ++$j) {
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
                ++$i;
            }
            $rows[$k][1] = $r;
            continue;
        }
        return $rows;
    },
    // Post-parse
    static function (?string $value) {
        // Restore escaped `~`
        return $value ? strtr($value, ["\x1a" => '~']) : $value;
    }
];

echo '<!DOCTYPE html>' . "\n";
echo '<html dir="ltr">' . "\n";
echo '<head>' . "\n";
echo '<meta content="width=device-width" name="viewport">' . "\n";
echo '<meta charset="utf-8">' . "\n";
echo '<title>Strike Extension</title>' . "\n";
echo '</head>' . "\n";
echo '<body>' . "\n";

echo x\markdown\from(file_get_contents(__DIR__ . '/strike.md'), [
    'tab' => 0,
    'with' => [$strike]
]) . "\n";

echo '</body>' . "\n";
echo '</html>';