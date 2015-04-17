<?php

namespace BlogUser\Model;

use NBlog\Model\EmailStatus;
use NBlog\Model\ServiceLocatorUserDB;

class Email extends ServiceLocatorUserDB
{
    /**
     * @var \BlogUser\Model\Dao\Email
     */
    protected $dao = null;
    protected $userHelperModel = null;

    public function getSummeryEmail($userId, $status)
    {
        if (empty($userId)) {
            return array();
        }

        return $this->getEmails(array(
            'user_id' => $userId,
            'status' => $status
        ));
    }

    public function getEmailDetails($emailId)
    {
        if (empty($emailId)) {
            return array();
        }
        $this->dao->modify(array('is_read' => 1), $emailId);
        return $this->getUserHelperModel()->getShortProfilesOfUsers($this->dao->getEmailDetails($emailId), array(
            'withProfile' => true,
            'userKey' => 'sender_id'
        ));
    }

    private function getEmails(array $options)
    {
        $options = $this->setCountOffset($options);
        return $this->getUserHelperModel()->getShortProfilesOfUsers($this->dao->getUserEmails($options), array(
            'withProfile' => true,
            'userKey' => 'sender_id'
        ));
    }

    public function getAdapter()
    {
        return $this->getDbAdapter($this->serviceManager);
    }

    public function getInboxEmailCount($userId)
    {
        $options = array_merge(array(
            'userId' => $userId,
            'status' => EmailStatus::INBOX,
            'NotRead' => EmailStatus::NOT_READ
        ));

        return $this->dao->getInboxEmailCount($options);
    }

    public function getSendEmailCount($userId)
    {
        $options = array_merge(array(
            'userId' => $userId,
            'status' => EmailStatus::SEND
        ));

        return $this->dao->getSendEmailCount($options);
    }

    public function getTrashEmailCount($userId)
    {
        $options = array_merge(array(
            'userId' => $userId,
            'status' => EmailStatus::TRASH
        ));

        return $this->dao->getTrashEmailCount($options);
    }

    public function getDraftEmailCount($userId)
    {
        $options = array_merge(array(
            'userId' => $userId,
            'status' => EmailStatus::DRAFT
        ));

        return $this->dao->getDraftEmailCount($options);
    }

    public function deleteEmails(array $data, $id)
    {
        return $this->dao->modify($data, $id);
    }

    /**
     * @return  \NBlog\Model\Helper\User
     */
    private function getUserHelperModel()
    {
        isset($this->userHelperModel) || $this->userHelperModel = $this->serviceManager->get('NBlog\Model\Helper\User');
        return $this->userHelperModel;
    }
}