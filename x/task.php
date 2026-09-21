<?php

require __DIR__ . '/../from.php';

$task = static function (array $rows) use (&$task) {
    if (!$rows) {
        return $rows;
    }
    foreach ($rows as $k => $row) {
        if (is_array($row) && 'ul' === ($row[0] ?? 0)) {
            if (is_array($row[1] ?? 0)) {
                foreach ($row[1] as $kk => $vv) {
                    if (is_array($vv[1] ?? 0)) {
                        $r = $vv[1][$i = array_key_first($vv[1])];
                        if (is_array($r) && 'p' === ($r[0] ?? 0)) {
                            if (is_array($r[1] ?? 0)) {
                                $loose_r = $r[1][$loose_i = array_key_first($r[1])];
                                if (is_array($loose_r) && 'a' === ($loose_r[0] ?? 0) && 'x' === $loose_r[1] && 3 === $loose_r[3][0]) {
                                    $rows[$k][1][$kk][1][$i][1][$loose_i] = [false, '&#x2612;'];
                                } else if (is_string($loose_r) && '[' === ($loose_r[0] ?? 0)) {
                                    if (0 === strpos($loose_r, '[ ] ')) {
                                        $rows[$k][1][$kk][1][$i][1][$loose_i] = substr($loose_r, 3);
                                        array_unshift($rows[$k][1][$kk][1][$i][1], [false, '&#x2610;']);
                                    } else if (0 === strpos($loose_r, '[x] ')) {
                                        $rows[$k][1][$kk][1][$i][1][$loose_i] = substr($loose_r, 3);
                                        array_unshift($rows[$k][1][$kk][1][$i][1], [false, '&#x2612;']);
                                    }
                                }
                            } else if (is_string($r[1]) && '[' === ($r[1][0] ?? 0)) {
                                if (0 === strpos($r[1], '[ ] ')) {
                                    $rows[$k][1][$kk][1][$i][1] = [[false, '&#x2610;'], substr($r[1], 3)];
                                } else if (0 === strpos($r[1], '[x] ')) {
                                    $rows[$k][1][$kk][1][$i][1] = [[false, '&#x2612;'], substr($r[1], 3)];
                                }
                            }
                        } else if (is_array($r) && 'a' === ($r[0] ?? 0) && 'x' === $r[1] && 3 === $r[3][0]) {
                            $rows[$k][1][$kk][1][$i] = [false, '&#x2612;'];
                        } else if (is_string($r) && '[' === ($r[0] ?? 0)) {
                            if (0 === strpos($r, '[ ] ')) {
                                $rows[$k][1][$kk][1][$i] = substr($r, 3);
                                array_unshift($rows[$k][1][$kk][1], [false, '&#x2610;']);
                            } else if (0 === strpos($r, '[x] ')) {
                                $rows[$k][1][$kk][1][$i] = substr($r, 3);
                                array_unshift($rows[$k][1][$kk][1], [false, '&#x2612;']);
                            }
                        }
                    } else if (is_string($vv[1] ?? 0) && '[' === ($vv[1][0] ?? 0)) {
                        if (0 === strpos($vv[1], '[ ] ')) {
                            $rows[$k][1][$kk][1] = [[false, '&#x2610;'], substr($vv[1], 3)];
                        } else if (0 === strpos($vv[1], '[x] ')) {
                            $rows[$k][1][$kk][1] = [[false, '&#x2612;'], substr($vv[1], 3)];
                        }
                    }
                }
            }
            // Add `style` attribute to the list container
            $rows[$k][2]['style'] = 'list-style:none;padding-left:0;';
            continue;
        }
        if (is_array($row[1] ?? 0)) {
            $rows[$k][1] = $task($row[1]);
        }
    }
    return $rows;
};

echo '<!DOCTYPE html>' . "\n";
echo '<html dir="ltr">' . "\n";
echo '<head>' . "\n";
echo '<meta content="width=device-width" name="viewport">' . "\n";
echo '<meta charset="utf-8">' . "\n";
echo '<title>Task List Extension</title>' . "\n";
echo '</head>' . "\n";
echo '<body>' . "\n";

echo x\markdown\from(file_get_contents(__DIR__ . '/task.md'), [
    'tab' => 0,
    'with' => [$task]
]) . "\n";

echo '</body>' . "\n";
echo '</html>';