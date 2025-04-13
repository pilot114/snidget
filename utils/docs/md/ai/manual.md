https://github.com/llm-agents-php/agents - агенты
https://github.com/context-hub/generator - генератор контекста

### Common

см. https://docs.ctxgithub.com/advanced/development-steps.html

Алгоритм:
- определить тип задачи:
  - рефакторинг -> анализ проблемного кода
  - bug report -> анализ ошибки
  - feature -> анализ требований
- сложность задачи -> докомпозиция
- подготовить контекст:
  - определить стандарты кодирования
  - подготовить примеры похожего кода
  - собрать релевантные части кодовой базы
- сформулировать промпт
- если решение неудовлетворительное - уточнить запрос
- проверить код
- если решение есть проблемы - запросить улучшение
- интеграция, тестирование
- если тесты не проходят - запросить исправление

### Feature

Главная задача - дать правильный контекст

- Надо четко понимать, что мы хотим
- Добавить системный промпт (You are PHP expert...)
- Добавить Readme / документацию проекта
- с помощью генератора контекста и context.yaml провайдим код
- всякие json-schema и прочие спеки тоже можно скормить
- промпт на создание context.yaml тоже есть)
- provide github feature request
- provide detailed realization plan
- provide mermaid sequence diagram
- provide section for readme
- полученные provide можно добавить в контекст, предыдущие контексты можно удалить

### Bug report
### Refactoring

### Business Analysis

- explain structure of application
- provide mermaid sequence diagram about ...

### MCP

