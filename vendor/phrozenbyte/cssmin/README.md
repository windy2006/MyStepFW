CssMin
======

> A (simple) css minifier with benefits

This repository is a copy of CssMin by Joe Scylla, previously hosted on [Google Code](https://code.google.com/p/cssmin/), to support [Composer](https://getcomposer.org/). Please refer to the original homepage of CssMin on Google Code for more info: http://code.google.com/p/cssmin/

This [GitHub fork](https://github.com/PhrozenByte/cssmin) aims to be the go-to address for Joe Scylla's CssMin. It was converted into a Composer library utilizing Composer's autoloader with the original non-concatenated PHP sources and aims to also include fixes from other CssMin forks. Even though this project isn't actively developed, we accept pull requests fixing existing problems, like compatibility issues with later PHP versions. Visit us on GitHub for more info: https://github.com/PhrozenByte/cssmin

What is CssMin?
---------------

CssMin is a CSS parser and minfier. It minifies CSS by removing unneeded whitespace characters, comments, empty blocks and empty declarations. In addition declaration values can get rewritten to shorter notations if available. The minification is configurable.

CssMin has some benefits. It supports the rewrite of CSS Level 3 properties to their browser specific counterparts and is able to simulate CSS variables.

Install
-------

CssMin is available on [Packagist.org](https://packagist.org/packages/phrozenbyte/cssmin) and may be included in other projects via `composer require phrozenbyte/cssmin`. CssMin requires PHP 5.0+

Syntax
------

```
string CssMin::minify(string $source [, array $filters = array()][, array $plugins = array()]);
```

* `string $source` - The source css as string.
* `array $filters` - The filter configuration as array (optional).
* `array $plugins` - The plugin configuration as array (optional).

Example
-------

```php
// Simple minification WITHOUT filter or plugin configuration
$result = CssMin::minify(file_get_contents("path/to/source.css"));

// Minification WITH filter or plugin configuration
$filters = array(/* ... */);
$plugins = array(/* ... */);

// Minify via CssMin adapter function
$result = CssMin::minify(file_get_contents("path/to/source.css"), $filters, $plugins);

// Minify via CssMinifier class
$minifier = new CssMinifier(file_get_contents("path/to/source.css"), $filters, $plugins);
$result = $minifier->getMinified();
```
