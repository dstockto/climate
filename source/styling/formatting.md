---
layout: layout
title: Formatting
---

Formatting
==============

You have several formatting options:

+ Bold
+ Dim
+ Underline
+ Blink
+ Invert
+ Hidden

To apply a format:

~~~.language-php
$climate->bold('Bold and beautiful.');
$climate->underline('I have a line beneath me.');

$climate->bold()->out('Bold and beautiful.');
$climate->underline()->out('I have a line beneath me.');
~~~

You can apply multiple formats by chaining them:


~~~.language-php
$climate->bold()->underline()->out('Bold (and underlined) and beautiful.');
$climate->blink()->dim('Dim. But noticeable.');
~~~