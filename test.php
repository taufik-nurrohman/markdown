<?php

$out = '<!DOCTYPE html>';
$out .= '<html dir="ltr">';
$out .= '<head>';
$out .= '<meta charset="utf-8">';
$out .= '<title>';
$out .= 'Test';
$out .= '</title>';
$out .= '</head>';
$out .= '<body>';

$out .= '<h1>';
$out .= 'Tests';
$out .= '</h1>';
$out .= '<ul>';
$out .= '<li>';
$out .= '<a href="test/from.php">';
$out .= 'from.php';
$out .= '</a>';
$out .= '</li>';
$out .= '<li>';
$out .= '<a href="test/to.php">';
$out .= 'to.php';
$out .= '</a>';
$out .= '</li>';
$out .= '</ul>';

$out .= '</body>';
$out .= '</html>';

echo $out;