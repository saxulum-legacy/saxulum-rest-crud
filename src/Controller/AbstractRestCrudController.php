<?php

namespace Saxulum\RestCrud\Controller;

use Doctrine\Common\Persistence\ManagerRegistry;
use JMS\Serializer\SerializerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

abstract class AbstractRestCrudController
{
    use RestCrudTrait;

    /**
     * @var AuthorizationCheckerInterface
     */
    protected $authorizationChecker;

    /**
     * @var ManagerRegistry
     */
    protected $doctrine;

    /**
     * @var UrlGeneratorInterface
     */
    protected $urlGenerator;

    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * @var FormFactoryInterface
     */
    protected $formFactory;

    /**
     * @var PaginatorInterface
     */
    protected $paginator;

    /**
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param ManagerRegistry               $doctrine
     * @param UrlGeneratorInterface         $urlGenerator
     * @param SerializerInterface           $serializer
     * @param FormFactoryInterface|null     $formFactory
     * @param PaginatorInterface|null       $paginator
     */
    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        ManagerRegistry $doctrine,
        UrlGeneratorInterface $urlGenerator,
        SerializerInterface $serializer,
        FormFactoryInterface $formFactory = null,
        PaginatorInterface $paginator = null
    ) {
        $this->authorizationChecker = $authorizationChecker;
        $this->doctrine = $doctrine;
        $this->urlGenerator = $urlGenerator;
        $this->serializer = $serializer;
        $this->paginator = $paginator;
        $this->formFactory = $formFactory;
    }

    /**
     * @return AuthorizationCheckerInterface
     */
    protected function restCrudAuthorizationChecker()
    {
        return $this->authorizationChecker;
    }

    /**
     * @return ManagerRegistry
     */
    protected function restCrudDoctrine()
    {
        return $this->doctrine;
    }

    /**
     * @return UrlGeneratorInterface
     */
    protected function restCrudUrlGenerator()
    {
        return $this->urlGenerator;
    }

    /**
     * @return SerializerInterface
     */
    protected function restCrudSerializer()
    {
        return $this->serializer;
    }

    /**
     * @return PaginatorInterface
     */
    protected function restCrudPaginator()
    {
        return $this->paginator;
    }

    /**
     * @return FormFactoryInterface
     */
    protected function restCrudFormFactory()
    {
        return $this->formFactory;
    }
}
