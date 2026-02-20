<?php

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

beforeEach(function () {
    TestPestSimpleJob::$executed = [];

    // Drain any leftover messages from previous tests
    $connection = Queue::connection('azurepush');
    while ($connection->pop() !== null) {
    }
});

it('can push a job onto the queue', function () {
    TestPestSimpleJob::dispatch('hello-world');

    $connection = Queue::connection('azurepush');

    expect($connection->size())->toBeGreaterThanOrEqual(1);
});

it('can push a job and pop it off the queue', function () {
    TestPestSimpleJob::dispatch('hello-world');

    $connection = Queue::connection('azurepush');
    $job = $connection->pop();

    expect($job)->not->toBeNull();

    $job->fire();

    expect(TestPestSimpleJob::$executed)
        ->toHaveCount(1)
        ->toContain('hello-world');
});

it('returns null when popping from an empty queue', function () {
    $connection = Queue::connection('azurepush');

    // Drain any leftover messages
    while ($connection->pop() !== null) {
    }

    expect($connection->pop())->toBeNull();
});

it('processes a job end-to-end via the push HTTP endpoint', function () {
    // 1. Dispatch the job so it lands in Azurite with a real messageId / popReceipt
    TestPestSimpleJob::dispatch('hello-world');

    $connection = Queue::connection('azurepush');

    // 2. Pop the real message to get valid Azure metadata
    /** @var \Squigg\AzureQueueLaravel\AzureJob $azureJob */
    $azureJob = $connection->pop();
    expect($azureJob)->not->toBeNull();

    $rawMessage = $azureJob->getAzureJob();
    $payload = json_decode($azureJob->getRawBody(), true);

    // 3. Simulate the HTTP webhook Azure would normally send
    $response = $this->postJson(route('azure-queue-handle'), [
        'id' => $rawMessage->getMessageId(),
        'message' => $payload,
        'meta' => [
            'dequeueCount' => $rawMessage->getDequeueCount(),
            'expirationTime' => $rawMessage->getExpirationDate()->format('c'),
            'insertionTime' => $rawMessage->getInsertionDate()->format('c'),
            'nextVisibleTime' => $rawMessage->getTimeNextVisible()->format('c'),
            'popReceipt' => $rawMessage->getPopReceipt(),
            'queueName' => 'package-test-queue',
        ],
    ]);

    $response->assertSuccessful();
    expect($response->json('uuid'))->not->toBeEmpty();

    expect(TestPestSimpleJob::$executed)
        ->toHaveCount(1)
        ->toContain('hello-world');
});

it('returns a validation error when required fields are missing', function () {
    $response = $this->postJson(route('azure-queue-handle'), [
        'id' => Str::uuid()->toString(),
        // missing 'message' and 'meta'
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['message', 'meta']);
});

it('can release a job back to the queue when the pop receipt is still valid', function () {
    // 1. Dispatch a job that always releases itself
    TestPestAlwaysReleaseJob::dispatch('release-me');

    $connection = Queue::connection('azurepush');

    // 2. Pop the real message — this makes it invisible but NOT deleted
    /** @var \Squigg\AzureQueueLaravel\AzureJob $azureJob */
    $azureJob = $connection->pop();
    $rawMessage = $azureJob->getAzureJob();
    $payload = json_decode($azureJob->getRawBody(), true);

    // 3. Send via the HTTP endpoint with the valid popReceipt
    $response = $this->postJson(route('azure-queue-handle'), [
        'id' => $rawMessage->getMessageId(),
        'message' => $payload,
        'meta' => [
            'dequeueCount' => $rawMessage->getDequeueCount(),
            'expirationTime' => $rawMessage->getExpirationDate()->format('c'),
            'insertionTime' => $rawMessage->getInsertionDate()->format('c'),
            'nextVisibleTime' => $rawMessage->getTimeNextVisible()->format('c'),
            'popReceipt' => $rawMessage->getPopReceipt(),
            'queueName' => 'package-test-queue',
        ],
    ]);

    // The release() call should NOT throw — the pop receipt is valid
    $response->assertSuccessful();

    // The job's handle() was called, but it released itself back
    expect(TestPestAlwaysReleaseJob::$handled)->toBeTrue();

    // The message should reappear in the queue after the visibility timeout
    // Pop again to confirm it was released back
    sleep(1); // brief wait for the message to become visible
    $releasedJob = $connection->pop();
    expect($releasedJob)->not->toBeNull();
});

it('fails to release when the pop receipt is invalid', function () {
    // 1. Dispatch a job that always releases itself
    TestPestAlwaysReleaseJob::dispatch('will-fail');
    TestPestAlwaysReleaseJob::$handled = false;

    $connection = Queue::connection('azurepush');

    // 2. Pop the real message to get valid metadata
    /** @var \Squigg\AzureQueueLaravel\AzureJob $azureJob */
    $azureJob = $connection->pop();
    $rawMessage = $azureJob->getAzureJob();
    $payload = json_decode($azureJob->getRawBody(), true);

    // 3. Send via HTTP endpoint with a FAKE pop receipt
    $response = $this->postJson(route('azure-queue-handle'), [
        'id' => $rawMessage->getMessageId(),
        'message' => $payload,
        'meta' => [
            'dequeueCount' => $rawMessage->getDequeueCount(),
            'expirationTime' => $rawMessage->getExpirationDate()->format('c'),
            'insertionTime' => $rawMessage->getInsertionDate()->format('c'),
            'nextVisibleTime' => $rawMessage->getTimeNextVisible()->format('c'),
            'popReceipt' => 'fake-invalid-pop-receipt',
            'queueName' => 'package-test-queue',
        ],
    ]);

    // Azure will reject the updateMessage call — the endpoint should 500
    $response->assertServerError();
});

class TestPestSimpleJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public static array $executed = [];

    public function __construct(public string $payload) {}

    public function handle(): void
    {
        self::$executed[] = $this->payload;
    }
}

class TestPestAlwaysReleaseJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public static bool $handled = false;

    public function __construct(public string $payload) {}

    public function handle(): void
    {
        // Always release — simulates what RateLimited middleware does
        static::$handled = true;
        $this->release(0);
    }
}
