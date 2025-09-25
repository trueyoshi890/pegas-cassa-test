<?php

declare(strict_types=1);

namespace App\Services\Resolvers;

use App\Services\FillBin\BinFromFileProcessorInterface;
use Illuminate\Support\Facades\App;
use RuntimeException;

final class BinFromFileProcessorResolver
{
    public function resolve(string $fileType): BinFromFileProcessorInterface
    {
        if (!isset(config('bin.processors')[$fileType])) {
            throw new RuntimeException(sprintf('Bin file processor "%s" not found', $fileType));
        }

        $processor = config('bin.processors')[$fileType];

        if (!is_a($processor, BinFromFileProcessorInterface::class, true))
        {
            throw new RuntimeException(sprintf('Bin file processor "%s" unexpected', $fileType));
        }

        return App::make($processor);
    }
}
