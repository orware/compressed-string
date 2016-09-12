<?php
use Orware\Compressed\CompressedString;

class CompressedStringTest extends \PHPUnit_Framework_TestCase
{
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

        if ($newline) {
            fwrite(STDOUT, "\n");
        }
    }

    public function testWriteStream()
    {
        $compressedString = new CompressedString();

        $content = 'The quick brown fox jumps over the lazy dog';
        $compressedString->write($content);

        $this->assertEquals($content, $compressedString->getDecompressedContents());
    }

    public function testMixedWriteStream()
    {
        $compressedString = new CompressedString();

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

        $compressedString = new CompressedString();

        $content = 'The quick brown fox jumps over the lazy dog';
        $compressedString->write($content);

        $textToPrepend = 'PREPENDED';
        $compressedString->prepend($textToPrepend);

        $this->assertEquals($textToPrepend.$content, $compressedString->getDecompressedContents());

        $this->memoryUsage(__METHOD__, 'Finish');

        $compressedString->writeDecompressedContents(__DIR__.'/files/tmp/prepended_test_decompressed.txt');
        $compressedString->writeCompressedContents(__DIR__.'/files/tmp/prepended_test_compressed.gz');
    }

    public function testPrependAndWriteStream()
    {
        $compressedString = new CompressedString();

        $content = 'The quick brown fox jumps over the lazy dog';
        $compressedString->write($content);

        $textToPrepend = 'PREPENDED';
        $textToAppend = 'APPENDED';
        $compressedString->prepend($textToPrepend);
        $compressedString->write($textToAppend);

        $this->assertEquals($textToPrepend.$content.$textToAppend, $compressedString->getDecompressedContents());

        $compressedString->writeDecompressedContents(__DIR__.'/files/tmp/appended_test_decompressed.txt');
        $compressedString->writeCompressedContents(__DIR__.'/files/tmp/appended_test_compressed.gz');
    }

    public function testDifferentCompressionModes()
    {
        $content = file_get_contents('README.md');

        $compressedString1 = new CompressedString(false, 1);
        $compressedString1->write($content);

        $compressedString2 = new CompressedString(false, 6);
        $compressedString2->write($content);

        $size1 = $compressedString1->getCompressedSize();
        $size2 = $compressedString2->getCompressedSize();

        $this->log(__METHOD__, false);
        $this->log('Original Size: ' . strlen($content), false);
        $this->log('GZIP Mode 1 Size: ' . $size1, false);
        $this->log('GZIP Mode 6 Size: ' . $size2);

        $this->assertGreaterThan($size2, $size1, 'Gzip Mode 1 should result in the larger file');
    }

    public function testReadGzipFile()
    {
        $this->memoryUsage(__METHOD__, 'Start');
        $compressedStringFile = new CompressedString(true, 6, __DIR__.'/files/companies_first_10.gz');

        $readOnlyStream = $compressedStringFile->getDecompressedReadOnlyStream();
        $i = 0;
        $firstLineOutput = '';
        while ($buffer = $readOnlyStream->read()) {
            if ($i < 1) {
                $firstLineOutput = $buffer;
            }

            $i++;
        }

        $this->assertContains('_id', $firstLineOutput);

        $this->log(__METHOD__, false);
        $this->memoryUsage(__METHOD__, 'Finish');
    }

    public function testCompressedReadOfReadOnlyGzipFile()
    {
        $this->memoryUsage(__METHOD__, 'Start');
        $compressedStringFile = new CompressedString(true, 6, __DIR__.'/files/companies_first_10.gz');

        $compressedReadOnlyStream = $compressedStringFile->getCompressedReadOnlyStream();

        $compressedString = '';
        while ($buffer = $compressedReadOnlyStream->read()) {
            $compressedString .= $buffer;
        }

        //$this->log(substr($compressedString, 0, 100));
        $uncompressedString = gzdecode($compressedString);
        //$this->log(substr($uncompressedString, 0, 100));

        $this->assertContains('_id', $uncompressedString);

        $this->log(__METHOD__, false);
        $this->memoryUsage(__METHOD__, 'Finish');
    }

    public function testCompressedReadOfWritableGzipFile()
    {
        $this->memoryUsage(__METHOD__, 'Start');
        $compressedStringFile = new CompressedString(false, 6, __DIR__.'/files/companies_first_10.gz');

        $compressedReadOnlyStream = $compressedStringFile->getCompressedReadOnlyStream();

        $compressedString = '';
        while ($buffer = $compressedReadOnlyStream->read()) {
            $compressedString .= $buffer;
        }

        //$this->log(substr($compressedString, 0, 100));
        $uncompressedString = gzdecode($compressedString);
        //$this->log(substr($uncompressedString, 0, 100));

        $this->assertContains('_id', $uncompressedString);

        $this->log(__METHOD__, false);
        $this->memoryUsage(__METHOD__, 'Finish');
    }

    public function testDecompressedReadOfReadOnlyGzipFile()
    {
        $this->memoryUsage(__METHOD__, 'Start');
        $compressedStringFile = new CompressedString(true, 6, __DIR__.'/files/companies_first_10.gz');

        $uncompressedReadOnlyStream = $compressedStringFile->getDecompressedReadOnlyStream();

        $uncompressedString = '';
        while ($buffer = $uncompressedReadOnlyStream->read()) {
            $uncompressedString .= $buffer;
        }

        $this->assertContains('_id', $uncompressedString);

        $this->log(__METHOD__, false);
        $this->memoryUsage(__METHOD__, 'Finish');
    }

    public function testDecompressedReadOfWritableGzipFile()
    {
        $this->memoryUsage(__METHOD__, 'Start');
        $compressedStringFile = new CompressedString(false, 6, __DIR__.'/files/companies_first_10.gz');

        $uncompressedReadOnlyStream = $compressedStringFile->getDecompressedReadOnlyStream();

        $uncompressedString = '';
        while ($buffer = $uncompressedReadOnlyStream->read()) {
            $uncompressedString .= $buffer;
        }

        $this->assertContains('_id', $uncompressedString);

        $this->log(__METHOD__, false);
        $this->memoryUsage(__METHOD__, 'Finish');
    }

    public function testCompressedReadOfInMemoryCompressedString()
    {
        $this->memoryUsage(__METHOD__, 'Start');
        $compressedInMemoryString = new CompressedString();

        $compressedInMemoryString->write(str_repeat('_id', 100));

        $compressedReadOnlyStream = $compressedInMemoryString->getCompressedReadOnlyStream();

        $compressedString = '';
        while ($buffer = $compressedReadOnlyStream->read()) {
            $compressedString .= $buffer;
        }

        //$this->log(substr($compressedString, 0, 100));
        $uncompressedString = gzdecode($compressedString);
        //$this->log(substr($uncompressedString, 0, 100));

        $this->assertContains('_id', $uncompressedString);

        $this->log(__METHOD__, false);
        $this->memoryUsage(__METHOD__, 'Finish');
    }

    public function testDecompressedReadOfInMemoryCompressedString()
    {
        $this->memoryUsage(__METHOD__, 'Start');
        $compressedInMemoryString = new CompressedString();

        $compressedInMemoryString->write(str_repeat('_id', 100));

        $uncompressedReadOnlyStream = $compressedInMemoryString->getDecompressedReadOnlyStream();

        $uncompressedString = '';
        while ($buffer = $uncompressedReadOnlyStream->read()) {
            $uncompressedString .= $buffer;
        }

        $this->assertContains('_id', $uncompressedString);

        $this->log(__METHOD__, false);
        $this->memoryUsage(__METHOD__, 'Finish');
    }

    public function testWriteFileAndReadStream()
    {
        $this->memoryUsage(__METHOD__, 'Start');

        $compressedString = new CompressedString();

        $content = file_get_contents(__DIR__.'/files/companies_first_10.json');
        $compressedString->write($content);

        $compressedString->writeCompressedContents(__DIR__.'/files/tmp/write_test.gz');

        $readOnlyStream = $compressedString->getDecompressedReadOnlyStream();
        $i = 0;
        $firstLineOutput = '';
        while ($buffer = $readOnlyStream->read()) {
            if ($i < 1) {
                $firstLineOutput = $buffer;
            }

            $i++;
        }

        $this->assertContains('_id', $firstLineOutput);

        $this->log(__METHOD__, false);
        $this->memoryUsage(__METHOD__, 'Finish');
    }
}
