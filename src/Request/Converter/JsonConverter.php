<?php

namespace Saxulum\RestCrud\Request\Converter;

use JMS\Serializer\SerializerInterface;

class JsonConverter implements ConverterInterface
{
    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * @param SerializerInterface $serializer
     */
    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * @param string $content
     *
     * @return array
     */
    public function convert($content)
    {
        return $this->serializer->deserialize($content, 'array', 'json');
    }

    /**
     * @return string
     */
    public function contentType()
    {
        return 'application/json';
    }
}
