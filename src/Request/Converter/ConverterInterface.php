<?php

namespace Saxulum\RestCrud\Request\Converter;

interface ConverterInterface
{
    /**
     * @param string $content
     * @return array
     */
    public function convert($content);

    /**
     * @return string
     */
    public function contentType();
}
