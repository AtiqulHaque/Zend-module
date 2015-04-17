<?php
/**
 * Base Controller
 *
 * Base controller for the Blog sub-module.
 *
 * @category        Controller
 * @package         Blog
 * @author          Md. Atiqul Haque <mailtoatiq@gmail.com>* @author          Md. Atiqul Haque <mailtoatiq@gmail.com>* @author          Md. Atiqul Haque <mailtoatiq@gmail.com>* @author          Md. Atiqul Haque <mailtoatiq@gmail.com>
 * @author          Md. Atiqul Haque <mailtoatiq@gmail.com>
 * @copyright       Copyright (c) 2013 Nokkhotro Blog. http://www.nokkhotroblog.com
 */
namespace Blog\Controller;

use NBlog\Controller\AbstractController;

abstract class BaseController extends AbstractController
{
    protected function initializeLayout($pageTitle = '')
    {
        parent::initializeLayout($pageTitle);
        $this->layout()->setVariables(array(
            'navCategories' => $this->getCategoryModel()->getAllForNavigation(),
        ));
    }

    /**
     * @return \NBlog\Model\Category
     */
    private function getCategoryModel()
    {
        return $this->getServiceLocator()->get('NBlog\Model\Category');
    }
}
