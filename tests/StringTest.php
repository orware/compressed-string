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
	public function memoryUsage($method, $stage = '')
	{
		fwrite(STDOUT, "\n");
    	fwrite(STDOUT, $method . ' ' . $stage . ': ' . memory_get_peak_usage(true) ." (peak)\n");
    	fwrite(STDOUT, $method . ' ' . $stage . ': ' . memory_get_usage(true) ." (current)\n");
	}

	public function log($string, $newline = true)
	{
		fwrite(STDOUT, "\n");
		fwrite(STDOUT, $string);

		if ($newline)
		{
			fwrite(STDOUT, "\n");
		}
	}

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
		$this->memoryUsage(__METHOD__, 'Start');

		$compressedString = new String();

        $content = 'The quick brown fox jumps over the lazy dog';
        $compressedString->write($content);

        $textToPrepend = 'PREPENDED';
        $compressedString->prepend($textToPrepend);

        $this->assertEquals($textToPrepend.$content, $compressedString->getDecompressedContents());

		$this->memoryUsage(__METHOD__, 'Finish');

        $compressedString->writeDecompressedContents('tests/files/tmp/prepended_test_decompressed.txt');
        $compressedString->writeCompressedContents('tests/files/tmp/prepended_test_compressed.gz');
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

        $compressedString->writeDecompressedContents('tests/files/tmp/appended_test_decompressed.txt');
        $compressedString->writeCompressedContents('tests/files/tmp/appended_test_compressed.gz');
    }

    public function testDifferentCompressionModes()
    {
        $content = file_get_contents('README.md');

		$compressedString1 = new String(false, 1);
        $compressedString1->write($content);

    	$compressedString2 = new String(false, 6);
        $compressedString2->write($content);

        $size1 = $compressedString1->getCompressedSize();
        $size2 = $compressedString2->getCompressedSize();

        $this->log(__METHOD__, false);
		$this->log('Original Size: ' . strlen($content), false);
		$this->log('GZIP Mode 1 Size: ' . $size1, false);
		$this->log('GZIP Mode 6 Size: ' . $size2);

    	$this->assertGreaterThan($size2, $size1, 'Gzip Mode 1 should result in the larger file');
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
