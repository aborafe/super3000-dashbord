<?php

namespace App\Http\Controllers;

abstract class Controller
{
    /**
     * Invoke the given controller method while ignoring route locale
     * for actions that do not explicitly accept a $locale parameter.
     */
    public function callAction($method, $parameters)
    {
        if (array_key_exists('locale', $parameters) && ! $this->methodExpectsLocale($method)) {
            unset($parameters['locale']);
        }

        return $this->{$method}(...array_values($parameters));
    }

    protected function methodExpectsLocale(string $method): bool
    {
        if (! method_exists($this, $method)) {
            return false;
        }

        $reflection = new \ReflectionMethod($this, $method);

        foreach ($reflection->getParameters() as $parameter) {
            if ($parameter->getName() === 'locale') {
                return true;
            }
        }

        return false;
    }
}
