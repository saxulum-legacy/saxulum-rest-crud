<?php

namespace Saxulum\RestCrud\Controller;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use Knp\Component\Pager\Pagination\AbstractPagination;
use Knp\Component\Pager\PaginatorInterface;
use Saxulum\RestCrud\Exception\ServiceNotFoundException;
use Saxulum\RestCrud\Repository\QueryBuilderForFilterFormInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\SecurityContextInterface;

trait RestCrudTrait
{
    /**
     * @param Request $request
     * @param array $additionalResponseVars
     * @return array
     * @throws \Exception
     */
    public function restCrudObjectList(Request $request, array $additionalResponseVars = array())
    {
        if($request->getMethod() !== 'GET') {
            throw new MethodNotAllowedHttpException('Only GET is allowed!');
        }

        $role = $this->restCrudListRole();
        if (!$this->restCrudIsGranted($role)) {
            throw new AccessDeniedException(sprintf('You need the permission to list entities, role: %s!', $role));
        }

        $form = $this->restCrudListForm($request);
        if (null !== $form) {
            $form->handleRequest($request);
            $formData = $form->getData();
        } else {
            $formData = array();
        }

        $formData = $this->restCrudListFormDataEnrich($request, $formData);

        $repo = $this->restCrudRepositoryForClass($this->restCrudObjectClass());
        if (!$repo instanceof QueryBuilderForFilterFormInterface) {
            throw new \Exception(sprintf('A repo used for crudListObjects needs to implement: %s', QueryBuilderForFilterFormInterface::interfacename));
        }

        $qb = $repo->getQueryBuilderForFilterForm($formData);

        $pagination = $this->restCrudPaginate($qb, $request);
        $page = $pagination->getCurrentPageNumber();
        $itemCountPerPage = $pagination->getItemNumberPerPage();
        $itemCount = $pagination->getTotalItemCount();
        $pageCount = ceil($itemCount / $itemCountPerPage);

        return array_replace_recursive(array(
            'items' => $pagination->getItems(),
            '_links' => array(
                'first' => $this->restCrudGenerateListUrl($request, array('page' => 1)),
                'prev' => $page > 1 ? $this->restCrudGenerateListUrl($request, array('page' => $page - 1)) : null,
                'next' => $page < $pageCount ? $this->restCrudGenerateListUrl($request, array('page' => $page + 1)) : null,
                'last' => $this->restCrudGenerateListUrl($request, array('page' => $pageCount)),
            ),
            '_metadata' => array(
                'itemCount' => $itemCount,
                'itemPerPage' => $itemCountPerPage,
                'page' => $page,
                'pageCount' => $pageCount
            ),
        ), $additionalResponseVars);
    }

    public function restCrudObjectCreate(Request $request)
    {

    }

    public function restCrudObjectRead(Request $request, $id)
    {

    }

    public function restCrudObjectUpdate(Request $request, $id)
    {

    }

    public function restCrudObjectPartialUpdate(Request $request, $id)
    {

    }

    public function restCrudObjectDelete(Request $request, $id)
    {

    }

    /**
     * @return string
     */
    protected function restCrudListRole()
    {
        return strtoupper(sprintf($this->restCrudRolePattern(), $this->restCrudName(), 'list'));
    }

    /**
     * @return string
     */
    protected function restCrudListRoute()
    {
        return strtolower(sprintf($this->restCrudRoutePattern(), $this->restCrudName(), 'list'));
    }

    /**
     * @param Request $request
     *
     * @return FormInterface|null
     */
    protected function restCrudListForm(Request $request)
    {
        if (null === $formType = $this->restCrudListFormType($request)) {
            return null;
        }

        return $this->restCrudForm($formType, array());
    }

    /**
     * @param Request $request
     *
     * @return FormTypeInterface|null
     */
    protected function restCrudListFormType(Request $request)
    {
        return null;
    }

    /**
     * @param Request $request
     * @param array   $formData
     *
     * @return array
     */
    protected function restCrudListFormDataEnrich(Request $request, array $formData)
    {
        return $formData;
    }

    /**
     * @param Request $request
     * @param array $replace
     *
     * @return string
     */
    protected function restCrudGenerateListUrl(Request $request, array $replace = array())
    {
        return $this->restCrudGenerateRoute($this->restCrudListRoute(), array_replace_recursive($request->query->all(), $replace));
    }

