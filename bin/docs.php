#!/usr/local/bin/php
<?php

use League\CommonMark\CommonMarkConverter as CommonMarkConverterAlias;

include_once __DIR__ . '/../vendor/autoload.php';

$converter = new CommonMarkConverterAlias([
    'html_input' => 'allow',
    'allow_unsafe_links' => false,
]);

function load(string $filename): string {
    global $converter;
    $md = file_get_contents(__DIR__ . '/../docs/md/' . $filename);
    return $converter->convert($md);
}

$pages = [
    [
        'title' => 'Концепция', 'overview' => 'Для чего нужен этот фреймворк', 'active' => true,
        'content' => load('intro.md'), 'type' => 'part'
    ], [
        'title' => 'Основы', 'overview' => 'Базовые понятия', 'active' => false,
        'content' => load('base.md'), 'type' => 'part'
    ], [
        'title' => 'HTTP', 'overview' => 'Роутинг, контроллеры, посредники', 'active' => false,
        'content' => load('http.md'), 'type' => 'part'
    ], [
        'title' => 'CLI', 'overview' => 'Консольные команды', 'active' => false,
        'content' => load('cli.md'), 'type' => 'part'
    ], [
        'title' => 'Базы данных', 'overview' => 'Работа с персистентными данными', 'active' => false,
        'content' => load('database.md'), 'type' => 'part'
    ], [
        'title' => 'Расширение функционала', 'overview' => 'события, PSR, модули, инфраструктура', 'active' => false,
        'content' => load('extra.md'), 'type' => 'part'
    ], [
        'title' => 'Фронт', 'overview' => 'Немного о уровне представления', 'active' => false,
        'content' => load('front.md'), 'type' => 'part'
    ],

    [
        'title' => 'Список атрибутов', 'active' => false,
        'content' => load('attributes.md'), 'type' => 'addition'
    ], [
        'title' => 'Список событий', 'active' => false,
        'content' => load('events.md'), 'type' => 'addition'
    ],

    [
        'title' => 'Admin', 'active' => false,
        'content' => load('admin.md'), 'type' => 'module'
    ], [
        'title' => 'Async', 'active' => false,
        'content' => load('async.md'), 'type' => 'module'
    ],
];

$template = file_get_contents(__DIR__ . '/../docs/index.template.html');
$content = str_replace('%pages%', json_encode($pages), $template);
file_put_contents(__DIR__ . '/../docs/index.html', $content);
