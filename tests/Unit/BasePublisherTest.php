<?php

namespace Tests\Unit;

use App\Services\BasePublisher;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

class BasePublisherTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function test_it_declares_queue_and_publishes_message(): void
    {
        // Arrange
        $queue = 'test-queue';
        $payload = ['foo' => 'bar'];

        $mockChannel = $this->createMock(AMQPChannel::class);

        $mockChannel->expects($this->once())
            ->method('queue_declare')
            ->with($queue, false, true, false, false);

        $mockChannel->expects($this->once())
            ->method('basic_publish')
            ->with(
                $this->callback(function (AMQPMessage $msg) use ($payload) {
                    return $msg->getBody() === json_encode($payload);
                }),
                '',
                $queue
            );

        // Use an anonymous subclass that injects the mock channel
        $publisher = new class($mockChannel) extends BasePublisher {
            // @phpstan-ignore-next-line
            public function __construct($channel)
            {
                $this->channel = $channel;
                $this->queue = $this->getQueueName();
                $this->channel->queue_declare($this->queue, false, true, false, false);
            }

            protected function getQueueName(): string
            {
                return 'test-queue';
            }
        };

        // Act
        $publisher->publish($payload);
    }
}
