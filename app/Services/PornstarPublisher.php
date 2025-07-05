<?php

namespace App\Services;

class PornstarPublisher extends BasePublisher
{
    protected function getQueueName(): string
    {
        return config('services.rabbitmq.pornstar_queue', 'pornstar-events');
    }
}
