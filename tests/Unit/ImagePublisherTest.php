<?php

namespace Tests\Unit;

use App\Services\ImagePublisher;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use PHPUnit\Framework\MockObject\Exception;
use Tests\TestCase;

class ImagePublisherTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function test_it_publishes_to_image_queue(): void
    {
        // Arrange
        $payload = [
            'pornstar_id' => 1,
            'name' => 'Test Star',
            'url' => 'https://example.com/test.jpg',
        ];

        $expectedQueue = 'image-download';

        // Fake AMQPChannel and expect queue_declare and basic_publish
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

        // Stub ImagePublisher to inject the mock channel
        $publisher = new class($mockChannel) extends ImagePublisher {
            // @phpstan-ignore-next-line
            public function __construct($mockChannel)
            {
                $this->channel = $mockChannel;
                $this->queue = $this->getQueueName();
                $this->channel->queue_declare($this->queue, false, true, false, false);
            }
        };

        // Act
        $publisher->publish($payload);
    }
}
