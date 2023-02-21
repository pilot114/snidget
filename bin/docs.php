#!/usr/local/bin/php
<?php

use League\CommonMark\CommonMarkConverter as CommonMarkConverterAlias;

include_once __DIR__ . '/../vendor/autoload.php';

$converter = new CommonMarkConverterAlias([
    'html_input' => 'strip',
    'allow_unsafe_links' => false,
]);

function load(string $filename): string {
    global $converter;
    $md = file_get_contents(__DIR__ . '/../docs/' . $filename);
    return $converter->convert($md);
}

$pages = [
    [
        'title' => 'Концепция', 'overview' => 'Для чего нужен этот фреймворк', 'active' => true,
        'content' => load('intro.md'), 'type' => 'part'
    ], [
        'title' => 'Основы', 'overview' => 'Базовые понятия', 'active' => false,
        'content' => load('docs.md'), 'type' => 'part'
    ], [
        'title' => 'HTTP', 'overview' => 'Роутинг, контроллеры, посредники', 'active' => false,
        'content' => load('async.md'), 'type' => 'part'
    ], [
        'title' => 'CLI', 'overview' => 'Консольные команды', 'active' => false,
        'content' => load('intro.md'), 'type' => 'part'
    ], [
        'title' => 'Базы данных', 'overview' => 'Работа с персистентными данными', 'active' => false,
        'content' => load('intro.md'), 'type' => 'part'
    ], [
        'title' => 'Расширение функционала', 'overview' => 'PSR, модули, инфраструктура', 'active' => false,
        'content' => load('intro.md'), 'type' => 'part'
    ],

    [
        'title' => 'Список атрибутов', 'active' => false,
        'content' => load('intro.md'), 'type' => 'addition'
    ], [
        'title' => 'Список событий', 'active' => false,
        'content' => load('intro.md'), 'type' => 'addition'
    ],

    [
        'title' => 'Async', 'active' => false,
        'content' => load('intro.md'), 'type' => 'module'
    ],
];

$template = file_get_contents(__DIR__ . '/../docs/index.template.html');
$content = str_replace('%pages%', json_encode($pages), $template);
file_put_contents(__DIR__ . '/../docs/index.html', $content);
