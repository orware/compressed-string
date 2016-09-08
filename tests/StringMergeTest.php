<?php
use Orware\Compressed\String;
use Orware\Compressed\StringList;
use Orware\Compressed\StringMerge;

class StringMergeTest extends \PHPUnit_Framework_TestCase
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

    public function testMerge()
    {
        $this->memoryUsage(__METHOD__, '');

        $compressedString1 = new String();
        $content = 'My first string';
        $compressedString1->write($content);

        $compressedString2 = new String();
        $content = 'My second string';
        $compressedString2->write($content);

        $compressedString3 = new String();
        $content = 'My third string';
        $compressedString3->write($content);

        $list = new StringList();

        $list->enqueue($compressedString1);
        $list->enqueue($compressedString2);
        $list->enqueue($compressedString3);

        $subject = '{"string1":"#|_|#","string2":"#|_|#","string3":"#|_|#"}';

        $mergedString = StringMerge::merge($subject, '#|_|#', $list);

        $expected = '{"string1":"My first string","string2":"My second string","string3":"My third string"}';
        $this->assertEquals($expected, $mergedString->getDecompressedContents());
    }

    public function testLargeMergeIntoString()
    {
        $this->memoryUsage(__METHOD__, 'Start');

        $compressedString1 = new String();
        $handle = fopen('tests/files/companies_first_10.json', "r");
        if ($handle) {
            while (($buffer = fgets($handle, 4096)) !== false) {
                $compressedString1->write($buffer);
            }

            fclose($handle);
        }

        $compressedString2 = new String();
        $handle = fopen('tests/files/companies_first_10.json', "r");
        if ($handle) {
            while (($buffer = fgets($handle, 4096)) !== false) {
                $compressedString2->write($buffer);
            }

            fclose($handle);
        }

        $subject = '[{"queryType":"SELECT","rowCount":4178,"executionTimeMilliseconds":4764.52,"executionTimeSeconds":4.76,"memoryUsageBytes":323344,"memoryUsageMegabytes":0.31,"cachedResponse":false,"data":#|_|#,"error":false},{"queryType":"SELECT","rowCount":4178,"executionTimeMilliseconds":4764.52,"executionTimeSeconds":4.76,"memoryUsageBytes":323344,"memoryUsageMegabytes":0.31,"cachedResponse":false,"data":#|_|#,"error":false}]';

        $list = new StringList();

        $list->enqueue($compressedString1);
        $list->enqueue($compressedString2);

        $mergedString = StringMerge::merge($subject, '#|_|#', $list);

        $this->log("Merged String Size is: " . $mergedString->getCompressedSize());

        $mergedString->writeCompressedContents('tests/files/tmp/merged_compressed.gz');

        // Actual size should be less than 80,000 bytes:
        $this->assertLessThanOrEqual(80000, $mergedString->getCompressedSize());

        $this->memoryUsage(__METHOD__, 'Finish');
    }

    public function testLargeMergeIntoObject()
    {
        $this->memoryUsage(__METHOD__, 'Start');

        $compressedString1 = new String();
        $handle = fopen('tests/files/companies_first_10.json', "r");
        if ($handle) {
            while (($buffer = fgets($handle, 4096)) !== false) {
                $compressedString1->write($buffer);
            }

            fclose($handle);
        }

        $compressedString2 = new String();
        $handle = fopen('tests/files/companies_first_10.json', "r");
        if ($handle) {
            while (($buffer = fgets($handle, 4096)) !== false) {
                $compressedString2->write($buffer);
            }

            fclose($handle);
        }

        $subject = array();
        $queryObject1 = new \stdClass();
        $queryObject1->queryType = 'SELECT';
        $queryObject1->rowCount = 4178;
        $queryObject1->executionTimeMilliseconds = 4178;
        $queryObject1->executionTimeSeconds = 4178;
        $queryObject1->memoryUsageBytes = 4178;
        $queryObject1->memoryUsageMegabytes = 4178;
        $queryObject1->cachedResponse = 4178;
        $queryObject1->data = '#|_|#';
        $queryObject1->error = false;

        $subject[] = $queryObject1;

        $queryObject2 = new \stdClass();
        $queryObject2->queryType = 'SELECT';
        $queryObject2->rowCount = 4178;
        $queryObject2->executionTimeMilliseconds = 4178;
        $queryObject2->executionTimeSeconds = 4178;
        $queryObject2->memoryUsageBytes = 4178;
        $queryObject2->memoryUsageMegabytes = 4178;
        $queryObject2->cachedResponse = 4178;
        $queryObject2->data = '#|_|#';
        $queryObject2->error = false;

        $subject[] = $queryObject2;

        $list = new StringList();

        $list->enqueue($compressedString1);
        $list->enqueue($compressedString2);

        $mergedString = StringMerge::merge($subject, '#|_|#', $list);

        // Actual size should be less than 80,000 bytes:
        $this->assertLessThanOrEqual(80000, $mergedString->getCompressedSize());

        //file_put_contents('test_large_merge.json', $actualString);
        $this->memoryUsage(__METHOD__, 'Finish');
    }
}