    /**
     * @return string
     */
    protected function restCrudRolePattern()
    {
        return 'role_%s_%s';
    }

    /**
     * @return string
     */
    protected function restCrudRoutePattern()
    {
        return 'api_%s_%s';
    }

    /**
     * @param mixed $attributes
     * @param mixed $object
     *
     * @return bool
     *
     * @throws \Exception
     */
    protected function restCrudIsGranted($attributes, $object = null)
    {
        try {
            return $this->restCrudAuthorizationChecker()->isGranted($attributes, $object);
        } catch (ServiceNotFoundException $e) {
            return $this->restCrudSecurity()->isGranted($attributes, $object);
        }
    }

    /**
     * @param FormTypeInterface $type
     * @param mixed             $data
     * @param array             $options
     *
     * @return FormInterface
     */
    protected function restCrudForm(FormTypeInterface $type, $data = null, array $options = array())
    {
        return $this->restCrudFormFactory()->create($type, $data, $options);
    }

    /**
     * @param string $class
     *
     * @return ObjectManager
     *
     * @throws \Exception
     */
    protected function restCrudManagerForClass($class)
    {
        $om = $this->restCrudDoctrine()->getManagerForClass($class);

        if (null === $om) {
            throw new \Exception(sprintf('There is no object manager for class: %s', $class));
        }

        return $om;
    }

    /**
     * @param string $class
     *
     * @return ObjectRepository
     */
    protected function restCrudRepositoryForClass($class)
    {
        return $this->restCrudManagerForClass($class)->getRepository($class);
    }

    /**
     * @param object  $target
     * @param Request $request
     *
     * @return AbstractPagination
     */
    protected function restCrudPaginate($target, Request $request)
    {
        return $this->restCrudPaginator()->paginate(
            $target,
            $request->query->get('page', 1),
            $request->query->get('perPage', $this->restCrudListPerPage())
        );
    }

    /**
     * @param string $name
     * @param array  $parameters
     *
     * @return string
     */
    protected function restCrudGenerateRoute($name, array $parameters = array())
    {
        return $this->restCrudUrlGenerator()->generate($name, $parameters, UrlGeneratorInterface::ABSOLUTE_URL);
    }

    /**
     * @return int
     */
    protected function restCrudListPerPage()
    {
        return 10;
    }

    /**
     * @return string
     */
    abstract protected function restCrudName();

    /**
     * @return string
     */
    abstract protected function restCrudObjectClass();

    /**
     * @return AuthorizationCheckerInterface
     *
     * @throws ServiceNotFoundException
     */
    protected function restCrudAuthorizationChecker()
    {
        throw new ServiceNotFoundException(sprintf(
            'For actions using authorization checker you need: %s',
            'Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface'
        ));
    }

    /**
     * @return SecurityContextInterface
     *
     * @throws ServiceNotFoundException
     */
    protected function restCrudSecurity()
    {
        throw new ServiceNotFoundException(sprintf(
            'For actions using security you need: %s',
            'Symfony\Component\Security\Core\SecurityContextInterface'
        ));
    }

    /**
     * @return FormFactoryInterface
     *
     * @throws ServiceNotFoundException
     */
    protected function restCrudFormFactory()
    {
        throw new ServiceNotFoundException(sprintf(
            'For actions using form you need: %s',
            'Symfony\Component\Form\FormFactoryInterface'
        ));
    }

    /**
     * @return ManagerRegistry
     *
     * @throws ServiceNotFoundException
     */
    protected function restCrudDoctrine()
    {
        throw new ServiceNotFoundException(sprintf(
            'For actions using doctrine you need: %s',
            'Doctrine\Common\Persistence\ManagerRegistry'
        ));
    }

    /**
     * @return PaginatorInterface
     *
     * @throws ServiceNotFoundException
     */
    protected function restCrudPaginator()
    {
        throw new ServiceNotFoundException(sprintf(
            'For actions using pagination you need: %s',
            'Saxulum\Crud\Pagination\PaginatorInterface'
        ));
    }

    /**
     * @return UrlGeneratorInterface
     *
     * @throws ServiceNotFoundException
     */
    protected function restCrudUrlGenerator()
    {
        throw new ServiceNotFoundException(sprintf(
            'For actions using url generation you need: %s',
            'Symfony\Component\Routing\Generator\UrlGeneratorInterface'
        ));
    }
}