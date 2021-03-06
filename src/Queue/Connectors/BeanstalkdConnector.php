<?php

namespace Fulfillment\TriagedQueues\Queue\Connectors;

use Fulfillment\TriagedQueues\Exceptions\NoHostException;
use Fulfillment\TriagedQueues\Queue\Queues\BetterBeanstalkdQueue;
use Illuminate\Queue\BeanstalkdQueue;
use Illuminate\Queue\Connectors\ConnectorInterface;
use Illuminate\Support\Arr;
use Pheanstalk\Pheanstalk;
use Pheanstalk\PheanstalkInterface;
use Log;

class BeanstalkdConnector extends \Illuminate\Queue\Connectors\BeanstalkdConnector
{
    /**
     * Establish a queue connection.
     *
     * @param  array $config
     * @return \Illuminate\Contracts\Queue\Queue
     * @throws NoHostException
     */
    public function connect(array $config)
    {
        // get all config keys with 'host' in it
        $hosts = array_filter(array_keys($config), function($key) { return strpos($key, 'host') !== false;});
        $maxAttempts = isset($config['attempts']) ? $config['attempts'] : 1;

        // sort by numeric ending so we tier hosts correctly
        natsort($hosts);

        foreach($hosts as $host) {
            $pheanstalk = new Pheanstalk($config[$host], Arr::get($config, 'port', PheanstalkInterface::DEFAULT_PORT), Arr::get($config, 'socketTimeout'));

            // test pheanstalk to see if we have a connection
            $attempts = 0;
            while($attempts < $maxAttempts) {
                if($pheanstalk->getConnection()->isServiceListening()) { // found a working host
                    $queue = new BetterBeanstalkdQueue(
                        $pheanstalk, $config['queue'], Arr::get($config, 'ttr', Pheanstalk::DEFAULT_TTR)
                    );
                    if(null !== $job = Arr::get($config, 'job')) {
						$queue->setCustomPayloadJob($job);
                    }
	                return $queue;
                } else {
                    $attempts++;
                }
            }
            Log::warning('Beanstalk host was unreachable.', ['beanstalkHost' => $config[$host], 'beanstalkAttempts' => $maxAttempts]);
        }

        // no working service found!
        throw new NoHostException('No working host found for beanstalkd driver.');
    }
}