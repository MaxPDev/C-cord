<?php

namespace App\Utils;

use JMS\Serializer\Construction\ObjectConstructorInterface;
use JMS\Serializer\DeserializationContext;
use JMS\Serializer\Metadata\ClassMetadata;
use JMS\Serializer\Visitor\DeserializationVisitorInterface;

class InitializedObjectConstructor implements ObjectConstructorInterface
{
    private $fallbackConstructor;

    /**
     * @param ObjectConstructorInterface $fallbackConstructorClassName Fallback object constructor
     */
    public function __construct($fallbackConstructorClassName)
    {
        $this->fallbackConstructor = new $fallbackConstructorClassName();
    }

    /**
     * {@inheritdoc}
     */
    public function construct(
        DeserializationVisitorInterface $visitor,
        ClassMetadata $metadata,
        $data,
        array $type,
        DeserializationContext $context
    ): ?object {
        if ($context->hasAttribute('deserialization-constructor-target') && 1 === $context->getDepth()) {
            return $context->getAttribute('deserialization-constructor-target');
        }

        return $this->fallbackConstructor->construct($visitor, $metadata, $data, $type, $context);
    }
}