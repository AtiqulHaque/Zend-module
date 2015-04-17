<?php
/**
 * Pages Controller
 *
 * This is the controller which has home of the site.
 *
 * @category        Controller
 * @package         Blog
 * @author          Md. Nuruzzaman Bappi <bappi.cse562@gmail.com>
 * @author          Md. Atiqul Haque <mailtoatiq@gmail.com>
 * @copyright       Copyright (c) 2013 Nokkhotro Blog. http://www.nokkhotroblog.com
 */
namespace Blog\Controller;

use Blog\Form\Contact;
use NBlog\Utility\Captcha;
use NBlog\Utility\Notifier;
use Zend\View\Model\ViewModel;

class PagesController extends BaseController
{
    protected $contactReasonModel;
    protected $faqQuestionModel;
    protected $pageModel;

    public function indexAction()
    {
        $pageInfo = $this->getPageModel()->getPageInfoByPermalink($this->params()->fromRoute('permalink'));
        $this->initializeLayout($pageInfo['title']);
        return new ViewModel(array(
            'pagesInfo' => $pageInfo
        ));
    }

    public function showFaqCategoryAction()
    {
        $this->initializeLayout($this->translate('FAQ'));
        return new ViewModel(array(
            'categoryList' => $this->getFaqQuestionModel()->getCategoryWiseQuestionList(),
        ));
    }

    public function contactUsAction()
    {
        $reasons = $this->getContactReasonModel()->getAll();
        $contactForm = new Contact(array(
            'translator' => $this->getTranslatorHelper(),
            'reasons' => $reasons
        ));

        $request = $this->getRequest();
        if ($request->isPost()) {
            $contactEntity = new \Blog\Model\Entity\Contact($this->getServiceLocator());
            $contactForm->setInputFilter($contactEntity->getInputFilter());
            $contactForm->setData($request->getPost());

            if ($contactForm->isValid()) {
                if ($this->checkCaptcha()) {
                    $notifier = new Notifier();
                    $notifier->sendContactUsEmail(array_merge($contactForm->getData(), array('reasons' => $reasons)));
                    $this->UserInformer()->addMessage($this->translate('An email with your query has been sent to the administration.'));
                    $contactForm->clearForm();
                } else {
                    $this->setFailureMessage($this->translate('Captcha entry is not correct.'));
                }
            } else {
                $this->setFailureMessage($this->translate('Please check the following errors.'));
            }
        }

        $this->initializeLayout($this->translate('Contact Us'));
        return new ViewModel(array(
            'contactForm' => $contactForm,
            'pageNav' => 'contact-us'
        ));
    }

    protected function initializeLayout($pageTitle = '')
    {
        parent::initializeLayout($pageTitle);
        $this->layout()->setVariables(array(
            'disableBanner' => true,
            'disableFooterCategory' => true,
        ));
    }

    private function checkCaptcha()
    {
        $postData = $this->getRequest()->getPost();
        $captchaUtility = new Captcha();
        return $captchaUtility->validate($postData['recaptcha_challenge_field'], $postData['recaptcha_response_field']);
    }

    /**
     * @return \Blog\Model\ContactReason
     */
    private function getContactReasonModel()
    {
        isset($this->contactReasonModel) || $this->contactReasonModel = $this->getServiceLocator()->get('Blog\Model\ContactReason');
        return $this->contactReasonModel;
    }

    /**
     * @return \NBlog\Model\FaqQuestion
     */
    private function getFaqQuestionModel()
    {
        isset($this->faqQuestionModel) || $this->faqQuestionModel = $this->getServiceLocator()->get('NBlog\Model\FaqQuestion');
        return $this->faqQuestionModel;
    }

    /**
     * @return \NBlog\Model\Page
     */
    private function getPageModel()
    {
        isset($this->pageModel) || $this->pageModel = $this->getServiceLocator()->get('NBlog\Model\Page');
        return $this->pageModel;
    }
}
