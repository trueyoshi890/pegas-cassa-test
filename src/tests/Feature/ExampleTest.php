<?php

declare(strict_types=1);

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Jobs\FillBinJob;
use App\Models\File;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Queue\Queue;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function testSuccessCase(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        Storage::fake('public');
        Bus::fake();

        $expectedResultFile = UploadedFile::fake()->create('expected.xlsx', 100);

        $file = UploadedFile::fake()->create('document.xlsx', 100);

        $sign = hash_hmac('sha256', file_get_contents($file->getPathname()), $user->secret_key);

        Str::shouldReceive('random')
            ->once()
            ->andReturn('TEST123');

        $response = $this->post(
            route('file.fill-bin'),
            [
                'file' => $file,
            ],
            [
                'X-Signature' => $sign,
            ]
        );

        $response->assertStatus(200);

        $dbFile = File::query()->firstOrFail();

        $this->assertDatabaseHas((new File())->getTable(), [
           'id' => $dbFile->id,
           'status' => 'pending',
        ]);

        Storage::disk('public')->assertExists('uploads/' . $file->hashName());
        Bus::assertDispatched(FillBinJob::class);

        Http::fake([
            'https://api.bincodes.com/multi-bin/*' => Http::response(['success' => true], 200),
        ]);

        $this->artisan('queue:work --once');

        // bins seeded successfully
        $this->assertSame(file_get_contents($expectedResultFile->path()), file_get_contents($dbFile->path));

        $signData = json_encode(['file_id' => $file->id], JSON_THROW_ON_ERROR);

        $signature = hash_hmac('sha256', $signData, $file->user->secret_key);

        // check pusher with signature
        // Broadcast::assertDispatched(FileProcessed::class, function ($event, $channels, $queue) use ($file) {
        //    return $event->file->id === $file->id && $event->signature === $signature;
        //});

        $this->assertDatabaseHas((new File())->getTable(), [
            'id' => $dbFile->id,
            'status' => 'processed',
        ]);
    }
}
