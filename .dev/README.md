PHP Markdown Parser
===================

![from.php] ![to.php]

[from.php]: https://img.shields.io/github/size/taufik-nurrohman/markdown/from.php?branch=main&color=%234f5d95&label=from.php&labelColor=%231f2328&style=flat-square
[to.php]: https://img.shields.io/github/size/taufik-nurrohman/markdown/to.php?branch=main&color=%234f5d95&label=to.php&labelColor=%231f2328&style=flat-square

With 99% compliance to [CommonMark 0.31.2](https://spec.commonmark.org/0.31.2) specifications.

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
if I want to upgrade, it might be more optimal to just switch to CommonMark.

I’m not into things like that. As someone who needs a function to convert Markdown syntax to HTML, that kind of
flexibility is completely unnecessary to me. I just want to convert Markdown syntax to HTML for once and then move on.
It was fulfilled by [Parsedown version 1.8](https://github.com/erusev/parsedown/tree/1.8.x-beta), but it seems that it
is no longer being actively maintained.

The goal of this project is to use it in my [Markdown extension for Mecha](https://github.com/mecha-cms/x.markdown) in
the future. Previously, I wanted to develop this converter directly into the extension, but my friend advised me to
create this project separately as it might have potential to be used by other developers beyond the
[Mecha CMS](https://github.com/mecha-cms) developers.

### Tabs

Unlike CommonMark, this converter does not preserve tabs. It is possible initially, but turn out this only works for
top-level blocks. Once I enter the container blocks to parse their contents, I then lose track of the column position
because the container block marker has been removed.

The recommended CommonMark parsing strategy is to parse the inner blocks of the current container block immediately,
producing nested blocks instantly.

Given this input:

~~~ md
asdf asdf asdf asdf

> asdf asdf asdf asdf
>
> 1. asdf asdf asdf asdf
>
>    asdf asdf asdf asdf
~~~

CommonMark would parse the input as follows:

 1. At line 1, got a paragraph block.
 2. At line 2, got a blank line which marks the end of the paragraph.
 3. At line 3, got a quote block.
    1. At line 3, got a paragraph block.
    2. At line 4, got a blank line which marks the end of the paragraph block.
       1. At line 5, got a list block.
          1. At line 5, got a paragraph block.
          2. At line 6, got a blank line which marks the end of the paragraph block.
          3. At line 7, got a paragraph block.
          4. At the end of the line, all open blocks will be closed.

Mine does not work that way. Instead, it extracts the inner blocks as plain Markdown text without the container block
marker. Then, it parses the inner blocks after all top-level blocks have been taken apart.

It is hard to keep track of the current column position due to the way I parse, though it can be done with more effort.
But that would make the parser more complex. The easiest way to correctly store white-space column positions is to
replace all tab sequences with spaces, so removing container block markers will correctly shift the white-space column
positions of the child blocks.

My method complies with the CommonMark column rules, except that it will not preserve tab characters.