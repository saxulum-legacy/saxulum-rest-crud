<?php

namespace Saxulum\RestCrud\Controller;

use Doctrine\Common\Persistence\ManagerRegistry;
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
     * @var FormFactoryInterface
     */
    protected $formFactory;

    /**
     * @var PaginatorInterface
     */
    protected $paginator;

    /**
     * @var UrlGeneratorInterface
     */
    protected $urlGenerator;

    /**
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param ManagerRegistry               $doctrine
     * @param FormFactoryInterface          $formFactory
     * @param PaginatorInterface            $paginator
     * @param UrlGeneratorInterface         $urlGenerator
     */
    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        ManagerRegistry $doctrine,
        FormFactoryInterface $formFactory = null,
        PaginatorInterface $paginator = null,
        UrlGeneratorInterface $urlGenerator = null
    ) {
        $this->authorizationChecker = $authorizationChecker;
        $this->doctrine = $doctrine;
        $this->paginator = $paginator;
        $this->formFactory = $formFactory;
        $this->urlGenerator = $urlGenerator;
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

    /**
     * @return UrlGeneratorInterface
     */
    protected function restCrudUrlGenerator()
    {
        return $this->urlGenerator;
    }
}
