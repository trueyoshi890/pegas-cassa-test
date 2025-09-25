<?php

use App\Services\FillBin\XlsBinProcessorService;

return [
    'processors' => [
        'xls' => XlsBinProcessorService::class,
        'xlsx' => XlsBinProcessorService::class,
    ],
];
