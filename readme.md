Snidget - idiomatic and ~~nerdy~~ smart php framework

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
     ┣━━ CLI - CLI handlers
     ┣━━ Controller - HTTP handlers
     ┣━━ Domain - own domain classes of project
     ┣━━ DTO - data transfer objects
     ┣━━ Middleware - addition flexible HTTP handlers
     ┣━━ cli.php - CLI entrypoint
     ┗━━ container.php - DI configuration

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

- no incapsulate without reason (rich index file)
- YAGNI first - code is written upon request
- low level separated by php modules
- "enrichment" principe - set base class only if need. In him DI also must work
- RouteTemplate - attr for controller for typical route set (controller/action, rest etc)
- < 100 line on each file
- update a bit everyday

# worklog

05.05.22 - base architecture, router with attributes, autoload  
06.05.22 - DI  
07.05.22 - DTO Config, PDO module, Tables  
08.05.22 - Tables improve, Types & Collections  
09.05.22 - refactoring, architecture design  
10.05.22 - refactoring
11.05.22 - Middlewares, tests

need improve:
Exception
Promices?