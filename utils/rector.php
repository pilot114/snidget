<?php

declare(strict_types=1);

use Rector\Caching\ValueObject\Storage\MemoryCacheStorage;
use Rector\Config\RectorConfig;
use Rector\PHPUnit\Set\PHPUnitSetList;
use Rector\Set\ValueObject\SetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->cacheClass(MemoryCacheStorage::class);

    $rectorConfig->paths([
        '/app/src',
        '/app/utils/tests',
    ]);

    $rectorConfig->skip([
        '/app/src/functions.php',
        '/app/utils/tests/fixtures',
    ]);

    $rectorConfig->sets([
        SetList::DEAD_CODE,
        SetList::CODE_QUALITY,
        SetList::PHP_82,
        SetList::PSR_4,
        SetList::TYPE_DECLARATION,
        SetList::EARLY_RETURN,
        PHPUnitSetList::PHPUNIT_91,
    ]);

    $rectorConfig->importNames();
    $rectorConfig->importShortClasses(false);
};
