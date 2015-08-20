<?php

namespace Saxulum\RestCrud\Request;

use JMS\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\Request;

class ContentToFormDataConverter
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
     * @param Request $request
     * @return Request
     */
    public function convert(Request &$request)
    {
        if(null === $contentType = $request->getContentType()) {
            throw new \InvalidArgumentException('Please define a content type!');
        }

        if('form' === $contentType) {
            return $request;
        }

        $formData =  $this->serializer->deserialize($request->getContent(), 'array', $contentType);

        $request = new Request(
            $request->query->all(),
            $formData,
            $request->attributes->all(),
            $request->cookies->all(),
            $request->files->all(),
            array_replace($request->server->all(), array(
                'CONTENT_TYPE' => 'application/x-www-form-urlencoded'
            )),
            http_build_query($formData)
        );

        return $request;
    }
}
