<?php

namespace Wshell\Snidget;

class Container
{
    protected array $pool = [];

    public function actionCall(string $controllerName, string $methodName, array $params): mixed
    {
        $controller = new $controllerName();
        $actionRef = new \ReflectionMethod($controller, $methodName);

        $injectParams = [];
        foreach ($actionRef->getParameters() as $parameter) {
            $paramName = $parameter->getName();
            if (isset($params[$paramName])) {
                $injectParams[$paramName] = $params[$paramName];
                continue;
            }
            $typeName = $parameter->getType()->getName();
            if (class_exists($typeName)) {
                $injectParams[$paramName] = $this->get($typeName);
                continue;
            }
            $message = sprintf('Нет удалось разрешить параметр %s контроллера %s', $paramName, $controllerName);
            throw new \LogicException($message);
        }

        return $controller->{$methodName}(...$injectParams);
    }

    public function get(string $className)
    {
        $constructorRef = (new \ReflectionClass($className))->getConstructor();

        $injectParams = [];

        if ($constructorRef) {
            foreach ($constructorRef->getParameters() as $param) {
                $paramName = $param->getName();
                $typeName = $param->getType()->getName();
                if (class_exists($typeName)) {
                    $injectParams[$paramName] = $this->get($typeName);
                    continue;
                }
                $message = sprintf('Нет удалось разрешить параметр %s класса %s', $paramName, $className);
                throw new \LogicException($message);
            }
        }

        if (isset($this->pool[$className])) {
            return $this->pool[$className];
        }
        $this->pool[$className] = new $className(...$injectParams);
        return $this->pool[$className];
    }
}