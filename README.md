# php-feed-ingestor

The `php-feed-ingestor` service is responsible for ingesting a daily JSON feed of pornstars
and publishing the relevant data to RabbitMQ for downstream processing. It acts as the entry
point of the data ingestion pipeline in a distributed microservices system.

## 🧠 Responsibilities

- Downloads the daily pornstar feed from a remote JSON URL.
- Parses the feed and dispatches individual feed items as messages to RabbitMQ.
- Publishes image download jobs to the `image-download` queue.
- Publishes pornstar data to the `pornstar-events` queue for database persistence.

## ⚙️ Tech Stack

- PHP 8.4
- Laravel 12
- Laravel Queue + RabbitMQ (via AMQP)
- Docker

## 🚀 Usage

The feed ingestion is triggered via an Artisan command (`ingest-pornstar-feed`) that is
scheduled to run every minute (for testing purposes). In production, this can be adjusted
to run once daily via Laravel's scheduler and system cron.

## 🧪 Testing

Run the test suite inside the container:

```bash
docker exec -it  infra-deployment-php-feed-ingestor-1 php artisan test
```

## 📂 Environment

Set the required environment variables via `.env` or using the mounted file
`.envs/php-feed-ingestor.env`.

## 🔗 Related

- Sends image jobs to: `php-image-worker`
- Sends pornstar events to: `php-api-service`
