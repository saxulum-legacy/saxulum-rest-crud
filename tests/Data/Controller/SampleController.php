<?php

namespace Saxulum\Tests\RestCrud\Data\Controller;

use Saxulum\RestCrud\Controller\AbstractRestCrudController;
use Saxulum\Tests\RestCrud\Data\Form\SampleListType;
use Saxulum\Tests\RestCrud\Data\Form\SampleType;
use Saxulum\Tests\RestCrud\Data\Model\Sample;
use Symfony\Component\HttpFoundation\Request;

class SampleController extends AbstractRestCrudController
{
    /**
     * @param Request $request
     *
     * @return SampleListType
     */
    protected function restCrudListFormType(Request $request)
    {
        return new SampleListType();
    }

    /**
     * @param Request $request
     * @param object  $object
     *
     * @return SampleType
     */
    protected function restCrudCreateFormType(Request $request, $object)
    {
        return new SampleType();
    }

    /**
     * @param Request $request
     * @param object  $object
     *
     * @return SampleType
     */
    protected function restCrudEditFormType(Request $request, $object)
    {
        return new SampleType();
    }

    /**
     * @return string
     */
    protected function restCrudName()
    {
        return 'sample';
    }

    /**
     * @return string
     */
    protected function restCrudObjectClass()
    {
        return Sample::classname;
    }
}
