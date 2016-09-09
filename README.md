Compressed String Class for PHP
=================

Based on the wonderful work by Tom Westcott (https://packagist.org/packages/cyberdummy/gzstream) which provided most of the functionality I required. Allows for gzip compressed string streams to be used for holding data. This project was created because I wanted a way to store large database result sets more easily in memory (especially ones that were just going to be output as JSON in an API response), since using a regular PHP array resulted in large memory usage.

Installation with Composer
--------------------------

```shell
curl -s http://getcomposer.org/installer | php
php composer.phar require orware/compressed-string
```

OR

```shell
composer require orware/compressed-string
```

Usage
-----

It's primarily intended to be used in a write forward way (primarily because going back to the beginning of a gzip string requires it to be decoded, so prepending should be discouraged from excessive use), but you do have the option to prepend text when needed:
```php
use Orware\Compressed\CompressedString;

$compressedString = new CompressedString();

// You may write multiple times:
$content = 'The quick brown fox jumps over the lazy dog';
$compressedString->write($content);

$moreContent = 'The quick brown fox jumps over the lazy dog';
$compressedString->write($moreContent);

// You can prepend text as well
// (currently this involves creating a new stream, adding the prepended text, then copying the existing stream into the new stream):
$textToPrepend = 'PREPENDED';
$compressedString->prepend($textToPrepend);

// You can write more text after prepending text:
$evenMoreContent = 'The quick brown fox jumps over the lazy dog';
$compressedString->write($evenMoreContent);

// You can pass in a PHP array or object and it will get automatically JSON encoded:
$list = [1, 2, 3, 4, 5, 6];
$compressedString->write($list);

// Gets the Decompressed String:
$decompressedString = $compressedString->getDecompressedContents();

// Writes the Decompressed String Directly to a file:
$compressedString->writeDecompressedContents('tests/files/appended_test_decompressed.txt');

// Writes the Compressed String Directly to a file:
$compressedString->writeCompressedContents('tests/files/appended_test_compressed.gz');

```

There's also the ability to merge in one or more compressed strings into a "wrapper" string. In my case my wrapper contained some metadata about the JSON results.
```php
use Orware\Compressed\CompressedString;
use Orware\Compressed\CompressedStringList;

$compressedString1 = new CompressedString();
$content = 'My first string';
$compressedString1->write($content);

$compressedString2 = new CompressedString();
$content = 'My second string';
$compressedString2->write($content);

$compressedString3 = new CompressedString();
$content = 'My third string';
$compressedString3->write($content);

// You must use this StringList class (it's what the merge call below expects):
$list = new CompressedStringList();

$list->enqueue($compressedString1);
$list->enqueue($compressedString2);
$list->enqueue($compressedString3);

// The default placeholder is #|_|#
// Each instance of that placeholder below will get replaced:
$subject = '{"string1":"#|_|#","string2":"#|_|#","string3":"#|_|#"}';

// The end result is a new compressed string.
// Depending on the size of your compressed strings execution
// time may go up during a merge since each has to be decoded
// and compressed again when merged into the new string.
$mergedString = CompressedStringList::merge($subject, '#|_|#', $list);

```

