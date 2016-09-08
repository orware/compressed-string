<?php
use GuzzleHttp\Psr7;
use Orware\Compressed\String;

class StringTest extends \PHPUnit_Framework_TestCase
{
    /*public function testReadStream()
    {
        $content = gzencode('test');
        $a = Psr7\stream_for($content);
        $b = new GzStreamGuzzle($a);
        $this->assertEquals('test', (string) $b);
    }
*/
    public function testWriteStream()
    {
        $compressedString = new String();

        $content = 'The quick brown fox jumps over the lazy dog';
        $compressedString->write($content);

        $this->assertEquals($content, $compressedString->getDecompressedContents());
    }

    public function testMixedWriteStream()
    {
        $compressedString = new String();

        $content = 'The quick brown fox jumps over the lazy dog';
        $compressedString->write($content);

        $list = [1, 2, 3, 4, 5, 6];
        $compressedString->write($list);

        $object = new stdClass();
        $object->test = true;
        $object->awesome = true;
        $compressedString->write($object);

        $this->assertEquals($content.json_encode($list).json_encode($object), $compressedString->getDecompressedContents());
    }

    public function testPrependStream()
    {
    	echo "\n";
    	echo 'testPrependStream Start: ' . memory_get_peak_usage(true) ." (peak)\n";
		echo 'testPrependStream Start: ' . memory_get_usage() ."  (current)\n";

		$compressedString = new String();

        $content = 'The quick brown fox jumps over the lazy dog';
        $compressedString->write($content);

        $textToPrepend = 'PREPENDED';
        $compressedString->prepend($textToPrepend);

        $this->assertEquals($textToPrepend.$content, $compressedString->getDecompressedContents());

		echo "\n";
    	echo 'testPrependStream Finish: ' . memory_get_peak_usage(true) ." (peak)\n";
		echo 'testPrependStream Finish: ' . memory_get_usage() ."  (current)\n";

        //$compressedString->writeDecompressedContents('prepended_test_decompressed.txt');
        //$compressedString->writeCompressedContents('prepended_test_compressed.gz');
    }

    public function testPrependAndWriteStream()
    {
		$compressedString = new String();

        $content = 'The quick brown fox jumps over the lazy dog';
        $compressedString->write($content);

        $textToPrepend = 'PREPENDED';
        $textToAppend = 'APPENDED';
        $compressedString->prepend($textToPrepend);
        $compressedString->write($textToAppend);

        $this->assertEquals($textToPrepend.$content.$textToAppend, $compressedString->getDecompressedContents());

        $compressedString->writeDecompressedContents('tests/files/appended_test_decompressed.txt');
        $compressedString->writeCompressedContents('tests/files/appended_test_compressed.gz');
    }

    /*public function testWriteAfterReadOnlyStream()
    {
		$compressedString = new String();

        $content = 'The quick brown fox jumps over the lazy dog';
        $compressedString->write($content);
		//$compressedString->writeCompressedContents('write_after_readonly_1.gz');

        $compressedString->getGzStream()->seek(0);
        $compressedString->getGzStream()->undoWriteFooter();

        $textToAppend = 'APPENDED';
        $compressedString->write($textToAppend);

		$compressedString->writeCompressedContents('write_after_readonly_2.gz');
        $this->assertEquals($content.$textToAppend, $compressedString->getDecompressedContents());

        //$compressedString->writeDecompressedContents('write_after_readonly.txt');
    }*/

}
