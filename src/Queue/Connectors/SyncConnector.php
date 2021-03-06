<?php

namespace Fulfillment\TriagedQueues\Queue\Connectors;

use Fulfillment\TriagedQueues\Queue\Queues\BetterSyncQueue;
use Illuminate\Support\Arr;

class SyncConnector extends \Illuminate\Queue\Connectors\SyncConnector
{
	/**
	 * Establish a queue connection.
	 *
	 * @param  array  $config
	 * @return \Illuminate\Contracts\Queue\Queue
	 */
	public function connect(array $config)
	{
		$queue = new BetterSyncQueue;
		if(null !== $job = Arr::get($config, 'job')) {
			$queue->setCustomPayloadJob($job);
		}
		return $queue;
	}
}