Snidget - idiomatic and ~~nerdy~~ smart php microframework

# What is a "Snidget" ?

The Golden Snidget was a small golden magical bird with fully rotational wings,  
best known for early use in the wizarding game of Quidditch, eventually being replaced by the Golden Snitch

![The Golden Snidget](https://static.wikia.nocookie.net/harrypotter/images/4/40/Golden_Snidget_HM_Icon.png/revision/latest/scale-to-width-down/320?cb=20201129013514)

# Features

- php 8 features
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

For small projects, it is recommended to use the default project structure (although it can always be changed).  
When the project grows, the feature-based approach is recommended.  

App design base on classic 3 tier architecture: Presentation layer (API), Logic layer (Domain) and Data Layer.
