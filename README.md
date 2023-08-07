Markdown to HTML Converter
==========================

Motivation
----------

I appreciate the [Parsedown](https://github.com/erusev/parsedown) project for its simplicity and speed. It utilizes only
a single class file to transform Markdown syntax to HTML. However, given the decrease in Parsedown project activity over
time, I assume that the project is now in the state of feature complete. The project still has some bugs to fix, and
with [the recent release of PHP version 8.1](https://www.php.net/releases/8.1/en.php), some of the PHP syntax in the
project has become obsolete.

_TODO_

Usage
-----

### Using Composer

From the command line interface, navigate to your project folder then run this command:

~~~ sh
composer require taufik-nurrohman/markdown
~~~

Require the generated auto-loader file in your application:

~~~ php
<?php

use function x\markdown\convert;

require 'vendor/autoload.php';

echo convert('# asdf {#asdf}'); // Returns `'<h1 id="asdf">asdf</h1>'`
~~~

### Using File

Require the `index.php` file in your application:

~~~ php
<?php

use function x\markdown\convert;

require 'index.php';

echo convert('# asdf {#asdf}'); // Returns `'<h1 id="asdf">asdf</h1>'`
~~~