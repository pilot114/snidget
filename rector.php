<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\SetList;

// Наборы — как в pretty-php; версия PHP берётся из require.php в composer.json.
return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/App',
        __DIR__ . '/public',
        __DIR__ . '/utils/tests',
    ])
    ->withSets([
        SetList::CODE_QUALITY,
        SetList::CODING_STYLE,
        SetList::DEAD_CODE,
        SetList::PRIVATIZATION,
        SetList::TYPE_DECLARATION,
        SetList::EARLY_RETURN,
        SetList::INSTANCEOF,
    ])
    ->withSkip([
        // аннотации @var нужны phpstan, даже когда rector считает их лишними
        \Rector\DeadCode\Rector\Node\RemoveNonExistingVarAnnotationRector::class,
        // ломает конструкторы с promoted-свойствами
        \Rector\DeadCode\Rector\ClassMethod\RemoveParentDelegatingConstructorRector::class,
        // сигнатуры публичных методов и конструкторов — контракт: их зовут контейнер,
        // фреймворк и CLI по имени, а не только код, который видит rector
        \Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPublicMethodParameterRector::class,
        \Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPromotedPropertyRector::class,
        // свойства заполняют через reflection (ORM, гидраторы, тестовые стабы):
        // тип и readonly это ломают
        \Rector\TypeDeclaration\Rector\Property\TypedPropertyFromStrictConstructorRector::class,
        \Rector\TypeDeclaration\Rector\Property\TypedPropertyFromAssignsRector::class,
        __DIR__ . '/src/functions.php',
        __DIR__ . '/utils/tests/fixtures',
        __DIR__ . '/utils/tests/coverage',
        // правила rector 2.7, меняющие поведение (strict_types, типы параметров замыканий там,
        // где ключи бывают int, а элементы — массивы)
        \Rector\TypeDeclaration\Rector\StmtsAwareInterface\SafeDeclareStrictTypesRector::class,
        \Rector\TypeDeclaration\Rector\FuncCall\AddArrayFunctionClosureParamTypeRector::class,
        \Rector\TypeDeclaration\Rector\ClassMethod\AddParamStringTypeFromSprintfUseRector::class,
        \Rector\TypeDeclaration\Rector\ClassMethod\ArrayParamTypeByMethodCallTypeRector::class,
        \Rector\TypeDeclaration\Rector\ClassMethod\ObjectParamTypeByMethodCallTypeRector::class,
        \Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromReturnNewRector::class,
        \Rector\TypeDeclaration\Rector\ArrowFunction\AddArrowFunctionReturnTypeRector::class,
    ])
    ->withImportNames(importShortClasses: false)
    ->withComposerBased(phpunit: true)
    ->withPhpSets();
