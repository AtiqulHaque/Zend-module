<?php
/**
 * Emails Controller
 *
 * @category        Controller
 * @package         BlogUser
 * @author          Md.Atiqul haque<md_atiqulhaque@yahoo.com>
 * @author          Md. Nuruzzaman Bappi <bappi.cse562@gmail.com>
 * @copyright       Copyright (c) 2013 Nokkhotro Blog. http://www.nokkhotroblog.com
 */
namespace BlogUser\Controller;

use BlogUser\Model\Entity\Email;
use NBlog\View\Helper\Profile;
use Zend\View\Model\ViewModel;
use Zend\Json\Json;

class EmailsController extends UserBaseController
{
    protected $emailModel;
    protected $emailStatusModel;
    protected $menuItem = 'emails';

    public function newMailAction()
    {
        $userDetail = $this->getUserDetail();
        if (empty($userDetail)) {
            return $this->redirectForFailure('blog', $this->translate('Unauthorized access. Please login to access.'));
        }
        $this->initialize(null, $userDetail);

        return new ViewModel;
    }

    public function composeAction()
    {
        $userDetail = $this->getUserDetail();
        if (empty($userDetail)) {
            return $this->redirectForFailure('blog', $this->translate('Unauthorized access. Please login to access.'));
        }
        $this->menuItem = 'chat';
        $emailForm = new \BlogUser\Form\Email(array(
            'translator' => $this->getTranslatorHelper()
        ));
        $request = $this->getRequest();
        if ($request->isPost()) {
            $emailEntity = new Email($this->getServiceLocator());
            $emailForm->setInputFilter($emailEntity->getInputFilter());
            $emailForm->setData($request->getPost());

            if ($emailForm->isValid()) {
                $userData = $emailForm->getData();
                $explodeData = explode(' ( ', $userData['to']);
                if (count($explodeData) == 1) {
                    $email = $explodeData;
                } else {
                    $email = explode(' )', $explodeData[1]);
                }

                $recipient_id = $this->getUserModel()->getUserIdByUserEmail(strip_tags($email[0]));

                if (empty($recipient_id)) {
                    return $this->redirectForFailure('compose-email', $this->translate('Email address is not valid.'));
                }

                $data = array_merge($emailForm->getData(), array(
                    'sender_id' => $this->getSessionContainer()->offsetGet('user_id'),
                    'recipient_id' => $recipient_id,
                    'status' => !empty($userData['draft'])
                ));

                if ($this->getEmailModel()->save($data)) {
                    return $this->redirectForSuccess('compose-email', $this->translate('Message has been send successfully'));
                } else {
                    return $this->redirectForFailure('compose-email', $this->translate('Something went wrong. Please try again.'));
                }
            } else {
                $this->setFailureMessage($this->translate('Please check the following errors.'));
            }
        }
        $this->initialize(null, $userDetail);
        $this->initializeCountLayout($userDetail);

        return new ViewModel(array(
            'form' => $emailForm
        ));
    }

    public function searchEmailAction()
    {
        $request = $this->getRequest();
        if ($request->isXmlHttpRequest()) {
            $params = $request->getPost();
            $result = $this->getUserModel()->searchEmail($this->getSessionContainer()->offsetGet('user_id'), $params['term']);

            $completeValue = array();
            foreach ($result AS $row) {
                $profile = new Profile();
                $name = $profile->getDisplayName($row);
                $completeValue[] = $name . ' ( ' . $row['email'] . ' )';
            }
            return $this->getResponse()->setContent(Json::encode($completeValue, true));
        } else {
            return $this->redirectForFailure('emails', $this->translate('Direct Access is Denied.'));
        }
    }

    public function specificEmailAction()
    {
        $userDetail = $this->getUserDetail();

        if (empty($userDetail)) {
            return $this->redirectForFailure('blog', $this->translate('Unauthorized access. Please login to access.'));
        }

        $this->menuItem = 'chat';
        $status = $this->getEmailStatusModel()->getStatusValue($this->params()->fromRoute('status'));

        $this->initialize(null, $userDetail);
        $this->initializeCountLayout($userDetail);

        return new ViewModel(array(
            'header' => $this->getEmailStatusModel()->getEmailHeaderValue($status),
            'summeryEmail' => $this->getEmailModel()->getSummeryEmail($userDetail['user_id'], $status)
        ));
    }

    public function getEmailAction()
    {
        $request = $this->getRequest();
        if ($request->isXmlHttpRequest()) {
            $params = $request->getPost();
            $arrResult = $this->getEmailModel()->getEmailDetails($params['emailId']);
            return $this->getResponse()->setContent(Json::encode(array('success' => 1, 'result' => $arrResult), true));
        } else {
            exit($this->translate('Direct Access is Denied.'));
        }
    }

    public function deleteEmailAction()
    {
        $request = $this->getRequest();
        if ($request->isXmlHttpRequest()) {
            $params = $request->getPost();
            $arrResult = $this->getEmailModel()->deleteEmails(array('is_deleted' => 1), $params['emailId']);
            return $this->getResponse()->setContent(Json::encode(array('success' => 1, 'result' => $arrResult), true));
        } else {
            exit($this->translate('Direct Access is Denied.'));
        }
    }

    public function draftEmailAction()
    {
        $request = $this->getRequest();
        if ($request->isXmlHttpRequest()) {
            $params = $request->getPost();
            $arrResult = $this->getEmailModel()->deleteEmails(array('is_deleted' => 1), $params['emailId']);
            return $this->getResponse()->setContent(Json::encode(array('success' => 1, 'result' => $arrResult), true));
        } else {
            exit($this->translate('Direct Access is Denied.'));
        }
    }

    /**
     * @return \BlogUser\Model\Email
     */
    private function getEmailModel()
    {
        isset($this->emailModel) || $this->emailModel = $this->getServiceLocator()->get('BlogUser\Model\Email');
        return $this->emailModel;
    }

    /**
     * @return \NBlog\Model\EmailStatus
     */
    private function getEmailStatusModel()
    {
        isset($this->emailStatusModel) || $this->emailStatusModel = $this->getServiceLocator()->get('NBlog\Model\EmailStatus');
        return $this->emailStatusModel;
    }

    private function initializeCountLayout($userDetail)
    {
        $this->layout()->setVariables(array(
            'inboxEmailCount' => $this->getEmailModel()->getInboxEmailCount($userDetail['user_id']),
            'sendEmailCount' => $this->getEmailModel()->getSendEmailCount($userDetail['user_id']),
            'trashEmailCount' => $this->getEmailModel()->getTrashEmailCount($userDetail['user_id']),
            'draftEmailCount' => $this->getEmailModel()->getDraftEmailCount($userDetail['user_id'])
        ));
    }
}