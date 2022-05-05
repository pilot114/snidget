<?php

namespace Wshell\Snidget;

class Container
{
    public function controllerCall(string $controllerName, string $methodName, array $params): mixed
    {
        $controller = new $controllerName();

        // TODO: route params inject ?
//        $actionRef = new \ReflectionMethod($controller, $methodName);
//        foreach ($actionRef->getParameters() as $parameter) {
//            dump($parameter->getName());
//            dump($parameter->getType()->getName());
//            die();
//        }

        return $controller->{$methodName}(...$params);
    }
}