<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\File;
use App\Services\Resolvers\BinFromFileProcessorResolver;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Psr\Log\LoggerInterface;

class FillBinJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly File $file,
        private readonly BinFromFileProcessorResolver $resolver,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @throws Exception
     */
    public function handle(): void
    {
        $fileId = $this->file->id;

        $file = DB::transaction(function () use ($fileId): ?File {
            $file = File::query()
                ->lockForUpdate()
                ->where('status', 'pending')
                ->where('id', $fileId)
                ->first();

            if ($file === null) {
                return null;
            }

            $file->status = 'processing';
            $file->save();

            return $file;
        });

        if ($file === null) {
            return;
        }

        $path = storage_path('app/' . $file->path);
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        $processor = $this->resolver->resolve($extension);
        $cardNumbers = $processor->getCardNumbers($path);

        $response = Http::get('https://api.bincodes.com/multi-bin/', [
            'format' => 'json',
            'api_key' => config('bin.api_key'),
            'bins' => implode(',', array_column($cardNumbers, 'bin')),
        ]);

        if (!$response->successful()) {
            $this->logger->error('Bin failed to retrieve bins from ' . $path);
            throw new Exception('Bins info response failed');
        }

        $body = $response->json();

        // map card numbers with response info
        $result = array_merge($body, $cardNumbers);

        // set bins to file
        $processor->setBins($path, $result);

        $signData = json_encode(['file_id' => $file->id], JSON_THROW_ON_ERROR);

        $signature = hash_hmac('sha256', $signData, $file->user->secret_key);
        // notify user (email/pusher etc)
        // event(new FileProcessed($file->id, $signature));

        $file->status = 'processed';
        $file->save();
    }
}
