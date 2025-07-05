<?php

namespace App\Services;

use Exception;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

abstract class BasePublisher
{
    protected AMQPChannel $channel;
    protected string $queue;

    /**
     * @throws Exception
     */
    public function __construct()
    {
        $connection = new AMQPStreamConnection(
            config('services.rabbitmq.host'),
            config('services.rabbitmq.port'),
            config('services.rabbitmq.user'),
            config('services.rabbitmq.password')
        );

        $this->channel = $connection->channel();
        $this->queue = $this->getQueueName();

        // Declare the queue this publisher will use
        $this->channel->queue_declare($this->queue, false, true, false, false);

        // 🔁 Declare all other known queues (safe if already exist)
        $knownQueues = array_unique([
            'default',
            config('services.rabbitmq.pornstar_queue', 'pornstar-events'),
            config('services.rabbitmq.image_queue', 'image-download'),
        ]);

        foreach ($knownQueues as $q) {
            if ($q !== $this->queue) {
                $this->channel->queue_declare($q, false, true, false, false);
            }
        }
    }

    abstract protected function getQueueName(): string;

    public function publish(array $payload): void
    {
        $message = new AMQPMessage(json_encode($payload), [
            'content_type' => 'application/json',
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
        ]);

        $this->channel->basic_publish($message, '', $this->queue);
    }

    /**
     * @throws Exception
     */
    public function __destruct()
    {
        if (isset($this->channel) && $this->channel->is_open()) {
            try {
                $connection = $this->channel->getConnection();
                $this->channel->close();
                $connection->close();
            } catch (\Throwable $e) {
                \Log::warning('[Publisher] Failed to close RabbitMQ connection gracefully', [
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
}
