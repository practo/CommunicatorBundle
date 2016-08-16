<?php

namespace Practo\CommunicatorBundle\Queue;

use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

/**
 * Queue Factory
 */
class QueueFactory extends ContainerAware
{
    /**
     * Get Queue Object
     *
     * @return AbstractQueue
     */
    public function get()
    {
        $uri = $this->container->getParameter('communicator.queue.uri');
        if ($this->container->hasParameter('legacy_queue.sqs_spool_dir')) {
            $sqsSpoolDir = $this->container->getParameter('legacy_queue.sqs_spool_dir');
        } else {
            $sqsSpoolDir = null;
        }
        $parts = parse_url($uri);
        if (($parts['scheme'] == 'https' || $parts['scheme'] == 'http') &&
            ($hParts = explode('.', $parts['host'], 3)) &&
            ($hParts[0] == 'sqs' && $hParts[2] == 'amazonaws.com')) {
            return new SQSQueue($uri, $sqsSpoolDir);
        } else if ($parts['scheme'] == 'beanstalk') {
            return new BeanstalkQueue($uri);
        } else {
            throw new \Exception('Unsupported uri scheme');
        }
    }
}
