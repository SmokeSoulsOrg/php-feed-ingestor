<?php

namespace App\Services;

class ImagePublisher extends BasePublisher
{
    protected function getQueueName(): string
    {
        return config('services.rabbitmq.image_queue', 'image-download');
    }
}
