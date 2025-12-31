<?php
namespace src\Core;

use Psr\Container\ContainerInterface as PsrContainerInterface;
use ReflectionClass;
use ReflectionException;

class Container implements PsrContainerInterface
{
    private $definitions = [];
    private $instances = [];
    private $factories = [];

    public function set(string $id, $value): void
    {
        $this->definitions[$id] = $value;
    }

    public function get(string $id)
    {
        // Если уже есть экземпляр, возвращаем его
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        // Если есть фабрика, вызываем ее
        if (isset($this->factories[$id])) {
            return $this->factories[$id]($this);
        }

        // Если есть определение, создаем объект
        if (isset($this->definitions[$id])) {
            $definition = $this->definitions[$id];

            // Если это замыкание, вызываем его
            if ($definition instanceof \Closure) {
                $instance = $definition($this);
            }
            // Если это имя класса, создаем экземпляр
            elseif (is_string($definition) && class_exists($definition)) {
                $instance = $this->resolve($definition);
            }
            // Если это готовый объект
            else {
                $instance = $definition;
            }

            // Сохраняем как синглтон, если не фабрика
            if (!isset($this->factories[$id])) {
                $this->instances[$id] = $instance;
            }

            return $instance;
        }

        // Пытаемся автоматически создать объект
        if (class_exists($id)) {
            $instance = $this->resolve($id);
            $this->instances[$id] = $instance;
            return $instance;
        }

        throw new ContainerException("Service '$id' not found");
    }

    public function has(string $id): bool
    {
        return isset($this->definitions[$id]) ||
            isset($this->instances[$id]) ||
            isset($this->factories[$id]) ||
            class_exists($id);
    }

    public function factory(string $id, callable $factory): void
    {
        $this->factories[$id] = $factory;
    }

    public function singleton(string $id, $value): void
    {
        $this->set($id, $value);
    }

    /**
     * Автоматическое создание объекта с внедрением зависимостей
     */
    private function resolve(string $className)
    {
        try {
            $reflection = new ReflectionClass($className);

            // Проверяем, можно ли создать экземпляр
            if (!$reflection->isInstantiable()) {
                throw new ContainerException("Class $className is not instantiable");
            }

            // Получаем конструктор
            $constructor = $reflection->getConstructor();

            // Если конструктора нет, просто создаем объект
            if ($constructor === null) {
                return new $className();
            }

            // Получаем параметры конструктора
            $parameters = $constructor->getParameters();
            $dependencies = $this->resolveDependencies($parameters);

            // Создаем объект с зависимостями
            return $reflection->newInstanceArgs($dependencies);

        } catch (ReflectionException $e) {
            throw new ContainerException("Cannot resolve $className: " . $e->getMessage());
        }
    }

    /**
     * Разрешение зависимостей конструктора
     */
    private function resolveDependencies(array $parameters): array
    {
        $dependencies = [];

        foreach ($parameters as $parameter) {
            // Получаем тип параметра
            $type = $parameter->getType();

            // Если тип не указан, проверяем значение по умолчанию
            if ($type === null) {
                if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                } else {
                    throw new ContainerException(
                        "Cannot resolve parameter \${$parameter->getName()} in {$parameter->getDeclaringClass()->getName()}"
                    );
                }
            } else {
                // Получаем имя типа
                $typeName = $type->getName();

                // Проверяем, можно ли разрешить через контейнер
                if (!$type->isBuiltin() && $this->has($typeName)) {
                    $dependencies[] = $this->get($typeName);
                } elseif (!$type->isBuiltin() && class_exists($typeName)) {
                    // Автоматически создаем зависимость
                    $dependencies[] = $this->resolve($typeName);
                } elseif ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                } elseif ($parameter->isOptional()) {
                    $dependencies[] = null;
                } else {
                    throw new ContainerException(
                        "Cannot resolve dependency {$typeName} for parameter \${$parameter->getName()}"
                    );
                }
            }
        }

        return $dependencies;
    }

    /**
     * Вызов метода с внедрением зависимостей
     */
    public function call(callable $callable, array $parameters = [])
    {
        if (is_array($callable)) {
            $reflection = new \ReflectionMethod($callable[0], $callable[1]);
        } elseif ($callable instanceof \Closure) {
            $reflection = new \ReflectionFunction($callable);
        } else {
            $reflection = new \ReflectionMethod($callable, '__invoke');
        }

        $dependencies = [];

        foreach ($reflection->getParameters() as $parameter) {
            $name = $parameter->getName();

            // Если параметр передан явно
            if (array_key_exists($name, $parameters)) {
                $dependencies[] = $parameters[$name];
                continue;
            }

            // Пытаемся разрешить через контейнер
            $type = $parameter->getType();

            if ($type && !$type->isBuiltin() && $this->has($type->getName())) {
                $dependencies[] = $this->get($type->getName());
            } elseif ($parameter->isDefaultValueAvailable()) {
                $dependencies[] = $parameter->getDefaultValue();
            } else {
                throw new ContainerException(
                    "Cannot resolve parameter \${$name} for callable"
                );
            }
        }

        return $reflection->invokeArgs($dependencies);
    }
}

class ContainerException extends \Exception {}