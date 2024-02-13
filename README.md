PHP Markdown Parser
===================

![from.php] ![to.php]

[from.php]: https://img.shields.io/github/size/taufik-nurrohman/markdown/from.php?branch=main&color=%234f5d95&label=from.php&labelColor=%231f2328&style=flat-square
[to.php]: https://img.shields.io/github/size/taufik-nurrohman/markdown/to.php?branch=main&color=%234f5d95&label=to.php&labelColor=%231f2328&style=flat-square

With 90% compliance to [CommonMark 0.31.2](https://spec.commonmark.org/0.31.2) specifications.

Motivation
----------

<picture>
  <source media="(prefers-color-scheme: dark)" srcset="https://github.com/taufik-nurrohman/markdown/assets/1669261/0a488f4a-0450-4e0a-8137-196a4b0657b0">
  <source media="(prefers-color-scheme: light)" srcset="https://github.com/taufik-nurrohman/markdown/assets/1669261/164b592d-e8db-4e28-be5d-467522f65f0d">
  <img alt="Why?" src="https://github.com/taufik-nurrohman/markdown/assets/1669261/164b592d-e8db-4e28-be5d-467522f65f0d">
</picture>

I appreciate the [Parsedown](https://github.com/erusev/parsedown) project for its simplicity and speed. It uses only a
single class file to convert Markdown syntax to HTML. However, given the decrease in Parsedown project activity over
time, I assume that it is now in the state of “feature complete”. It still has some bugs to fix, and with
[the recent release of PHP version 8.1](https://www.php.net/releases/8.1/en.php), some of the PHP syntax there has
become obsolete.

There is actually [a draft for Parsedown version 2.0](https://github.com/erusev/parsedown/tree/2.0.x), but it is no
longer made as a single class file. It’s broken down into components. The goal, I think, is to make it easy to add
functionality without breaking what’s already in the core. For others, it may be of great use, but I see it as a form of
similarity to the features provided by
[CommonMark](https://github.com/thephpleague/commonmark/blob/2.4/docs/2.4/customization/extensions.md). Because of that,
if I want to update, it might be more optimal to just switch to CommonMark.

I’m not into things like that. As someone who needs a function to convert Markdown syntax to HTML, that kind of
flexibility is completely unnecessary to me. I just want to convert Markdown syntax to HTML for once and then move on.
It was fulfilled by [Parsedown version 1.8](https://github.com/erusev/parsedown/tree/1.8.x-beta), but it seems that it
is no longer being actively maintained.

The goal of this project is to use it in my [Markdown extension for Mecha](https://github.com/mecha-cms/x.markdown) in
the future. Previously, I wanted to develop this converter directly into the extension, but my friend advised me to
create this project separately as it might have potential to be used by other developers beyond the
[Mecha CMS](https://github.com/mecha-cms) developers.

Usage
-----

This converter can be installed using [Composer](https://packagist.org/packages/taufik-nurrohman/markdown), but it
doesn’t need any other dependencies and just uses Composer’s ability to automatically include files. Those of you who
don’t use Composer should be able to include the `from.php` and `to.php` files directly into your application without
any problems.

### Using Composer

From the command line interface, navigate to your project folder then run this command:

~~~ sh
composer require taufik-nurrohman/markdown
~~~

Require the generated auto-loader file in your application:

~~~ php
<?php

use function x\markdown\from as from_markdown;
use function x\markdown\to as to_markdown;

require 'vendor/autoload.php';

echo from_markdown('# asdf {#asdf}'); // Returns `'<h1 id="asdf">asdf</h1>'`
~~~

### Using File

Require the `from.php` and `to.php` files in your application:

~~~ php
<?php

use function x\markdown\from as from_markdown;
use function x\markdown\to as to_markdown;

require 'from.php';
require 'to.php';

echo from_markdown('# asdf {#asdf}'); // Returns `'<h1 id="asdf">asdf</h1>'`
~~~

The `to.php` file is optional and is used to convert HTML to Markdown. If you just want to convert Markdown to HTML, you
don’t need to include this file. This feature is experimental and is provided as a complementary feature, as there is
function `json_encode()` besides function `json_decode()`. The Markdown result may not satisfy everyone, but it can be
discussed further.

Options
-------

~~~ php
/**
 * Convert Markdown string to HTML string.
 *
 * @param null|string $value Your Markdown string.
 * @param bool $block If this option is set to `false`, Markdown block syntax will be ignored.
 * @return null|string
 */
from(?string $value, bool $block = true): ?string;
~~~

~~~ php
/**
 * Convert HTML string to Markdown string.
 *
 * @param null|string $value Your HTML string.
 * @param bool $block If this option is set to `false`, HTML block syntax will be stripped off.
 * @return null|string
 */
to(?string $value, bool $block = true): ?string;
~~~

Dialect
-------

From time to time, the history of Mecha slowly forms my Markdown writing style. The Markdown extension used by Mecha
[was first](https://github.com/mecha-cms/mecha/tree/v1.2.2) built with
[Michel Fortin’s Markdown converter](https://michelf.ca/projects/php-markdown) (which I believe is the very first port
of a PHP-based Markdown converter originally written in Perl by
[John Gruber](https://daringfireball.net/projects/markdown)). Until the release of
[Mecha version 1.2.3](https://github.com/mecha-cms/mecha/tree/v1.2.3), I decided to switch to
[Parsedown](https://github.com/erusev/parsedown) because it was quite popular at the time. It can also do the conversion
process much faster. Emanuil Rusev’s way of detecting the block type
[by reading the first character](https://github.com/erusev/parsedown/tree/1.7.4#questions) is, in my opinion, very
clever and efficient.

### Attributes

My Markdown converter supports a more extensive attribute syntax, including a mix of `.class` and `#id` attribute
syntax, and a mix of `key=value` attribute syntax:

<table>
  <thead>
    <tr>
      <th>Markdown</th>
      <th>HTML</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td><pre><code># asdf {#asdf}</code></pre></td>
      <td><pre><code>&lt;h1 id="asdf"&gt;asdf&lt;/h1&gt;</code></pre></td>
    </tr>
    <tr>
      <td><pre><code># asdf {#asdf.asdf}</code></pre></td>
      <td><pre><code>&lt;h1 class="asdf" id="asdf"&gt;asdf&lt;/h1&gt;</code></pre></td>
    </tr>
    <tr>
      <td><pre><code># asdf {#asdf.asdf asdf=asdf}</code></pre></td>
      <td><pre><code>&lt;h1 asdf="asdf" class="asdf" id="asdf"&gt;asdf&lt;/h1&gt;</code></pre></td>
    </tr>
  </tbody>
</table>

Inline attributes always win over native syntax attributes and pre-defined attributes:

<table>
  <thead>
    <tr>
      <th>Markdown</th>
      <th>HTML</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td><pre><code>[asdf](asdf) {href=x}</code></pre></td>
      <td><pre><code>&lt;a href="x"&gt;asdf&lt;/a&gt;</code></pre></td>
    </tr>
    <tr>
      <td><pre><code>[asdf]&#10;&#10;[asdf]: asdf {href=x}</code></pre></td>
      <td><pre><code>&lt;a href="x"&gt;asdf&lt;/a&gt;</code></pre></td>
    </tr>
    <tr>
      <td><pre><code>[asdf] {.x href=x}&#10;&#10;[asdf]: asdf {.asdf}</code></pre></td>
      <td><pre><code>&lt;a class="x" href="x"&gt;asdf&lt;/a&gt;</code></pre></td>
    </tr>
  </tbody>
</table>

### Links

Relative links and absolute links with the server’s host name will be treated as internal links, otherwise they will be
treated as external links and will automatically get `rel="nofollow"` and `target="_blank"` attributes.

### Notes

Notes follow the [Markdown Extra’s notes syntax](https://michelf.ca/projects/php-markdown/extra#footnotes) but with
slightly different HTML output to match [Mecha](https://github.com/mecha-cms)’s common naming style. Multi-line notes
don’t have to be indented by four spaces as required by Markdown Extra. A space or tab is enough to continue the note.

<table>
  <thead>
    <tr>
      <th>Markdown</th>
      <th>HTML</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td><pre><code>asdf [^1]&#10;&#10;[^1]: asdf</code></pre></td>
      <td><pre><code>&lt;p&gt;asdf &lt;sup id="from:1"&gt;&lt;a href="#to:1" role="doc-noteref"&gt;1&lt;/a&gt;&lt;/sup&gt;&lt;/p&gt;&lt;div role="doc-endnotes"&gt;&lt;hr /&gt;&lt;ol&gt;&lt;li id="to:1"&gt;&lt;p&gt;asdf&amp;#160;&lt;a href="#from:1" role="doc-backlink"&gt;&amp;#8617;&lt;/a&gt;&lt;/p&gt;&lt;/li&gt;&lt;/ol&gt;&lt;/div&gt;</code></pre></td>
    </tr>
    <tr>
      <td><pre><code>asdf [^1]&#10;&#10;[^1]:&#10;&#10;  asdf&#10;  ====&#10;&#10;  asdf&#10;  asdf&#10;&#10;      asdf&#10;&#10;  asdf&#10;  asdf&#10;&#10;asdf</code></pre></td>
      <td><pre><code>&lt;p&gt;asdf &lt;sup id="from:1"&gt;&lt;a href="#to:1" role="doc-noteref"&gt;1&lt;/a&gt;&lt;/sup&gt;&lt;/p&gt;&lt;p&gt;asdf&lt;/p&gt;&lt;div role="doc-endnotes"&gt;&lt;hr /&gt;&lt;ol&gt;&lt;li id="to:1"&gt;&lt;h1&gt;asdf&lt;/h1&gt;&lt;p&gt;asdf asdf&lt;/p&gt;&lt;pre&gt;&lt;code&gt;asdf&lt;/code&gt;&lt;/pre&gt;&lt;p&gt;asdf asdf&amp;#160;&lt;a href="#from:1" role="doc-backlink"&gt;&amp;#8617;&lt;/a&gt;&lt;/p&gt;&lt;/li&gt;&lt;/ol&gt;&lt;/div&gt;</code></pre></td>
    </tr>
  </tbody>
</table>


### Soft Break

Soft breaks are collapsed to spaces in non-critical parts such as in paragraphs and list items:

<table>
  <thead>
    <tr>
      <th>Markdown</th>
      <th>HTML</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td><pre><code>asdf asdf asdf asdf&#10;asdf asdf asdf asdf&#10;&#10;asdf asdf asdf asdf</code></pre></td>
      <td><pre><code>&lt;p&gt;asdf asdf asdf asdf asdf asdf asdf asdf&lt;/p&gt;&lt;p&gt;asdf asdf asdf asdf&lt;/p&gt;</code></pre></td>
    </tr>
  </tbody>
</table>

### Code Block

I try to avoid conflict between different Markdown dialects and try to support whatever dialect you are using. For
example, since I originally used Markdown Extra, I am used to adding info string with a dot prefix to the fenced code
block syntax. This is not supported by Parsedown (or rather, Parsedown doesn’t care about the pattern of the given info
string and simply appends `language-` prefix to it, since CommonMark also doesn’t give implementors special rules for
processing info string in fenced code block syntax).

Here’s how the code block results compare across each Markdown converter:

#### Markdown Extra

<table>
  <thead>
    <tr>
      <th>Markdown</th>
      <th>HTML</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td><pre><code>~~~ asdf&#10;asdf&#10;~~~</code></pre></td>
      <td><pre><code>&lt;pre&gt;&lt;code class="asdf"&gt;asdf&#10;&lt;/code&gt;&lt;/pre&gt;</code></pre></td>
    </tr>
    <tr>
      <td><pre><code>~~~ .asdf&#10;asdf&#10;~~~</code></pre></td>
      <td><pre><code>&lt;pre&gt;&lt;code class="asdf"&gt;asdf&#10;&lt;/code&gt;&lt;/pre&gt;</code></pre></td>
    </tr>
    <tr>
      <td><pre><code>~~~ asdf asdf&#10;asdf&#10;~~~</code></pre></td>
      <td><em>Invalid.</em></td>
    </tr>
    <tr>
      <td><pre><code>~~~ .asdf.asdf&#10;asdf&#10;~~~</code></pre></td>
      <td><em>Invalid.</em></td>
    </tr>
    <tr>
      <td><pre><code>~~~ {#asdf.asdf}&#10;asdf&#10;~~~</code></pre></td>
      <td><pre><code>&lt;pre&gt;&lt;code class="asdf" id="asdf"&gt;asdf&#10;&lt;/code&gt;&lt;/pre&gt;</code></pre></td>
    </tr>
    <tr>
      <td><pre><code>~~~ {#asdf.asdf asdf=asdf}&#10;asdf&#10;~~~</code></pre></td>
      <td><em>Invalid.</em></td>
    </tr>
  </tbody>
</table>

#### Parsedown Extra

<table>
  <thead>
    <tr>
      <th>Markdown</th>
      <th>HTML</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td><pre><code>~~~ asdf&#10;asdf&#10;~~~</code></pre></td>
      <td><pre><code>&lt;pre&gt;&lt;code class="language-asdf"&gt;asdf&lt;/code&gt;&lt;/pre&gt;</code></pre></td>
    </tr>
    <tr>
      <td><pre><code>~~~ .asdf&#10;asdf&#10;~~~</code></pre></td>
      <td><pre><code>&lt;pre&gt;&lt;code class="language-.asdf"&gt;asdf&lt;/code&gt;&lt;/pre&gt;</code></pre></td>
    </tr>
    <tr>
      <td><pre><code>~~~ asdf asdf&#10;asdf&#10;~~~</code></pre></td>
      <td><pre><code>&lt;pre&gt;&lt;code class="language-asdf"&gt;asdf&lt;/code&gt;&lt;/pre&gt;</code></pre></td>
    </tr>
    <tr>
      <td><pre><code>~~~ .asdf.asdf&#10;asdf&#10;~~~</code></pre></td>
      <td><pre><code>&lt;pre&gt;&lt;code class="language-.asdf.asdf"&gt;asdf&lt;/code&gt;&lt;/pre&gt;</code></pre></td>
    </tr>
    <tr>
      <td><pre><code>~~~ {#asdf.asdf}&#10;asdf&#10;~~~</code></pre></td>
      <td><pre><code>&lt;pre&gt;&lt;code class="language-{#asdf.asdf}"&gt;asdf&lt;/code&gt;&lt;/pre&gt;</code></pre></td>
    </tr>
    <tr>
      <td><pre><code>~~~ {#asdf.asdf asdf=asdf}&#10;asdf&#10;~~~</code></pre></td>
      <td><pre><code>&lt;pre&gt;&lt;code class="language-{#asdf.asdf"&gt;asdf&lt;/code&gt;&lt;/pre&gt;</tr>
  </tbody>
</table>

#### Mine

<table>
  <thead>
    <tr>
      <th>Markdown</th>
      <th>HTML</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td><pre><code>~~~ asdf&#10;asdf&#10;~~~</code></pre></td>
      <td><pre><code>&lt;pre&gt;&lt;code class="language-asdf"&gt;asdf&lt;/code&gt;&lt;/pre&gt;</code></pre></td>
    </tr>
    <tr>
      <td><pre><code>~~~ .asdf&#10;asdf&#10;~~~</code></pre></td>
      <td><pre><code>&lt;pre&gt;&lt;code class="asdf"&gt;asdf&lt;/code&gt;&lt;/pre&gt;</code></pre></td>
    </tr>
    <tr>
      <td><pre><code>~~~ asdf asdf&#10;asdf&#10;~~~</code></pre></td>
      <td><pre><code>&lt;pre&gt;&lt;code class="language-asdf"&gt;asdf&lt;/code&gt;&lt;/pre&gt;</code></pre></td>
    </tr>
    <tr>
      <td><pre><code>~~~ .asdf.asdf&#10;asdf&#10;~~~</code></pre></td>
      <td><pre><code>&lt;pre&gt;&lt;code class="asdf"&gt;asdf&lt;/code&gt;&lt;/pre&gt;</code></pre></td>
    </tr>
    <tr>
      <td><pre><code>~~~ {#asdf.asdf}&#10;asdf&#10;~~~</code></pre></td>
      <td><pre><code>&lt;pre&gt;&lt;code class="asdf" id="asdf"&gt;asdf&lt;/code&gt;&lt;/pre&gt;</code></pre></td>
    </tr>
    <tr>
      <td><pre><code>~~~ {#asdf.asdf asdf=asdf}&#10;asdf&#10;~~~</code></pre></td>
      <td><pre><code>&lt;pre&gt;&lt;code asdf="asdf" class="asdf" id="asdf"&gt;asdf&lt;/code&gt;&lt;/pre&gt;</tr>
  </tbody>
</table>

### HTML Block

CommonMark doesn’t care about the DOM and therefore also doesn’t care if a HTML element is perfectly balanced or not.
Unlike the original Markdown syntax specification which doesn’t allow you to convert Markdown syntax inside a HTML
block, the CommonMark specification doesn’t limit such a case. It cares about blank lines around the lines that look
like a HTML block tag, as specified in [Section 4.6](https://spec.commonmark.org/0.30#html-blocks), type 6.

Any text that comes after the opening and/or closing of a HTML block is treated as raw text and is not processed as
Markdown syntax. A blank line is required to end the raw HTML block state:

<table>
  <thead>
    <tr>
      <th>Markdown</th>
      <th>HTML</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td><pre><code>&lt;div&gt; asdf asdf &#42;asdf&#42; asdf&#10;&lt;/div&gt; asdf asdf &#42;asdf&#42; asdf</code></pre></td>
      <td><pre><code>&lt;div&gt; asdf asdf &#42;asdf&#42; asdf&#10;&lt;/div&gt; asdf asdf &#42;asdf&#42; asdf</code></pre></td>
    </tr>
    <tr>
      <td><pre><code>&lt;div&gt;&#10;asdf asdf &#42;asdf&#42; asdf&#10;&#10;&lt;/div&gt;&#10;asdf asdf &#42;asdf&#42; asdf</code></pre></td>
      <td><pre><code>&lt;div&gt;&#10;asdf asdf &#42;asdf&#42; asdf&lt;/div&gt;&#10;asdf asdf &#42;asdf&#42; asdf</code></pre></td>
    </tr>
    <tr>
      <td><pre><code>&lt;div&gt;&#10;&#10;asdf asdf &#42;asdf&#42; asdf&#10;&#10;&lt;/div&gt;&#10;&#10;asdf asdf &#42;asdf&#42; asdf</code></pre></td>
      <td><pre><code>&lt;div&gt;&lt;p&gt;asdf asdf &lt;em&gt;asdf&lt;/em&gt; asdf&lt;/p&gt;&lt;/div&gt;&lt;p&gt;asdf asdf &lt;em&gt;asdf&lt;/em&gt; asdf&lt;/p&gt;</code></pre></td>
    </tr>
  </tbody>
</table>

Exception for types 1, 2, 3, 4, and 5. A line break is enough to end the raw HTML block state:

<table>
  <thead>
    <tr>
      <th>Markdown</th>
      <th>HTML</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td><pre><code>&lt;!-- asdf asdf &#42;asdf&#42; asdf --&gt; asdf asdf &#42;asdf&#42; asdf</code></pre></td>
      <td><pre><code>&lt;!-- asdf asdf &#42;asdf&#42; asdf --&gt; asdf asdf &#42;asdf&#42; asdf</code></pre></td>
    </tr>
    <tr>
      <td><pre><code>&lt;!-- asdf asdf &#42;asdf&#42; asdf --&gt;&#10;asdf asdf &#42;asdf&#42; asdf</code></pre></td>
      <td><pre><code>&lt;!-- asdf asdf &#42;asdf&#42; asdf --&gt;&lt;p&gt;asdf asdf &lt;em&gt;asdf&lt;/em&gt; asdf&lt;/p&gt;</code></pre></td>
    </tr>
    <tr>
      <td><pre><code>&lt;!-- asdf asdf &#42;asdf&#42; asdf --&gt;&#10;&#10;asdf asdf &#42;asdf&#42; asdf</code></pre></td>
      <td><pre><code>&lt;!-- asdf asdf &#42;asdf&#42; asdf --&gt;&lt;p&gt;asdf asdf &lt;em&gt;asdf&lt;/em&gt; asdf&lt;/p&gt;</code></pre></td>
    </tr>
  </tbody>
</table>

The examples below will generate a predictable HTML code, but not because this converter cares about the existing HTML
tag balance:

<table>
  <thead>
    <tr>
      <th>Markdown</th>
      <th>HTML</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td><pre><code>&lt;nav&gt;&#10;&lt;ul&gt;&#10;&lt;li&gt;&#10;&lt;a&gt;asdf&lt;/a&gt;&#10;&lt;/li&gt;&#10;&lt;li&gt;&#10;&lt;a&gt;asdf&lt;/a&gt;&#10;&lt;/li&gt;&#10;&lt;li&gt;&#10;&lt;a&gt;asdf&lt;/a&gt;&#10;&lt;/li&gt;&#10;&lt;/ul&gt;&#10;&lt;/nav&gt;&#10;&#10;asdf asdf &#42;asdf&#42; asdf</code></pre></td>
      <td><pre><code>&lt;nav&gt;&#10;&lt;ul&gt;&#10;&lt;li&gt;&#10;&lt;a&gt;asdf&lt;/a&gt;&#10;&lt;/li&gt;&#10;&lt;li&gt;&#10;&lt;a&gt;asdf&lt;/a&gt;&#10;&lt;/li&gt;&#10;&lt;li&gt;&#10;&lt;a&gt;asdf&lt;/a&gt;&#10;&lt;/li&gt;&#10;&lt;/ul&gt;&#10;&lt;/nav&gt;&lt;p&gt;asdf asdf &lt;em&gt;asdf&lt;/em&gt; asdf&lt;/p&gt;</code></pre></td>
    </tr>
    <tr>
      <td><pre><code>&lt;nav&gt;&#10;  &lt;ul&gt;&#10;    &lt;li&gt;&#10;      &lt;a&gt;asdf&lt;/a&gt;&#10;    &lt;/li&gt;&#10;    &lt;li&gt;&#10;      &lt;a&gt;asdf&lt;/a&gt;&#10;    &lt;/li&gt;&#10;    &lt;li&gt;&#10;      &lt;a&gt;asdf&lt;/a&gt;&#10;    &lt;/li&gt;&#10;  &lt;/ul&gt;&#10;&lt;/nav&gt;&#10;&#10;asdf asdf &#42;asdf&#42; asdf</code></pre></td>
      <td><pre><code>&lt;nav&gt;&#10;  &lt;ul&gt;&#10;    &lt;li&gt;&#10;      &lt;a&gt;asdf&lt;/a&gt;&#10;    &lt;/li&gt;&#10;    &lt;li&gt;&#10;      &lt;a&gt;asdf&lt;/a&gt;&#10;    &lt;/li&gt;&#10;    &lt;li&gt;&#10;      &lt;a&gt;asdf&lt;/a&gt;&#10;    &lt;/li&gt;&#10;  &lt;/ul&gt;&#10;&lt;/nav&gt;&lt;p&gt;asdf asdf &lt;em&gt;asdf&lt;/em&gt; asdf&lt;/p&gt;</code></pre></td>
    </tr>
  </tbody>
</table>

You will understand why when you add a number of blank lines at any point in the HTML block:

<table>
  <thead>
    <tr>
      <th>Markdown</th>
      <th>HTML</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td><pre><code>&lt;nav&gt;&#10;&lt;ul&gt;&#10;&lt;li&gt;&#10;&lt;a&gt;&#10;&#10;asdf&lt;/a&gt;&#10;&lt;/li&gt;&#10;&#10;&lt;li&gt;&#10;&lt;a&gt;asdf&lt;/a&gt;&#10;&lt;/li&gt;&#10;&lt;li&gt;&#10;&lt;a&gt;asdf&lt;/a&gt;&#10;&lt;/li&gt;&#10;&lt;/ul&gt;&#10;&lt;/nav&gt;&#10;&#10;asdf asdf &#42;asdf&#42; asdf</code></pre></td>
      <td><pre><code>&lt;nav&gt;&#10;&lt;ul&gt;&#10;&lt;li&gt;&#10;&lt;a&gt;&lt;p&gt;asdf&lt;/a&gt;&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&#10;&lt;a&gt;asdf&lt;/a&gt;&#10;&lt;/li&gt;&#10;&lt;li&gt;&#10;&lt;a&gt;asdf&lt;/a&gt;&#10;&lt;/li&gt;&#10;&lt;/ul&gt;&#10;&lt;/nav&gt;&lt;p&gt;asdf asdf &lt;em&gt;asdf&lt;/em&gt; asdf&lt;/p&gt;</code></pre></td>
    </tr>
    <tr>
      <td><pre><code>&lt;nav&gt;&#10;  &lt;ul&gt;&#10;    &lt;li&gt;&#10;      &lt;a&gt;&#10;&#10;      asdf&lt;/a&gt;&#10;    &lt;/li&gt;&#10;&#10;    &lt;li&gt;&#10;      &lt;a&gt;asdf&lt;/a&gt;&#10;    &lt;/li&gt;&#10;    &lt;li&gt;&#10;      &lt;a&gt;asdf&lt;/a&gt;&#10;    &lt;/li&gt;&#10;  &lt;/ul&gt;&#10;&lt;/nav&gt;&#10;&#10;asdf asdf &#42;asdf&#42; asdf</code></pre></td>
      <td><pre><code>&lt;nav&gt;&#10;  &lt;ul&gt;&#10;    &lt;li&gt;&#10;      &lt;a&gt;&lt;pre&gt;&lt;code&gt;  asdf&amp;lt;/a&amp;gt;&#10;&amp;lt;/li&amp;gt;&#10;&#10;&amp;lt;li&amp;gt;&#10;  &amp;lt;a&amp;gt;asdf&amp;lt;/a&amp;gt;&#10;&amp;lt;/li&amp;gt;&#10;&amp;lt;li&amp;gt;&#10;  &amp;lt;a&amp;gt;asdf&amp;lt;/a&amp;gt;&#10;&amp;lt;/li&amp;gt;&lt;/code&gt;&lt;/pre&gt;&lt;/ul&gt;&#10;&lt;/nav&gt;&lt;p&gt;asdf asdf &lt;em&gt;asdf&lt;/em&gt; asdf&lt;/p&gt;</code></pre></td>
    </tr>
  </tbody>
</table>

Markdown Extra features the `markdown` attribute on HTML to allow you to convert Markdown syntax to HTML in a HTML
block. In this converter, the feature will not work. For now, I have no plans to add such feature to avoid DOM parsing
tasks as much as possible. This also ensured me to avoid on using [PHP `dom`](https://www.php.net/book.dom).

However, if you add a blank line, it’s as if the feature works (although the `markdown` attribute is still there, it
doesn’t affect the HTML when rendered in the browser window). If you’re used to adding a blank line after the opening
HTML block tag and before the closing HTML block tag, you should be okay.

<table>
  <thead>
    <tr>
      <th>Markdown</th>
      <th>HTML</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td><pre><code>&lt;div markdown="1"&gt;&#10;asdf asdf &#42;asdf&#42; asdf&#10;&lt;/div&gt;</code></pre></td>
      <td><pre><code>&lt;div markdown="1"&gt;&#10;asdf asdf &#42;asdf&#42; asdf&#10;&lt;/div&gt;</code></pre></td>
    </tr>
    <tr>
      <td><pre><code>&lt;div markdown="1"&gt;&#10;&#10;asdf asdf &#42;asdf&#42; asdf&#10;&lt;/div&gt;</code></pre></td>
      <td><pre><code>&lt;div markdown="1"&gt;&lt;p&gt;asdf asdf &lt;em&gt;asdf&lt;/em&gt; asdf&lt;/p&gt;&lt;/div&gt;</code></pre></td>
    </tr>
  </tbody>
</table>

Opening an inline HTML element will not trigger the raw HTML block state unless the opening and closing tags stand alone
on a single line. This is explained in [Section 4.6](https://spec.commonmark.org/0.30#html-blocks), type 7:

<table>
  <thead>
    <tr>
      <th>Markdown</th>
      <th>HTML</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td><pre><code>&lt;span&gt;asdf &#42;asdf&#42;&lt;/span&gt; asdf &#42;asdf&#42; asdf</code></pre></td>
      <td><pre><code>&lt;p&gt;&lt;span&gt;asdf &lt;em&gt;asdf&lt;/em&gt;&lt;/span&gt; asdf &lt;em&gt;asdf&lt;/em&gt; asdf&lt;/p&gt;</code></pre></td>
    </tr>
    <tr>
      <td><pre><code>&lt;span&gt;&#10;asdf &#42;asdf&#42;&#10;&lt;/span&gt;&#10;asdf &#42;asdf&#42; asdf</code></pre></td>
      <td><pre><code>&lt;span&gt;&#10;asdf &#42;asdf&#42;&#10;&lt;/span&gt;&#10;asdf &#42;asdf&#42; asdf</code></pre></td>
    </tr>
  </tbody>
</table>

Since CommonMark doesn’t care about HTML structure, the examples below will also conform to the specification, even if
they result in broken HTML. However, these are very rarely intentionally written by hand, so such cases are very
unlikely to occur:

<table>
  <thead>
    <tr>
      <th>Markdown</th>
      <th>HTML</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td><pre><code>&lt;h1&gt;&#10;&#10;asdf asdf &#42;asdf&#42; asdf&#10;&#10;&lt;/h1&gt;</code></pre></td>
      <td><pre><code>&lt;h1&gt;&lt;p&gt;asdf asdf &lt;em&gt;asdf&lt;/em&gt; asdf&lt;/p&gt;&lt;/h1&gt;</code></pre></td>
    </tr>
    <tr>
      <td><pre><code>&lt;p&gt;&#10;&#10;asdf asdf &#42;asdf&#42; asdf&#10;&#10;&lt;/p&gt;</code></pre></td>
      <td><pre><code>&lt;p&gt;&lt;p&gt;asdf asdf &lt;em&gt;asdf&lt;/em&gt; asdf&lt;/p&gt;&lt;/p&gt;</code></pre></td>
    </tr>
  </tbody>
</table>

### Image Block

Markdown was initiated before the HTML5 era. When the `<figure>` element was introduced, people started using it as a
feature to display an image with a caption. Most Markdown converters will convert image syntax that stands alone on a
single line as an image element wrapped in a paragraph element in the output. My converter would instead wrap it in a
figure element. Because for now, it seems like a figure element would be more desirable in this situation.

Paragraphs that appear below it will be taken as the image caption if you prepend a number of spaces less than 4.

<table>
  <thead>
    <tr>
      <th>Markdown</th>
      <th>HTML</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td><pre><code>![asdf](asdf.jpg)</code></pre></td>
      <td><pre><code>&lt;figure&gt;&lt;img alt="asdf" src="asdf.jpg" /&gt;&lt;/figure&gt;</code></pre></td>
    </tr>
    <tr>
      <td><pre><code>![asdf](asdf.jpg)&#10; asdf</code></pre></td>
      <td><pre><code>&lt;figure&gt;&lt;img alt="asdf" src="asdf.jpg" /&gt;&lt;figcaption&gt;asdf&lt;/figcaption&gt;&lt;/figure&gt;</code></pre></td>
    </tr>
    <tr>
      <td><pre><code>![asdf](asdf.jpg)&#10; asdf&#10;&#10; asdf&#10;&#10;asdf</code></pre></td>
      <td><pre><code>&lt;figure&gt;&lt;img alt="asdf" src="asdf.jpg" /&gt;&lt;figcaption&gt;&lt;p&gt;asdf&lt;/p&gt;&lt;p&gt;asdf&lt;/p&gt;&lt;/figcaption&gt;&lt;/figure&gt;&lt;p&gt;asdf&lt;/p&gt;</code></pre></td>
    </tr>
    <tr>
      <td><pre><code>![asdf](asdf.jpg) asdf</code></pre></td>
      <td><pre><code>&lt;p&gt;&lt;img alt="asdf" src="asdf.jpg" /&gt; asdf&lt;/p&gt;</code></pre></td>
    </tr>
  </tbody>
</table>

FYI, this pattern should also be valid for average Markdown files. And so it will be gracefully degraded when parsed by
other Markdown converters.

### List Block

List blocks follow the CommonMark specifications with one exception: if the next ordered list item uses a number that is
less than the number of the previous ordered list item, a new list block will be created. This is different from the
original specification, which does not care about the literal value of the number.

<table>
  <thead>
    <tr>
      <th>Markdown</th>
      <th>HTML</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td><pre><code>1. asdf&#10;2. asdf&#10;3. asdf</code></pre></td>
      <td><pre><code>&lt;ol&gt;&lt;li&gt;asdf&lt;/li&gt;&lt;li&gt;asdf&lt;/li&gt;&lt;li&gt;asdf&lt;/li&gt;&lt;/ol&gt;</code></pre></td>
    </tr>
    <tr>
      <td><pre><code>1. asdf&#10;1. asdf&#10;1. asdf</code></pre></td>
      <td><pre><code>&lt;ol&gt;&lt;li&gt;asdf&lt;/li&gt;&lt;li&gt;asdf&lt;/li&gt;&lt;li&gt;asdf&lt;/li&gt;&lt;/ol&gt;</code></pre></td>
    </tr>
    <tr>
      <td><pre><code>1. asdf&#10;2. asdf&#10;1. asdf</code></pre></td>
      <td><pre><code>&lt;ol&gt;&lt;li&gt;asdf&lt;/li&gt;&lt;li&gt;asdf&lt;/li&gt;&lt;/ol&gt;&lt;ol&gt;&lt;li&gt;asdf&lt;/li&gt;&lt;/ol&gt;</code></pre></td>
    </tr>
  </tbody>
</table>

### Table Block

Table blocks follow the [Markdown Extra’s table block syntax](https://michelf.ca/projects/php-markdown/extra#table).
However, there are a few additional features and rules:

 - The actual number of columns follows the number of columns in the table header separator. If you have columns in
   table header and/or table data with a number that exceeds the actual number of columns, the excess columns will be
   discarded. If you have columns in table header and/or table data with a number that is less than the actual number of
   columns, several empty columns will be added automatically to the right side.
 - Literal pipe characters in table columns must be escaped. Exceptions are those that appear in code span and attribute
   values of raw HTML tags.
 - Header-less table is supported, but may not be compatible with other Markdown converters. Consider using this feature
   as rarely as possible, unless you have no plans to switch to other Markdown converters in the future.
 - Table caption is supported and can be created using the same syntax as the image block’s caption syntax.

<table>
  <thead>
    <tr>
      <th>Markdown</th>
      <th>HTML</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td><pre><code>asdf | asdf&#10;---- | ----&#10;asdf | asdf</code></pre></td>
      <td><pre><code>&lt;table&gt;&lt;thead&gt;&lt;tr&gt;&lt;th&gt;asdf&lt;/th&gt;&lt;th&gt;asdf&lt;/th&gt;&lt;/tr&gt;&lt;/thead&gt;&lt;tbody&gt;&lt;tr&gt;&lt;td&gt;asdf&lt;/td&gt;&lt;td&gt;asdf&lt;/td&gt;&lt;/tr&gt;&lt;/tbody&gt;&lt;/table&gt;</code></pre></td>
    </tr>
    <tr>
      <td><pre><code>asdf | asdf&#10;---- | ----</code></pre></td>
      <td><pre><code>&lt;table&gt;&lt;thead&gt;&lt;tr&gt;&lt;th&gt;asdf&lt;/th&gt;&lt;th&gt;asdf&lt;/th&gt;&lt;/tr&gt;&lt;/thead&gt;&lt;/table&gt;</code></pre></td>
    </tr>
    <tr>
      <td><pre><code>---- | ----&#10;asdf | asdf</code></pre></td>
      <td><pre><code>&lt;table&gt;&lt;tbody&gt;&lt;tr&gt;&lt;td&gt;asdf&lt;/td&gt;&lt;td&gt;asdf&lt;/td&gt;&lt;/tr&gt;&lt;/tbody&gt;&lt;/table&gt;</code></pre></td>
    </tr>
  </tbody>
</table>

XSS
---

This converter is intended only to convert Markdown syntax to HTML based on the
[CommonMark](https://spec.commonmark.org/0.30) specification. It doesn’t care about your user input. I have no intention
of adding any special security features in the future, sorry. The attribute syntax feature may be a security risk for
you if you want to use this converter on your comment entries, for example:

<table>
  <thead>
    <tr>
      <th>Markdown</th>
      <th>HTML</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td><pre><code class="language-markdown">![asdf](asdf.asdf) {onerror="alert('Yo!')"}</code></pre></td>
      <td><pre><code class="language-html">&lt;img alt="asdf" onerror="alert(&amp;apos;Yo!&amp;apos;)" src="asdf.asdf" /&gt;</code></pre></td>
    </tr>
  </tbody>
</table>

There should be many specialized PHP applications already that have specific tasks to deal with XSS, so consider
post-processing the generated HTML markup before putting it out to the web:

 - [ezyang/htmlpurifier](https://github.com/ezyang/htmlpurifier)
 - [voku/anti-xss](https://github.com/voku/anti-xss)

Tests
-----

Clone this repository into the root of your web server that supports PHP and then you can open the `test/from.php` and
`test/to.php` file with your browser to see the result and the performance of this converter in various cases.

Tweaks
------

Not all Markdown dialects are supported for various reasons. Some of the modification methods below can be implemented
to add features that you might find in other Markdown converters.

Your Markdown content is represented as variable `$value`. If you modify the content before the function
`from_markdown()` is called, it means that you modify the Markdown content before it is converted. If you modify the
content after the function `from_markdown()` is called, it means that you modify the results of the Markdown conversion.

### XHTML to HTML5

This converter escapes invalid HTML elements and takes care of HTML special characters that you put in the Markdown
attribute syntax, so it is safe to replace `' />'` with `'>'` directly from the results of the Markdown conversion:

~~~ php
$value = from_markdown($value);

$value = strtr($value, [' />' => '>']);

echo $value;
~~~

### Strike

This method allows you to add strike-through syntax, as you may have already noticed in the
[GFM specification](https://github.github.com/gfm):

~~~ php
$value = from_markdown($value);

$value = preg_replace('/((?<![~])[~]{1,2}(?![~]))([^~]+)\1/', '<del>$2</del>', $value);

echo $value;
~~~

### Task List

I am against the task list feature because it promotes bad practices to abuse the form input element. Although from the
presentation side it displays a check box interface correctly, I still believe that input elements should ideally be
used inside a form element. There are several Unicode symbols that are more suitable and easier to read from the
Markdown source like &#x2610; and &#x2612;, which means that this feature can actually be made using the existing list
feature:

~~~ md
- ☒ asdf
- ☐ asdf
- ☐ asdf
~~~

In case you need it, or don’t want to update your existing task list syntax in your Markdown files, here’s the hack:

~~~ php
$value = from_markdown($value);

$value = strtr($value, [
    '<li><p>[ ] ' => '<li><p>&#x2610; ',
    '<li><p>[x] ' => '<li><p>&#x2612; ',
    '<li>[ ] ' => '<li>&#x2610; ',
    '<li>[x] ' => '<li>&#x2612; '
]);

echo $value;
~~~

### Pre-Defined Abbreviations, Notes, and References

By inserting abbreviations, notes, and references at the end of the Markdown content, it will be as if you had
pre-defined abbreviations, notes, and references feature. This should be placed at the end of the Markdown content,
because according to the [link reference definitions](https://spec.commonmark.org/0.30#example-204) specification, the
first declared reference always takes precedence:

~~~ php
$abbreviations = [
    'CSS' => 'Cascading Style Sheet',
    'HTML' => 'Hyper Text Markup Language',
    'JS' => 'JavaScript'
];

$references = [
    'mecha-cms' => ['https://github.com/mecha-cms', 'Mecha CMS', []],
    'taufik-nurrohman' => ['https://github.com/taufik-nurrohman', 'Taufik Nurrohman', []],
];

$suffix = "";

if (!empty($abbreviations)) {
    foreach ($abbreviations as $k => $v) {
        $k = strtr($k, [
            '[' => '\[',
            ']' => '\]'
        ]);
        $v = trim(preg_replace('/\s+/', ' ', $v));
        $suffix .= "\n*[" . $k . ']: ' . $v;
    }
}

if (!empty($references)) {
    foreach ($references as $k => $v) {
        [$link, $title, $attributes] = $v;
        $k = strtr($k, [
            '[' => '\[',
            ']' => '\]'
        ]);
        if ("" === $link || false !== strpos($link, ' ')) {
            $link = '<' . $link . '>';
        }
        $reference = '[' . $k . ']: ' . $link;
        if (!empty($title)) {
            $reference .= " '" . strtr($title, ["'" => "\\'"]) . "'";
        }
        if (!empty($attributes)) {
            foreach ($attributes as $kk => &$vv) {
                // `{.asdf}`
                if ('class' === $kk) {
                    $vv = '.' . trim(preg_replace('/\s+/', '.', $vv));
                    continue;
                }
                // `{#asdf}`
                if ('id' === $kk) {
                    $vv = '#' . $vv;
                    continue;
                }
                // `{asdf}`
                if (true === $vv) {
                    $vv = $kk;
                    continue;
                }
                // `{asdf=""}`
                if ("" === $vv) {
                    $vv = $kk . '=""';
                    continue;
                }
                // `{asdf='asdf'}`
                $vv = $kk . "='" . strtr($vv, ["'" => "\\'"]) . "'";
            }
            unset($vv);
            sort($attributes);
            $attributes = trim(strtr(implode(' ', $attributes), [
                ' #' => '#',
                ' .' => '.'
            ]));
            $reference .= ' {' . $attributes . '}';
        }
        $suffix .= "\n" . $reference;
    }
}

$value = from_markdown($value . "\n" . $suffix);

echo $value;
~~~

### Pre-Defined Header’s ID

Add an automatic `id` attribute to headers level 2 through 6 if it’s not set, and then prepend an anchor element that
points to it:

~~~ php
$value = from_markdown($value);

if ($value && false !== strpos($value, '</h')) {
    $value = preg_replace_callback('/<(h[2-6])(\s(?>"[^"]*"|\'[^\']*\'|[^>])*)?>([\s\S]+?)<\/\1>/', static function ($m) {
        if (!empty($m[2]) && false !== strpos($m[2], 'id=') && preg_match('/\bid=("[^"]+"|\'[^\']+\'|[^\/>\s]+)/', $m[2], $n)) {
            if ('"' === $n[1][0] && '"' === substr($n[1], -1)) {
                $id = substr($n[1], 1, -1);
            } else if ("'" === $n[1][0] && "'" === substr($n[1], -1)) {
                $id = substr($n[1], 1, -1);
            } else {
                $id = $n[1];
            }
            $m[3] = '<a href="#' . htmlspecialchars($id) . '" style="text-decoration: none;">⚓</a> ' . $m[3];
            return '<' . $m[1] . $m[2] . '>' . $m[3] . '</' . $m[1] . '>';
        }
        $id = trim(preg_replace('/[^a-z\x{4e00}-\x{9fa5}\d]+/u', '-', strtolower($m[3])), '-');
        $m[3] = '<a href="#' . htmlspecialchars($id) . '" style="text-decoration: none;">⚓</a> ' . $m[3];
        return '<' . $m[1] . ($m[2] ?? "") . ' id="' . htmlspecialchars($id) . '">' . $m[3] . '</' . $m[1] . '>';
    }, $value);
}

echo $value;
~~~

### Idea: Embed Syntax

The [CommonMark specification for automatic links](https://spec.commonmark.org/0.30#autolinks) doesn’t limit specific
types of URL protocols. It just specifies the pattern so we can take advantage of the automatic link syntax to render it
as a kind of “embed syntax”, which you can then turn it into a chunk of HTML elements.

I’m sure this idea has never been done before and that’s why I want to be the first to mention it. But I’m not going to
integrate this feature directly into my converter to keep it slim. I just want to give you a couple of ideas:

#### YouTube Video Embed

An embed syntax to display a YouTube video by video ID.

~~~ md
<youtube:dQw4w9WgXcQ>
~~~

~~~ php
$value = preg_replace('/^[ ]{0,3}<youtube:([^>]+)>\s*$/m', '<iframe src="https://www.youtube.com/embed/$1"></iframe>', $value);

$value = from_markdown($value);

echo $value;
~~~

#### GitHub Gist Embed

An embed syntax to display a GitHub gist by gist ID.

~~~ md
<gist:9c96049ca6c66e30e50793f5aef4818b>
~~~

~~~ php
$value = preg_replace('/^[ ]{0,3}<gist:([^>]+)>\s*$/m', '<script src="https://gist.github.com/taufik-nurrohman/$1.js"></script>', $value);

$value = from_markdown($value);

echo $value;
~~~

#### Form Embed

An embed syntax to display a HTML form that was generated from the server side with a reference ID of `18a4596d42c` and
a `title` parameter to customize the HTML form title.

~~~ md
<form:18a4596d42c?title=Form+Title>
~~~

~~~ php
$value = preg_replace_callback('/^[ ]{0,3}<form:([^#>?]+)([?][^#>]*)?([#][^>]*)?>\s*$/m', static function ($m) {
    $path = $m[1];
    $value = "";
    parse_str(substr($m[2] ?? "", 1), $state);
    $value .= '<form action="/form/' . $path . '" method="post">';
    if (!empty($state['title'])) {
        $value .= '<h1>' . $state['title'] . '</h1>';
    }
    // … etc.
    // Be careful not to include blank line(s), or the raw HTML block state will end before the HTML form is complete!
    $value .= '</form>';
    return $value;
}, $value);

$value = from_markdown($value);

echo $value;
~~~

### Idea: Note Block

Several people have discussed this feature, and I think I like
[this answer](https://stackoverflow.com/a/41449789/1163000) the most. The syntax is compatible with native Markdown
syntax, which is nice to look at directly through the Markdown source, even when it gets rendered to HTML:

~~~ md
------------------------------

  **NOTE:** asdf asdf asdf

------------------------------
~~~

~~~ md
------------------------------

  **NOTE:**

  asdf asdf asdf asdf
  asdf asdf asdf asdf

  asdf asdf asdf asdf

------------------------------
~~~

Most Markdown converters will render the syntax above to this HTML, which is still acceptable to be treated as a note
block from its presentation, despite its broken semantic:

~~~ html
<hr /><p><strong>NOTE:</strong> asdf asdf asdf</p><hr />
~~~

~~~ html
<hr /><p><strong>NOTE:</strong></p><p>asdf asdf asdf asdf asdf asdf asdf asdf</p><p>asdf asdf asdf asdf</p><hr />
~~~

With regular expressions, you can improve its [semantic](https://w3c.github.io/aria#note):

~~~ php
$value = from_markdown($value);

$value = preg_replace_callback('/<hr\s*\/?>(<p><strong>NOTE:<\/strong>[\s\S]*?<\/p>)<hr\s*\/?>/', static function ($m) {
    return '<div role="note">' . $m[1] . '</div>';
}, $value);

echo $value;
~~~

License
-------

This library is licensed under the [MIT License](LICENSE). Please consider
[donating 💰](https://github.com/sponsors/taufik-nurrohman) if you benefit financially from this library.

Links
-----

 - Autumn image sample by [@blmiers2](https://www.flickr.com/photos/41304517@N00/6250498399)
 - Emoticon image sample by [@emoticons4u](https://web.archive.org/web/20090117060451/http://emoticons4u.com) (web archive)