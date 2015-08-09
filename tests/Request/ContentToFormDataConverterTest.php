<?php

namespace Saxulum\Tests\RestCrud\Request;

use JMS\Serializer\SerializerInterface;
use Saxulum\RestCrud\Request\ContentToFormDataConverter;
use Symfony\Component\HttpFoundation\Request;

class ContentToFormDataConverterTest extends \PHPUnit_Framework_TestCase
{
    public function testConvert()
    {
        $serializer = $this->getSerializer();
        $converter = new ContentToFormDataConverter($serializer);

        $formData = array(
            'field1' => 1,
            'field2' => 2,
        );

        $originalRequest = new Request(
            array(),
            array(),
            array(),
            array(),
            array(),
            array('CONTENT_TYPE' => 'application/json'),
            json_encode($formData)
        );

        $request = $converter->convert($originalRequest);

        $this->assertSame($originalRequest, $request);

        $this->assertEquals('application/x-www-form-urlencoded', $request->headers->get('content-type'));
        $this->assertEquals($formData, $request->request->all());
        $this->assertEmpty($request->getContent());
    }

    /**
     * @return SerializerInterface
     */
    protected function getSerializer()
    {
        $mock = $this->getMock('JMS\Serializer\SerializerInterface');
        $mock
            ->expects($this->any())
            ->method('deserialize')
            ->will($this->returnCallback(function ($data) {
                return json_decode($data, true);
            }))
        ;

        return $mock;
    }
}