<?php
/**
 *   _ _ _               _       _
 *  | (_) |             (_)     (_)
 *  | |_| |__  _ __ ___  _ _ __  _  __ _  __ _ _ __ ___   ___  ___
 *  | | | '_ \| '_ ` _ \| | '_ \| |/ _` |/ _` | '_ ` _ \ / _ \/ __|
 *  | | | |_) | | | | | | | | | | | (_| | (_| | | | | | |  __/\__ \
 *  |_|_|_.__/|_| |_| |_|_|_| |_|_|\__, |\__,_|_| |_| |_|\___||___/
 *                                  __/ |
 *                                 |___/
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * @author sylvrs
 *
 */
declare(strict_types=1);

namespace libminigames\settings;

use Closure;
use InvalidArgumentException;
use libforms\elements\Element;
use libminigames\Arena;
use libminigames\settings\components\Component;
use pocketmine\player\Player;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionProperty;
use ReflectionUnionType;
use RuntimeException;

class SettingsHolder
{
    /**
     * @param object $instance
     * @param SettingsDescription $description
     * @param Component $component
     * @param ReflectionProperty $property
     * @param array<Closure(mixed, mixed): void> $observers
     */
    public function __construct(
        private object              $instance,
        private SettingsDescription $description,
        private Component           $component,
        private ReflectionProperty  $property,
        private array               $observers = []
    )
    {
        $reflectedInstance = new ReflectionClass($instance);
        // If neither the class nor any parents has the settings trait, throw an exception
        if (!self::hasTraitRecursive($reflectedInstance, SettingsTrait::class)) {
            throw new InvalidArgumentException("Instance or any parents of the instance must have the SettingsTrait");
        }
    }

    /**
     * A recursive check that checks if a class or any of its parents has a given trait
     *
     * @param ReflectionClass<object> $class - The original class to check
     * @param class-string<object> $traitClass - The class name of the trait to check for
     * @param int $maxIterations - The maximum number of inheritance levels to check (e.g., child -> parent -> grandparent, etc.)
     * @return bool - Returns true if the class or any of its parents has the trait
     */
    private static function hasTraitRecursive(ReflectionClass $class, string $traitClass, int $maxIterations = 10): bool
    {
        if ($maxIterations <= 0) {
            throw new RuntimeException("Maximum number of iterations exceeded");
        }

        return match (true) {
            self::hasTrait($class, $traitClass) => true,
            $class->getParentClass() instanceof ReflectionClass => self::hasTraitRecursive($class->getParentClass(), $traitClass, $maxIterations - 1),
            default => false
        };
    }

    /**
     * Used to check if a class has a given trait
     *
     * @param ReflectionClass<object> $class - The class to check
     * @param class-string<object> $traitClass - The class name of the trait to check for
     * @return bool - Returns true if the class has the trait, false otherwise
     */
    private static function hasTrait(ReflectionClass $class, string $traitClass): bool
    {
        return count(array_filter($class->getTraits(), static fn(ReflectionClass $trait) => $trait->getName() === $traitClass)) > 0;
    }

    /**
     * @param Closure(mixed, mixed): void $observer
     * @return void
     */
    public function registerObserver(Closure $observer): void
    {
        $this->observers[] = $observer;
    }

    /**
     * This method is used to render a Form element for the given property.
     * To do so, the method uses these steps:
     * 1. Take the component and create a UI element from it using the description name & the property value
     * 2. Set a callback to automatically update the property value when the user submits the form
     * 3. Return the element
     *
     * @return Element
     */
    public function asElement(Arena $arena): Element
    {
        $currentValue = $this->property->getValue($this->instance);
        $element = $this->component->asElement($this->description->name, $currentValue);
        $element->setCallable(function (Player $player, mixed $data) use ($arena, $currentValue): void {
            if (!$arena->isWaiting()) {
                return;
            }

            // This allows elements like step-sliders to convert the data to the correct type
            $data = $this->component->processData($data);
            // Ensure that the data type matches that of the property
            self::checkType(property: $this->property, data: $data);
            $this->property->setValue($this->instance, $data);
            if ($data !== $currentValue) {
                // Attempt to execute observer if it exists
                foreach ($this->observers as $observer) {
                    $observer($currentValue, $data);
                }
            }
        });
        return $element;
    }

    /**
     * Checks if the data passed is of the correct type as determined by the property's type(s)
     *
     * @param ReflectionProperty $property
     * @param mixed $data
     * @return void
     */
    private static function checkType(ReflectionProperty $property, mixed $data): void
    {
        $type = $property->getType();
        if ($type === null) {
            // The property doesn't have an explicit type, so we can't check it
            return;
        }
        if (!$type->allowsNull() && $data === null) {
            throw new InvalidArgumentException("Property cannot be null");
        }
        $dataType = get_debug_type($data);
        if ($type instanceof ReflectionNamedType && $dataType !== $type->getName()) {
            throw new InvalidArgumentException("Data must be of type {$type->getName()}");
        }
        if ($type instanceof ReflectionUnionType && !in_array($dataType, ($types = array_map(fn(ReflectionNamedType $current) => $current->getName(), $type->getTypes())), true)) {
            throw new InvalidArgumentException("Data must be one of types: " . implode(", ", $types));
        }
        // TODO: PHP 8.1 supports ReflectionIntersectionType
    }

    /**
     * This method is used as a way to encode the property's name and value into a json-encoded string.
     *
     * @return array{0: string, 1: mixed}
     */
    public function asArray(): array
    {
        return [$this->property->getName(), $this->property->getValue($this->instance)];
    }
}