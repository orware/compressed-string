<?php
namespace Orware\Compressed;

class StringList
{
	protected $queue = null;

	public function __construct()
	{
		$this->queue = new \SplQueue();
		$this->queue->setIteratorMode(\SplDoublyLinkedList::IT_MODE_FIFO | \SplDoublyLinkedList::IT_MODE_DELETE);
	}

	public function enqueue(String $string)
	{
		return $this->queue->enqueue($string);
	}

	/**
	*
	*
	* @return Orware\Compressed\String
	*/
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
