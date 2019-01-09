<?php

namespace Imanghafoori\Decorator;

use Illuminate\Support\Str;

class Decorator
{
    /**
     * All of the decorators for method calls.
     *
     * @var array
     */
    protected $globalDecorators = [];

    /**
     * All of the decorator names and definitions.
     *
     * @var array
     */
    protected $decorations = [];

    /**
     * Defines a new decorator with name.
     *
     * @param  string $name
     * @param  callable $callback
     * @return void
     */
    public function define($name, $callback)
    {
        $this->globalDecorators[$name] = $callback;
    }

    public function getGlobalDecorator($name)
    {
        return $this->globalDecorators[$name] ?? null;
    }

    public function getDecorationsFor($callback)
    {
        return $this->decorations[$callback] ?? [];
    }

    /**
     * Decorates a callable with a defined decorator name.
     *
     * @param  string $decorated
     * @param  string $decorator
     * @return void
     */
    public function decorate($decorated, $decorator)
    {
        $this->decorations[$decorated][] = $decorator;
    }

    /**
     * Calls a class@method with it's specified decorators.
     *
     * @param  string $callback
     * @param  array $parameters
     * @param  string|null $defaultMethod
     * @return mixed
     */
    public function call($callback, array $parameters = [], $defaultMethod = null)
    {
        if (is_array($callback)) {
            $callback = $this->normalizeMethod($callback);
        }

        $decorations = $this->getDecorationsFor($callback);

        $callback = $this->getDecoratedCall($callback, $decorations);

        return app()->call($callback, $parameters, $defaultMethod);
    }

    public function unDecorate($decorated, $decorator = null)
    {
        if (is_null($decorator)) {
            unset($this->decorations[$decorated]);
        } else {
            unset($this->decorations[$decorated][$decorator]);
        }
    }

    private function normalizeMethod($callback)
    {
        $class = is_string($callback[0]) ? $callback[0] : get_class($callback[0]);

        return "{$class}@{$callback[1]}";
    }

    /**
     * @param $callback
     * @param $decorations
     * @return mixed
     */
    public function getDecoratedCall($callback, $decorations): callable
    {
        foreach ($decorations as $decorator) {
            if (is_string($decorator) and ! Str::contains($decorator, '@')) {
                $decorator = $this->globalDecorators[$decorator];
            }

            $callback = app()->call($decorator, [$callback]);
        }

        return $callback;
    }
}
