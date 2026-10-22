<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\SetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/../src',
        __DIR__ . '/tests',
    ]);

    $rectorConfig->skip([
        __DIR__ . '/../src/functions.php',
        __DIR__ . '/tests/fixtures',
        // правила, появившиеся в rector 2.7: меняют поведение (strict_types,
        // типы параметров замыканий там, где ключи бывают int, а элементы — массивы)
        \Rector\TypeDeclaration\Rector\StmtsAwareInterface\SafeDeclareStrictTypesRector::class,
        \Rector\TypeDeclaration\Rector\FuncCall\AddArrayFunctionClosureParamTypeRector::class,
        \Rector\TypeDeclaration\Rector\ClassMethod\AddParamStringTypeFromSprintfUseRector::class,
        \Rector\TypeDeclaration\Rector\ClassMethod\ArrayParamTypeByMethodCallTypeRector::class,
        \Rector\TypeDeclaration\Rector\ClassMethod\ObjectParamTypeByMethodCallTypeRector::class,
        \Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromReturnNewRector::class,
        \Rector\TypeDeclaration\Rector\ArrowFunction\AddArrowFunctionReturnTypeRector::class,
    ]);

    $rectorConfig->sets([
        SetList::DEAD_CODE,
        SetList::CODE_QUALITY,
        SetList::PHP_84,
        SetList::TYPE_DECLARATION,
        SetList::EARLY_RETURN,
    ]);

    $rectorConfig->importNames();
    $rectorConfig->importShortClasses(false);
};
