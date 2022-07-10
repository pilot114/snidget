Snidget - idiomatic and ~~nerdy~~ smart php microframework

# What is a "Snidget" ?

The Golden Snidget was a small golden magical bird with fully rotational wings,  
best known for early use in the wizarding game of Quidditch, eventually being replaced by the Golden Snitch

![The Golden Snidget](https://static.wikia.nocookie.net/harrypotter/images/4/40/Golden_Snidget_HM_Icon.png/revision/latest/scale-to-width-down/320?cb=20201129013514)

# Features

- php 8 features
- transparent architecture without magic
- best practices for scalable architectures (DDD and contracts)
- full compatible with "12-factor app" concept
- thoughtful api architecture from the box
- compability with all PSR
- advanced code generation
- encourages the use of effective algorithms and patterns

# App structure

    public - dir for public scripts
    data - outer data project
    app - dir for you source code
     ┣━━ Command - CLI handlers
     ┣━━ Controller - HTTP handlers
     ┣━━ Domain - own domain classes of project
     ┣━━ DTO - data transfer objects
     ┣━━ Middleware - addition flexible HTTP handlers
     ┣━━ app - CLI entrypoint (can be renamed)
     ┗━━ container.php - DI configuration

# App design

https://en.wikipedia.org/wiki/Multitier_architecture
App design base on classic 3 tier architecture: Presentation layer (API), Logic layer (Domain) and Data Layer.

Tekhnologii_proektirovania_baz_dannykh_2019_Osipov
chapters 4-9 (~ 100 pages) - base

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
- RouteTemplate - attr for controller for typical route set (controller/action, rest etc)

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

# need improve:
- feature-centric Boxes (one file - copy to App struct, more complex - copy to app/feature dir)
- Exception
- Api
- Admin - manage all enities
- Logger
- PSR
- Migrations
- (https://blog.jetbrains.com/ru/phpstorm/2021/12/phpstorm-2021-3-release/#new)
