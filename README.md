# api-authentic-document-bundle

This Symfony 4.4 bundle provides API endpoints for

- TBD

for the API-Gateway.

## Prerequisites

- API Gateway with openAPI/Swagger

## Installation

### Step 1

Copy this bundle to `./bundles/api-authentic-document-bundle`

### Step 2

Enable this bundle in `./config/bundles.php` by adding this element to the array returned:

```php
...
    return [
        ...
        DBP\API\AuthenticDocumentBundle\DbpAuthenticDocumentBundle::class => ['all' => true],
    ];
}
```

### Step 3

Add this bundle to `./symfony.lock`:

```json
...
    "dbp/api-authentic-document-bundle": {
        "version": "dev-master"
    },
...
```

## Configuration

You need to set an environment variable `MESSENGER_TRANSPORT_DSN` in your `.env` file or by any other means.
[Redis](https://redis.io/) is also the best way for this.

Example:

```dotenv
MESSENGER_TRANSPORT_DSN=redis://redis:6379/local-messages/symfony/consumer?auto_setup=true&serializer=1&stream_max_entries=0&dbindex=0
```

## Development

The `console` shell script in the `docker` directory is running commands inside the php container.

### Send test message

```bash
cd docker && ./console dbp:message-test
```

### Consume messages

```bash
cd docker && ./console messenger:consume async

# you can stop the worker in another terminal
cd docker && ./console messenger:stop-workers
```

only consume one message and quit worker:

```bash
cd docker && ./console messenger:consume async --limit=1
```
