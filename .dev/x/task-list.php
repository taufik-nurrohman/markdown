<?php

require __DIR__ . '/../from.php';

$task_list = static function (array $rows) use (&$task_list) {
    if (!$rows) {
        return $rows;
    }
    foreach ($rows as $k => $row) {
        if (is_array($row) && 'li' === ($row[0] ?? 0)) {
            if (is_array($row[1])) {
                $first = $row[1][$key = array_key_first($row[1])];
                if (is_array($first) && 'a' === ($first[0] ?? 0) && 'x' === $first[1] && 3 === $first[3][0]) {
                    $rows[$k][1][$key] = [false, '&#x2612;'];
                } else if (is_string($first) && '[' === ($first[0] ?? 0)) {
                    if (0 === strpos($first, '[ ] ')) {
                        $rows[$k][1][$key] = substr($first, 3);
                        array_unshift($rows[$k][1], [false, '&#x2610;']);
                    } else if (0 === strpos($first, '[x] ')) {
                        $rows[$k][1][$key] = substr($first, 3);
                        array_unshift($rows[$k][1], [false, '&#x2612;']);
                    }
                }
            } else if (is_string($row[1]) && '[' === ($row[1][0] ?? 0)) {
                if (0 === strpos($row[1], '[ ] ')) {
                    $rows[$k][1] = [[false, '&#x2610;'], substr($row[1], 3)];
                } else if (0 === strpos($row[1], '[x] ')) {
                    $rows[$k][1] = [[false, '&#x2612;'], substr($row[1], 3)];
                }
            }
            continue;
        }
        if (is_array($row[1] ?? 0)) {
            $rows[$k][1] = $task_list($row[1]);
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

echo x\markdown\from(file_get_contents(__DIR__ . '/task-list.md'), [
    'tab' => 0,
    'with' => [$task_list]
]) . "\n";

echo '</body>' . "\n";
echo '</html>';