<?php

$t = hrtime(true);
$r = (new ParsedownExtra)->text($content);
$t = (hrtime(true) - $t) / 1e6;

$with = 'Parsedown Extra';

return $r;