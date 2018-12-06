<?php
namespace MyskillsBundle\Service;

use JMS\Serializer\SerializationContext;
use JMS\Serializer\Serializer;

/**
 * Class SerializerService
 */
class SerializerService
{
    /**
     * @var Serializer $serializerProvider
     */
    private $serializer;

    /**
     * @var SerializationContext
     */
    private $serializerContext;

    /**
     * SerializerService constructor
     * 
     * @param Serializer $serializer
     * @param SerializationContext $serializerContext
     */
    public function __construct(Serializer $serializer, SerializationContext $serializerContext)
    {
        $this->serializer = $serializer;
        $this->serializerContext = $serializerContext;
        $this->serializerContext->enableMaxDepthChecks();
        $this->serializerContext->setSerializeNull(true);
    }

    /**
     * Serialize data
     * 
     * @param $data
     * @param array $groups
     * @param string $format
     * @return mixed|string
     */
    public function serialize($data, $groups = null, $format = 'json')
    {
        $context = null;
        if ($groups != null) {
            $this->serializerContext->setGroups($groups);
            $context = $this->serializerContext;
        }

        return $this->serializer->serialize($data, $format, $context);
    }
}