<?php
declare(strict_types=1);

namespace AutoShell;

use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;

class Reflector
{
    /**
     * @param class-string $class
     */
    public function getClass(string $class) : ReflectionClass
    {
        return new ReflectionClass($class);
    }

    public function getMethod(
        ReflectionClass $rc,
        string $method
    ) : ReflectionMethod
    {
        return $rc->getMethod($method);
    }

    public function getParameterType(ReflectionParameter $rp) : string
    {
        $name = $rp->getName();
        $type = $rp->getType();
        return ($type === null)
            ? 'mixed'
            : $type->getName(); // @phpstan-ignore-line
    }

    public function isOptionsClass(string $class) : bool
    {
        return is_a($class, Options::class, true);
    }

    public function getOptionsClass(ReflectionMethod $rm) : ?string
    {
        $parameters = $rm->getParameters();

        foreach ($parameters as $parameter) {
            $type = $this->getParameterType($parameter);
            if ($this->isOptionsClass($type)) {
                return $type;
            }
        }

        return null;
    }

    /**
     * @return array<string, Option>
     */
    public function getOptionAttributes(ReflectionMethod $rm) : array
    {
        /** @var class-string */
        $optionsClass = $this->getOptionsClass($rm);

        if (! $optionsClass) {
            return [];
        }

        $attributes = [];
        $properties = $this->getClass($optionsClass)->getProperties();

        foreach ($properties as $property) {
            foreach ($property->getAttributes() as $attribute) {
                if ($attribute->getName() === Option::class) {
                    /** @var Option */
                    $instance = $attribute->newInstance();
                    $attributes[$property->getName()] = $instance;
                }
            }
        }

        return $attributes;
    }

    /**
     * @return ReflectionParameter[]
     */
    public function getArgumentParameters(ReflectionMethod $rm) : array
    {
        $argumentParameters = [];
        $rps = $rm->getParameters();

        while (! empty($rps)) {
            $rp = array_shift($rps);
            if ($this->isOptionsClass($this->getParameterType($rp))) {
                break;
            }
        }

        foreach ($rps as $rp) {
            $argumentParameters[] = $rp;
        }

        return $argumentParameters;
    }

    public function getHelpAttribute(
        ReflectionClass|ReflectionMethod|ReflectionParameter $spec
    ) : ?Help
    {
        foreach ($spec->getAttributes() as $attribute) {
            if ($attribute->getName() === Help::class) {
                /** @var Help */
                return $attribute->newInstance();
            }
        }

        return null;
    }

    public function isCommandClass(string $class) : bool
    {
        if (
            ! class_exists($class)
            || interface_exists($class)
            || trait_exists($class)
            || is_a($class, Options::class, true)
        ) {
            return false;
        }

        return ! $this->getClass($class)->isAbstract();
    }
}
