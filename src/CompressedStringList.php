<?php
namespace Orware\Compressed;

class CompressedStringList
{
    protected $queue = null;

    public function __construct()
    {
        $this->queue = new \SplQueue();
        $this->queue->setIteratorMode(\SplDoublyLinkedList::IT_MODE_FIFO | \SplDoublyLinkedList::IT_MODE_DELETE);
    }

	public static function merge($subject, $delimiter, CompressedStringList $gzippedStrings)
    {
        if (!is_string($subject)) {
            $subject = json_encode($subject);
        }

        $subjectParts = explode($delimiter, $subject);

        $merged = new CompressedString();

        foreach ($subjectParts as $part) {
            $merged->write($part);
            if (!$gzippedStrings->isEmpty()) {
                $string = $gzippedStrings->dequeue();

                $readStream = $string->getReadOnlyStream();
                while ($buffer = $readStream->read(4096)) {
                    $merged->write($buffer);
                }
            }
        }

        return $merged;
    }

    public function enqueue(CompressedString $string)
    {
        return $this->queue->enqueue($string);
    }

    public function dequeue()
    {
        return $this->queue->dequeue();
    }

    public function isEmpty()
    {
        return $this->queue->isEmpty();
    }

    public function count()
    {
        return $this->queue->count();
    }
}