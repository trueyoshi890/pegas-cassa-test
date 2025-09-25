<?php

declare(strict_types=1);

namespace App\Services\FillBin;

interface BinFromFileProcessorInterface
{
    public function getCardNumbers(string $filePath): array;
    public function setBins(string $filePath, array $cardToBinMap): void;
}
