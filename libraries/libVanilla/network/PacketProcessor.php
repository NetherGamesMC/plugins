<?php
/**
 *   _ _ _ __      __         _ _ _
 *  | (_) |\ \    / /        (_) | |
 *  | |_| |_\ \  / /_ _ _ __  _| | | __ _
 *  | | | '_ \ \/ / _` | '_ \| | | |/ _` |
 *  | | | |_) \  / (_| | | | | | | | (_| |
 *  |_|_|_.__/ \/ \__,_|_| |_|_|_|_|\__,_|
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

namespace libVanilla\network;

use Closure;
use Exception;
use pocketmine\event\EventPriority;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\network\mcpe\protocol\Packet;
use pocketmine\network\mcpe\protocol\ServerboundPacket;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\ObjectSet;
use pocketmine\utils\SingletonTrait;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;

final class PacketProcessor
{
    use SingletonTrait;


    protected bool $registered = false;
    /** @var array<string, ObjectSet<Closure>> */
    protected array $ingressHandlers = [];
    /** @var array<string, ObjectSet<Closure>> */
    protected array $egressHandlers = [];

    /**
     * @param PacketHandler $handler
     * @param PluginBase $plugin
     * @throws Exception
     *
     * Disclaimer: There are extraneous variables for reflection types because of PHPStan's
     * particularity about return values.
     */
    public function registerHandler(PacketHandler $handler, PluginBase $plugin): void
    {
        if (!$this->registered) {
            $this->registerListeners($plugin);
        }
        $listenerClass = new ReflectionClass(get_class($handler));
        foreach ($listenerClass->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if (count($method->getParameters()) !== 2) {
                continue;
            }
            $closure = $method->getClosure($handler);
            $parameters = $method->getParameters();

            /** @var ReflectionNamedType $sessionParameterType */
            $sessionParameterType = $parameters[0]->getType();
            if ($sessionParameterType->getName() !== NetworkSession::class) {
                continue;
            }

            /** @var ReflectionNamedType $packetParameterType */
            $packetParameterType = $parameters[1]->getType();
            $packet = new ReflectionClass($packetParameterType->getName());
            if (!$packet->implementsInterface(Packet::class)) {
                continue;
            }

            /** @var ReflectionNamedType|null $returnType */
            $returnType = $method->getReturnType();
            if ($returnType === null || $returnType->getName() !== "bool") {
                throw new Exception("Expected \"bool\" return type from method {$method->getName()}. Received \"$returnType\"");
            }

            if ($packet->implementsInterface(ServerboundPacket::class)) {
                $this->registerIngress($closure);
            } elseif ($packet->implementsInterface(ClientboundPacket::class)) {
                $this->registerEgress($closure);
            }
        }
    }

    /**
     * @throws ReflectionException
     */
    private function registerListeners(PluginBase $plugin): void
    {
        if ($this->registered) {
            return;
        }
        $this->registered = true;
        $pluginManager = $plugin->getServer()->getPluginManager();
        $pluginManager->registerEvent(DataPacketReceiveEvent::class, function (DataPacketReceiveEvent $event): void {
            if ($this->hasIngressHandlers($event->getPacket())) {
                $packet = $event->getPacket();
                if ($this->handleIngress($event->getOrigin(), $packet)) {
                    $event->cancel();
                }
            }
        }, EventPriority::NORMAL, $plugin, false);
        $pluginManager->registerEvent(DataPacketSendEvent::class, function (DataPacketSendEvent $event): void {
            foreach ($event->getPackets() as $packet) {
                if ($this->hasEgressHandlers($packet)) {
                    $targets = $event->getTargets();
                    foreach ($targets as $key => $target) {
                        if ($this->handleEgress($target, $packet)) {
                            /*
                             * TODO: We would want to modify the target list for
                             * each packet separately, but that'd mean we'd have
                             * increased bandwidth when modified.
                             *
                             * As we don't use this for any feature at the moment,
                             * we will culminate solutions for this before making
                             * rash decisions that could negatively impact the network
                             */
                            // unset($targets[$key]);
                        }
                    }
                }
            }
        }, EventPriority::NORMAL, $plugin, false);
    }

    protected function hasIngressHandlers(ServerboundPacket $packet): bool
    {
        return isset($this->ingressHandlers[$packet->getName()]);
    }

    /**
     * @param NetworkSession $origin
     * @param ServerboundPacket $packet
     * @return bool - Returns true if a handler has successfully handled the packet
     */
    protected function handleIngress(NetworkSession $origin, ServerboundPacket $packet): bool
    {
        $handlers = $this->ingressHandlers[$packet->getName()];
        foreach ($handlers as $handler) {
            if (($handler)($origin, $packet)) {
                return true;
            }
        }
        return false;
    }

    protected function hasEgressHandlers(ClientboundPacket $packet): bool
    {
        return isset($this->egressHandlers[$packet->getName()]);
    }

    /**
     * @param NetworkSession $target
     * @param ClientboundPacket $packet
     * @return bool - Returns true if the handler wants to prevent the packet from being sent to the client
     */
    protected function handleEgress(NetworkSession $target, ClientboundPacket $packet): bool
    {
        $handlers = $this->egressHandlers[$packet->getName()];
        foreach ($handlers as $handler) {
            if (($handler)($target, $packet)) {
                return true;
            }
        }
        return false;
    }

    public function registerIngress(Closure $closure): void
    {
        // attempt to validate the closure before registering it to the handlers
        self::validateHandlerSignature($closure, static function (NetworkSession $origin, ServerboundPacket $packet): bool {
            return false;
        });
        ($this->ingressHandlers[self::parsePacketFromClosure($closure)] ??= new ObjectSet())->add($closure);
    }

    /**
     * @param Closure $handler
     * @param Closure $validation
     *
     * @throws Exception
     * @see \pocketmine\utils\Utils::validateCallableSignature() - Allows for flexibility in packet class parameter
     *
     * Disclaimer: There are extraneous variables for reflection types because of PHPStan's
     * particularity about return values.
     */
    protected static function validateHandlerSignature(Closure $handler, Closure $validation): void
    {
        $reflectedHandler = new ReflectionFunction($handler);
        $reflectedValidation = new ReflectionFunction($validation);

        /** @var ReflectionNamedType|null $reflectedHandlerReturnType */
        $reflectedHandlerReturnType = $reflectedHandler->getReturnType();
        /** @var ReflectionNamedType|null $reflectedValidationReturnType */
        $reflectedValidationReturnType = $reflectedValidation->getReturnType();

        if ($reflectedHandlerReturnType === null || ($reflectedHandlerReturnType->getName() !== $reflectedValidationReturnType->getName())) {
            throw new Exception("Return type of passed handler ({$reflectedHandler->getReturnType()}) does not match verification handler ({$reflectedValidation->getReturnType()})");
        }
        if (($handlerParameterCount = count($reflectedHandler->getParameters())) !== ($validationParameterCount = count($reflectedValidation->getParameters()))) {
            throw new Exception("Parameter count of passed handler ($handlerParameterCount) does not match verification handler ($validationParameterCount)");
        }
        $handlerParameters = $reflectedHandler->getParameters();

        $getClass = static function (ReflectionParameter $parameter): ReflectionClass {
            /** @var ReflectionNamedType $parameterType */
            $parameterType = $parameter->getType();
            return new ReflectionClass($parameterType->getName());
        };
        foreach ($reflectedValidation->getParameters() as $key => $verificationParameter) {
            $handlerParameter = $handlerParameters[$key];

            $validationParameterClass = $getClass($verificationParameter);
            $handlerParameterClass = $getClass($handlerParameter);
            if ($validationParameterClass->isInterface()) {
                if (!($handlerParameterClass->implementsInterface($validationParameterClass))) {
                    throw new Exception("Handler parameter ({$handlerParameter->getName()}) was expected to inherit interface of {$validationParameterClass->getName()}. Received interfaces (" . implode(",", $handlerParameterClass->getInterfaceNames()) . ")");
                }
            } elseif (!($handlerParameterClass->getName() === $validationParameterClass->getName())) {
                throw new Exception("Handler parameter ({$handlerParameter->getName()}) was expected to be class of {$validationParameterClass->getName()}. Received {$handlerParameterClass->getName()}");
            }
        }
    }

    /**
     * @throws Exception
     */
    protected static function parsePacketFromClosure(Closure $closure): string
    {
        $reflectedClosure = new ReflectionFunction($closure);
        foreach ($reflectedClosure->getParameters() as $parameter) {
            /** @var ReflectionNamedType $parameterType */
            $parameterType = $parameter->getType();
            $parameterClass = new ReflectionClass($parameterType->getName());
            if ($parameterClass->isSubclassOf(Packet::class)) {
                return $parameterClass->getShortName();
            }
        }
        throw new Exception("Unable to locate packet in closure");
    }

    public function registerEgress(Closure $closure): void
    {
        // attempt to validate the closure before registering it to the handlers
        self::validateHandlerSignature($closure, static function (NetworkSession $target, ClientboundPacket $packet): bool {
            return false;
        });
        ($this->egressHandlers[self::parsePacketFromClosure($closure)] ??= new ObjectSet())->add($closure);
    }

}