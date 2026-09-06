<?php

$t = hrtime(true);
$r = x\markdown\f($content);
$t = (hrtime(true) - $t) / 1e6;

$with = 'My Markdown Parser';

return $r;