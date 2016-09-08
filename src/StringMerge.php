<?php
namespace Orware\Compressed;

class StringMerge
{
	public static function merge($subject, $delimiter, StringList $gzippedStrings)
	{
		if (!is_string($subject))
		{
			$subject = json_encode($subject);
		}

		$subjectParts = explode($delimiter, $subject);

		//if ((count($subjectParts) - 1) === $gzippedStrings->count())
		//{
			// We have the correct number of Gzipped Strings provided:
			$merged = new String();

			foreach($subjectParts as $part)
			{
				$merged->write($part);
				if (!$gzippedStrings->isEmpty())
				{
					$string = $gzippedStrings->dequeue();

					$readStream = $string->getReadOnlyStream();
					while($buffer = $readStream->read(4096)) {
						$merged->write($buffer);
					}
					//$merged->write($string->getDecompressedContents());
				}
			}
		//}

		return $merged;
	}
}