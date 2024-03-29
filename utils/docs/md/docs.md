# documentation

Documentation is best part of Snidget.  
Most of it describes use cases from common practices web-development

- Intro (typical framework features)
- Principles
- Attributes
- Types based
- Class descriptions
- Advanced: plug-and-play features (patterns and use-cases)
- Advanced: plug-and-play libs (PSR compatible and other - by decorators)
- Example project - "Gamers!"

# (move to parts of documentation)

- YAGNI first - code is written upon request
- low level separated by php modules
- "enrichment" principe - set base class only if need. In him DI also must work


# worklog

05.05.22 - base architecture, router with attributes, autoload  
06.05.22 - DI  
07.05.22 - DTO Config, PDO module, Tables  
08.05.22 - Tables improve, Types & Collections  
09.05.22 - refactoring, architecture design  
10.05.22 - refactoring  
12.05.22 - Middlewares, phpstan  
13.05.22 - Middlewares  
14.05.22 - Middlewares  
15.05.22 - Duck Validation  
25.05.22 - Route prefixes, admin panel  
26.05.22 - Internal templating (for admin)  
27.05.22 - Base design data layer  
30.05.22 - duck upgrade, refactoring config load  
09.06.22 - Add psalm and composer-git-hooks  
09.07.22 - Events  
15.07.22 - Events improve  
30.07.22 - Modules
15-21.08.22 - async mode  
- docker, TODOs, async events, tests

# need improve:
- Async mode base on parallel
- Exception
- Api
- Admin - manage all enities
- Logger
- PSR?
- Migrations
- (https://blog.jetbrains.com/ru/phpstorm/2021/12/phpstorm-2021-3-release/#new)


# Domain

- detect level complexity and meaning team of domain logic
  patterns:
- transaction script (use case by client. simple, but no flexibility). Maybe use Command pattern
- domain model aka entity (for enterprise, poor(only data) / rich)
- table module

Service Layer - interface domain-application
 




Асинхронность в PHP
Многие php-разработчики с опаской относятся к теме асинхронности в PHP.
Обычно это происходит по одной из следующих причин:
- человек знает только PHP, а все нестандартные концепции, чуждые "классической разработке под PHP" отметает на уровне идеи и разбираться в них не хочет
- "Это все не нужно именно в PHP". Вполне здравая позиция, ведь "PHP рожден чтобы умирать" и практически весь код,
 написанный сообществом - синхронный, а, как известно, писать свои велосипеды весьма больно. Проблемы с синхронностью просто игнорируются, в редких случаях что-то выносится в очереди
- человек хорошо знает другой язык, например C# или JavaScript, где асинхронный код предусмотрен by design и как только вопрос синхронности в PHP становится проблемой - пишет код на более подходящем ЯП и живет себе дальше счастливо

Эта статья будет полезна всем, кто избегает асинхронности по любой из этих причин, и вот почему:
- Разобраться в теме полезно для общего понимания. Надо четко понимать как сильные, так и слабые стороны своего стека
- Да, раньше в PHP асинхронности не было. А еще не было ООП и нормальной типизации. Язык развивается и область его применения также расширяется, прогрессивная часть сообщества следит за обновами и создает впролне себе production ready решения
- Что если вам не нужно лучшее, а достаточно просто хорошего решения? Переписывать проект на другой ЯП редко бывает экономически выгодно

Но прежде чем погружаться в дебри тонкостей вопроса, нужно усвоить немного теории

Немного теории
Представим, что вам нужно написать код, который максимально быстро решит поставленную ресурсоемкую задачу.
Чтобы не отвлекаться на несущественные в данном случае вопросы, представьте что вы пишете хороший код (да, мы все любим фантазировать), памяти/канала/дескрипторов хватает с избытком и главным ограничителем наших возможностей выступает процессорное время. Чем круче ваш процессор, тем быстрее выполнится задача! Посмотрим как это должно выглядеть в идеале:

cpu 100%
старт---выполнение---финиш

Вы запустили программу, она на 100% утилизировала ресурсы CPU и выполнилась максимально эффективно... Но стоп, мы же знаем что современные процессоры имеют по несколько ядер. К сожалению, наш процесс будет выполняться только на 1 ядре
 

cpu 0%
cpu 0%
cpu 0%
cpu 100%
или 25% total cpu

Также сделаю смелое предположение, что ваша программа запускается под управлением многозадачной операционной системы

служебные сервисы, а также месседжеры/браузеры/прочий user space
total cpu 20% 
(картинка с покерным столом)

Конечно, менеджер процессов ОС (OPM) достаточно умный, чтобы выделять процессорное время не поровну, а с некоторыми приоритетами, поэтому ваша требовательная к CPU программа приберет себе большую часть ресурсов. Неочевидная проблема в том, что даже якобы ничего не делающие процессы, хоть на долю миллисекунды да получают управление в течении каждого цикла работы OPM, причем получают гарантированно. Даже если процесс "съел" всего 1 такт, переключение контекста между процессами "съедает" гораздо больше. Больше процессов - больше переключений - больше потери драгоценных тактов.
В самых простых случаях это действительно неизбежно - бывает что програме просто нечего делать, пока не будет получен результат. Но если вспомнить что основная область применения PHP - бэкенд с множеством клиентов, с большой вероятностью нам всегда есть чем занятся.

Радикальное решение обозначенных проблем - многопоточность, этой темы тут касаться не будем. Замечу лишь, что хорошо реализованная многопоточность не нуждается в асинхронности, т.е. в том же Golang вообще не страшно ждать результата операции, потому что его ждет всего лишь один поток выполнения, а их одновременно могут быть десятки тысячи (причем это не процессы, а легковесные треды, между которыми не происходит затратного переключения контекста)

В случае, когда многопоточность нам не доступна (или мы почему-то не хотим её использовать) - используются асинхронные вызовы. Например, node.js долгое время был устроен именно так - один процесс, внутри крутится event-loop, внутри него - весь остальной код. И черт возьми, это работает!

Event Loop
таймеры, промисы, дефферы - чего только не придумают чтобы многопоточность не писать. Рассмотрим все по порядку.

Готовые решения
ReactPHP / AMPHP -> Revolt, Swoole, Workerman
Релизы и всякие значимые моменты в развитии асинхронного php

Свой велосипед

Для начала создадим сам цикл, это несложно.



У меня выдает что-то около 30 наносекунд на итерацию. Для того, чтобы избежать использования всего CPU на пустой цикл, нужно добавить хотябы минимальную задержку на каждой итерации с помощью usleep(0). Теперь на итерацию уходит около 50 микросекунд. Отлично, с этим можно работать.


---
- добавить хэндлеры, которые будут вызываться внутри цикла. Также будет массив специальных хэндлеров, вызываемых периодически (они же таймеры).
- сделать генератор запросов, который будет эмулировать реальные запросы с данными.
- далее - события, чтобы маршрутизировать данные
- далее - Файберы - это способ останавливать любые функции, а не только генераторы в любом месте (в том числе во вложенных вызовах) и возобновлять их.
---

