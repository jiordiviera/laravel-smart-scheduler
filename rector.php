<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\SetList;

return static function (RectorConfig $rectorConfig): void {
    // Basic sets to improve code quality and remove dead code
    $rectorConfig->sets([
        SetList::DEAD_CODE,
        SetList::CODE_QUALITY,
        SetList::CODING_STYLE,
    ]);

    // Process src and tests
    $rectorConfig->paths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ]);

    // Ignore vendor and storage
    $rectorConfig->skip([
        __DIR__ . '/vendor',
        __DIR__ . '/storage',
    ]);
};
