<?php

namespace Saxulum\RestCrud\Request;

use Saxulum\RestCrud\Request\Converter\ConverterInterface;
use Symfony\Component\HttpFoundation\Request;

class ContentToFormDataConverter
{
    /**
     * @var ConverterInterface[]
     */
    protected $converters = [];

    /**
     * @param ConverterInterface[] $converters
     */
    public function __construct(array $converters)
    {
        foreach ($converters as $i => $converter) {
            if (!$converter instanceof ConverterInterface) {
                $type = is_object($converter) ? get_class($converter) : gettype($converter);
                throw new \InvalidArgumentException(
                    sprintf(
                        'Only objects implementing "%s" are supported! "%s" given on index %s',
                        'Saxulum\RestCrud\Request\Converter\ConverterInterface',
                        $type,
                        $i
                    )
                );
            }
            $this->converters[$converter->contentType()] = $converter;
        }
    }

    /**
     * @param Request $request
     *
     * @return Request
     */
    public function convert(Request &$request)
    {
        $contentType = $request->headers->get('Content-Type');
        if (false !== $pos = strpos($contentType, ';')) {
            $contentType = substr($contentType, 0, $pos);
        }

        if (in_array($contentType, array('application/x-www-form-urlencoded', 'multipart/form-data'), true)) {
            return $request;
        }

        $formData = $this->getConverter($contentType)->convert($request->getContent());

        $request = new Request(
            $request->query->all(),
            $formData,
            $request->attributes->all(),
            $request->cookies->all(),
            $request->files->all(),
            array_replace($request->server->all(), array(
                'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
            )),
            http_build_query($formData)
        );

        return $request;
    }

    /**
     * @param string $contentType
     *
     * @return ConverterInterface
     *
     * @throws \Exception
     */
    protected function getConverter($contentType)
    {
        if (!isset($this->converters[$contentType])) {
            throw new \Exception(sprintf('There is no converter for content type: "%s', $contentType));
        }

        return $this->converters[$contentType];
    }
}
