<?php
use GuzzleHttp\Psr7;
use Orware\Compressed\String;
use Orware\Compressed\StringList;
use Orware\Compressed\StringMerge;

class StringMergeTest extends \PHPUnit_Framework_TestCase
{
    public function testMerge()
    {
    	echo "\n";
    	echo 'testMerge: ' . memory_get_peak_usage(true) ." (peak)\n";
		echo 'testMerge: ' . memory_get_usage() ."  (current)\n";

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
    	echo "\n";
    	echo 'testLargeMergeIntoString Start: ' . memory_get_peak_usage(true) ." (peak)\n";
		echo 'testLargeMergeIntoString Start: ' . memory_get_usage() ."  (current)\n";

    	$compressedString1 = new String();
    	$handle = fopen('tests/files/test_data.json', "r");
		if ($handle)
		{
		    while (($buffer = fgets($handle, 4096)) !== false)
		    {
		        $compressedString1->write($buffer);
		    }

		    fclose($handle);
		}

		$compressedString2 = new String();
    	$handle = fopen('tests/files/test_data.json', "r");
		if ($handle)
		{
		    while (($buffer = fgets($handle, 4096)) !== false)
		    {
		        $compressedString2->write($buffer);
		    }

		    fclose($handle);
		}

		$subject = '[{"queryType":"SELECT","rowCount":4178,"executionTimeMilliseconds":4764.52,"executionTimeSeconds":4.76,"memoryUsageBytes":323344,"memoryUsageMegabytes":0.31,"cachedResponse":false,"data":#|_|#,"error":false},{"queryType":"SELECT","rowCount":4178,"executionTimeMilliseconds":4764.52,"executionTimeSeconds":4.76,"memoryUsageBytes":323344,"memoryUsageMegabytes":0.31,"cachedResponse":false,"data":#|_|#,"error":false}]';

		$list = new StringList();

		$list->enqueue($compressedString1);
		$list->enqueue($compressedString2);

		$mergedString = StringMerge::merge($subject, '#|_|#', $list);

		// This method was working fine but used quite a bit of memory due to the full
		// decompression of the string:

		//$actualString = $mergedString->getDecompressedContents();
		//$expectedSHA256 = '65c29e2df667ebd3db57c9c99e30d56810ecadb15f7b6379097abf98ceece1d6';
		//$actualSHA256 = hash('sha256', $actualString);
		//$this->assertEquals($expectedSHA256, $actualSHA256);

		// Actual size should be less than 300,000 bytes:
		$this->assertLessThanOrEqual(300000, $mergedString->getGzStream()->getSize());

		//file_put_contents('test_large_merge.json', $actualString);

		echo "\n";
    	echo 'testLargeMergeIntoString Finish: ' . memory_get_peak_usage(true) ." (peak)\n";
		echo 'testLargeMergeIntoString Finish: ' . memory_get_usage() ."  (current)\n";
    }

	public function testLargeMergeIntoObject()
    {
    	echo "\n";
    	echo 'testLargeMergeIntoObject Start: ' . memory_get_peak_usage(true) ." (peak)\n";
		echo 'testLargeMergeIntoObject Start: ' . memory_get_usage() ."  (current)\n";

    	$compressedString1 = new String();
    	$handle = fopen('tests/files/test_data.json', "r");
		if ($handle)
		{
		    while (($buffer = fgets($handle, 4096)) !== false)
		    {
		        $compressedString1->write($buffer);
		    }

		    fclose($handle);
		}

		$compressedString2 = new String();
    	$handle = fopen('tests/files/test_data.json', "r");
		if ($handle)
		{
		    while (($buffer = fgets($handle, 4096)) !== false)
		    {
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

		// Actual size should be less than 300,000 bytes:
		$this->assertLessThanOrEqual(300000, $mergedString->getGzStream()->getSize());

		//file_put_contents('test_large_merge.json', $actualString);
		echo "\n";
    	echo 'testLargeMergeIntoObject Finish: ' . memory_get_peak_usage(true) ." (peak)\n";
		echo 'testLargeMergeIntoObject Finish: ' . memory_get_usage() ."  (current)\n";
    }
}
