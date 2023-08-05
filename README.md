Markdown to HTML Converter
==========================

Motivation
----------

Why another Markdown converter?

Usage
-----

~~~ php
<?php

use function x\markdown\from as from_markdown;
use function x\markdown\to as to_markdown;

require __DIR__ . '/index.php';

echo from_markdown('# asdf {#a}'); // Returns `'<h1 id="a">asdf</h1>'`
echo to_markdown('<h1 id="a">asdf</h1>'); // Returns `'# asdf {#a}'`
~~~