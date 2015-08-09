<?php

namespace Saxulum\Tests\RestCrud\Controller;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use JMS\Serializer\SerializerInterface;
use Knp\Component\Pager\Pagination\AbstractPagination;
use Knp\Component\Pager\PaginatorInterface;
use Saxulum\RestCrud\Repository\QueryBuilderForFilterFormInterface;
use Saxulum\Tests\RestCrud\Data\Controller\SampleController;
use Saxulum\Tests\RestCrud\Data\Model\Sample;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class RestCrudTraitTest extends \PHPUnit_Framework_TestCase
{
    public function testListObject()
    {
        $request = new Request();
        $request->setSession(new Session(new MockArraySessionStorage()));
        $request->query->set('sample_list', array('title' => 't'));

        $page = 3;
        $itemPerPage = 10;
        $itemCount = 45;
        $pageCount = ceil($itemCount/$itemPerPage);

        $controller = new SampleController(
            $this->getAuthorizationChecker('ROLE_SAMPLE_LIST'),
            $this->getDoctrine(Sample::classname),
            $this->getUrlGenerator(),
            $this->getSerializer(),
            $this->getFormFactory(
                'Saxulum\Tests\RestCrud\Data\Form\SampleListType',
                array('title' => 't'),
                'query'
            ),
            $this->getPaginator('QueryBuilder', 1, 10, array(), $page, $itemPerPage, $itemCount)
        );

        $response = $controller->restCrudObjectList($request);

        $data = json_decode($response->getContent(), true);

        $this->assertCount(10, $data['items']);

        $this->assertEquals('http://test.lo/api_sample_listsample_list%5Btitle%5D=t&page=1', $data['_links']['first']);
        $this->assertEquals('http://test.lo/api_sample_listsample_list%5Btitle%5D=t&page=2', $data['_links']['prev']);
        $this->assertEquals('http://test.lo/api_sample_listsample_list%5Btitle%5D=t&page=4', $data['_links']['next']);
        $this->assertEquals('http://test.lo/api_sample_listsample_list%5Btitle%5D=t&page=5', $data['_links']['last']);
        
        $this->assertEquals($itemCount, $data['_metadata']['itemCount']);
        $this->assertEquals($itemPerPage, $data['_metadata']['itemPerPage']);
        $this->assertEquals($page, $data['_metadata']['page']);
        $this->assertEquals($pageCount, $data['_metadata']['pageCount']);
    }

    /**
     * @return ManagerRegistry
     */
    protected function getDoctrine($expectedClass)
    {
        $managerRegistyMock = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $managerRegistyMock
            ->expects($this->any())
            ->method('getManagerForClass')
            ->will($this->returnCallback(function ($givenClass) use ($expectedClass) {
                $this->assertEquals($expectedClass, $givenClass);
                $objectManagerMock = $this->getMock('Doctrine\Common\Persistence\ObjectManager');
                $objectManagerMock
                    ->expects($this->any())
                    ->method('getRepository')
                    ->will($this->returnCallback(function () {
                        return $this->getRepository();
                    }))
                ;
                $objectManagerMock
                    ->expects($this->any())
                    ->method('persist')
                    ->will($this->returnCallback(function (Sample $model) {
                        $this->setModelId($model, 1);
                    }));
                $objectManagerMock
                    ->expects($this->any())
                    ->method('getClassMetadata')
                    ->will($this->returnCallback(function () {
                        return $this->getClassMetadata();
                    }));

                return $objectManagerMock;
            }))
        ;

        return $managerRegistyMock;
    }

    /**
     * @return QueryBuilderForFilterFormInterface
     */
    protected function getRepository()
    {
        $objectRepositoryMock = $this->getMock('Saxulum\RestCrud\Repository\QueryBuilderForFilterFormInterface');
        $objectRepositoryMock
            ->expects($this->any())
            ->method('find')
            ->will($this->returnCallback(function () {

                $reflectionClass = new \ReflectionClass(Sample::classname);
                $model = $reflectionClass->newInstanceWithoutConstructor();

                $reflectionProperty = $reflectionClass->getProperty('id');
                $reflectionProperty->setAccessible(true);
                $reflectionProperty->setValue($model, 1);
                $reflectionProperty->setAccessible(false);

                return $model;
            }))
        ;
        $objectRepositoryMock
            ->expects($this->any())
            ->method('getQueryBuilderForFilterForm')
            ->willReturn($this->getMock('QueryBuilder'))
        ;

        return $objectRepositoryMock;
    }

    /**
     * @return ClassMetadata
     */
    protected function getClassMetadata()
    {
        $objectRepositoryMock = $this->getMock('Doctrine\Common\Persistence\Mapping\ClassMetadata');
        $objectRepositoryMock
            ->expects($this->once())
            ->method('getIdentifier')
            ->willReturn(array('id'))
        ;

        return $objectRepositoryMock;
    }

    /**
     * @return PaginatorInterface
     */
    protected function getPaginator($expectedTarget, $expectedPage, $expectedLimit, $expectedOptions, $page, $itemsPerPage, $itemCount)
    {
        $mock = $this->getMock('Knp\Component\Pager\PaginatorInterface');

        $mock
            ->expects($this->any())
            ->method('paginate')
            ->will($this->returnCallback(function ($givenTarget, $givenPage, $givenLimit, $givenOptions) use ($expectedTarget, $expectedPage, $expectedLimit, $expectedOptions, $page, $itemsPerPage, $itemCount) {
                $this->assertInstanceOf($expectedTarget, $givenTarget);
                $this->assertEquals($expectedPage, $givenPage);
                $this->assertEquals($expectedLimit, $givenLimit);
                $this->assertEquals($expectedOptions, $givenOptions);

                return $this->getPagination($page, $itemsPerPage, $itemCount);
            }));

        return $mock;
    }

    /**
     * @param int $page
     * @param int $itemsPerPage,
     * @param int $itemCount
     *
     * @return AbstractPagination
     */
    protected function getPagination($page, $itemsPerPage, $itemCount)
    {
        if($page * $itemsPerPage - $itemsPerPage > $itemCount) {
            throw new \InvalidArgumentException('Testinput does not make any sense!');
        }

        $mock = $this->getMock('Knp\Component\Pager\Pagination\AbstractPagination');

        $mock
            ->expects($this->any())
            ->method('getCurrentPageNumber')
            ->willReturn($page)
        ;

        $mock
            ->expects($this->any())
            ->method('getItemNumberPerPage')
            ->willReturn($itemsPerPage)
        ;

        $mock
            ->expects($this->any())
            ->method('getTotalItemCount')
            ->willReturn($itemCount)
        ;

        $mock
            ->expects($this->any())
            ->method('getItems')
            ->will($this->returnCallback(function () use ($page, $itemsPerPage, $itemCount) {
                $items = array();

                // not the last page
                if($page * $itemsPerPage < $itemCount) {
                    for($i = 0; $i < $itemsPerPage; $i++) {
                        $items[] = new Sample();
                    }
                } else {
                    for($i = 0; $i < $page * $itemsPerPage - $itemCount; $i++) {
                        $items[] = new Sample();
                    }
                }

                return $items;
            }))
        ;

        return $mock;
    }

    /**
     * @param FormTypeInterface $expectedType
     * @param mixed             $expectedData
     * @param string            $requestProperty
     *
     * @return FormFactoryInterface
     */
    protected function getFormFactory($expectedType, $expectedData, $requestProperty = null)
    {
        $formFactoryMock = $this->getMock('Symfony\Component\Form\FormFactoryInterface');

        $formFactoryMock
            ->expects($this->any())
            ->method('create')
            ->will($this->returnCallback(function (AbstractType $givenType, $givenData) use ($expectedType, $expectedData, $requestProperty) {
                $this->assertInstanceOf($expectedType, $givenType);

                $formName = $givenType->getName();

                $formMock = $this->getMock('Symfony\Component\Form\FormInterface');
                $formMock
                    ->expects($this->any())
                    ->method('getData')
                    ->will($this->returnCallback(function() use(&$givenData) {
                        return $givenData;
                    }))
                ;

                $formMock
                    ->expects($this->any())
                    ->method('handleRequest')
                    ->will($this->returnCallback(function (Request $request) use (&$givenData, $expectedData, $requestProperty, $formName) {

                        if (null === $requestProperty) {
                            return $givenData;
                        }

                        /** @var array $requestData */
                        $requestData = $request->$requestProperty->get($formName);
                        $propertyAccessor = new PropertyAccessor();
                        $isObject = is_object($givenData);

                        foreach ($requestData as $property => $value) {
                            if (!$isObject) {
                                $property = '['.$property.']';
                            }
                            $propertyAccessor->setValue($givenData, $property, $value);
                        }
                    }))
                ;

                $formMock
                    ->expects($this->any())
                    ->method('isValid')
                    ->willReturn(true)
                ;

                $formMock
                    ->expects($this->any())
                    ->method('createView')
                    ->willReturn($this->getFormView())
                ;

                return $formMock;
            }))
        ;

        return $formFactoryMock;
    }

    /**
     * @return FormView
     */
    protected function getFormView()
    {
        return $this->getMock('Symfony\Component\Form\FormView');
    }

    /**
     * @return UrlGeneratorInterface
     */
    protected function getUrlGenerator()
    {
        $mock = $this->getMock('Symfony\Component\Routing\Generator\UrlGeneratorInterface');

        $mock
            ->expects($this->any())
            ->method('generate')
            ->will($this->returnCallback(function ($givenName, $givenParameters, $givenReferenceType) {
                $this->assertEquals(UrlGeneratorInterface::ABSOLUTE_URL, $givenReferenceType);

                return 'http://test.lo/' . $givenName . http_build_query($givenParameters);
            }))
        ;

        return $mock;
    }

    /**
     * @param string $expectedRole
     *
     * @return AuthorizationCheckerInterface
     */
    protected function getAuthorizationChecker($expectedRole)
    {
        $mock = $this->getMock('Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface');
        $mock
            ->expects($this->any())
            ->method('isGranted')
            ->will($this->returnCallback(function ($givenRole) use ($expectedRole) {
                return $givenRole === $expectedRole;
            }))
        ;

        return $mock;
    }

    /**
     * @return SerializerInterface
     */
    protected function getSerializer()
    {
        $mock = $this->getMock('JMS\Serializer\SerializerInterface');
        $mock
            ->expects($this->any())
            ->method('serialize')
            ->will($this->returnCallback(function ($data) {
                return json_encode($data);
            }))
        ;
        $mock
            ->expects($this->any())
            ->method('deserialize')
            ->will($this->returnCallback(function ($data) {
                return json_decode($data, true);
            }))
        ;

        return $mock;
    }

    /**
     * @param Sample $model
     * @param int    $id
     */
    protected function setModelId(Sample $model, $id)
    {
        $reflectionClass = new \ReflectionClass(Sample::classname);

        $reflectionProperty = $reflectionClass->getProperty('id');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($model, $id);
        $reflectionProperty->setAccessible(false);
    }
}
