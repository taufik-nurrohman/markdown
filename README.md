Markdown to HTML Converter
==========================

Motivation
----------

Why another Markdown converter?

Usage
-----

~~~ php
<?php

use function x\markdown\convert;

require __DIR__ . '/index.php';

echo convert('# asdf {#a}'); // Returns `'<h1 id="a">asdf</h1>'`
~~~