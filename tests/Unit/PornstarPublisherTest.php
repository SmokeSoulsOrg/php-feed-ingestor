<?php

namespace Tests\Unit;

use App\Services\PornstarPublisher;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use PHPUnit\Framework\MockObject\Exception;
use Tests\TestCase;

class PornstarPublisherTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function test_it_publishes_to_pornstar_queue(): void
    {
        // Arrange
        $payload = [
            'id' => 42,
            'name' => 'Test Star',
            'aliases' => ['Alias A', 'Alias B'],
        ];

        $expectedQueue = 'pornstar-events';

        // Mock AMQPChannel
        $mockChannel = $this->createMock(AMQPChannel::class);

        $mockChannel->expects($this->once())
            ->method('queue_declare')
            ->with($expectedQueue, false, true, false, false);

        $mockChannel->expects($this->once())
            ->method('basic_publish')
            ->with(
                $this->callback(function (AMQPMessage $msg) use ($payload) {
                    return $msg->getBody() === json_encode($payload);
                }),
                '',
                $expectedQueue
            );

        // Create a testable subclass injecting the mock channel
        $publisher = new class($mockChannel) extends PornstarPublisher {
            // @phpstan-ignore-next-line
            public function __construct($channel)
            {
                $this->channel = $channel;
                $this->queue = $this->getQueueName();
                $this->channel->queue_declare($this->queue, false, true, false, false);
            }
        };

        // Act
        $publisher->publish($payload);
    }
}
