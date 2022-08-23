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
30.07.22 - modules (Boxes)  
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
 