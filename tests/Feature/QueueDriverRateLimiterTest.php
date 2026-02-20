<?php

use Illuminate\Bus\Queueable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Testing\TestResponse;

beforeEach(function () {
    TestPestRateLimitedJob::$executed = [];

    config()->set('cache.default', 'array');

    // Drain any leftover messages from previous tests
    $connection = Queue::connection('azurepush');

    while ($connection->pop() !== null) {
        // Keep popping until the queue is empty
    }
});

/**
 * Helper to pop a message from Azurite and POST it to the HTTP endpoint,
 * simulating the real Azure push flow.
 */
function popAndPostToHandler(\Orchestra\Testbench\TestCase $test): TestResponse
{
    $connection = Queue::connection('azurepush');

    /** @var \Squigg\AzureQueueLaravel\AzureJob $azureJob */
    $azureJob = $connection->pop();
    expect($azureJob)->not->toBeNull();

    $rawMessage = $azureJob->getAzureJob();
    $payload = json_decode($azureJob->getRawBody(), true);

    return $test->postJson(route('azure-queue-handle'), [
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
}

it('releases jobs back to the queue when rate limit is hit', function () {
    // Define a strict rate limiter: 1 per minute
    RateLimiter::for('custom-driver-test', function ($job) {
        return Limit::perMinute(1);
    });

    // Dispatch two jobs
    TestPestRateLimitedJob::dispatch('payload-1');
    TestPestRateLimitedJob::dispatch('payload-2');

    // Process the FIRST job via the HTTP endpoint (should succeed)
    $response1 = popAndPostToHandler($this);
    $response1->assertSuccessful();

    expect(TestPestRateLimitedJob::$executed)->toHaveCount(1);

    // Process the SECOND job via the HTTP endpoint (should hit the rate limit and release)
    $response2 = popAndPostToHandler($this);
    $response2->assertSuccessful();

    // The second job's handle() should NOT have run — rate limiter released it
    expect(TestPestRateLimitedJob::$executed)->toHaveCount(1);
});

class TestPestRateLimitedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public static $executed = [];

    public $payload;

    public function __construct($payload)
    {
        $this->payload = $payload;
    }

    public function middleware()
    {
        return [new RateLimited('custom-driver-test')];
    }

    public function handle()
    {
        self::$executed[] = $this->payload;
    }
}
