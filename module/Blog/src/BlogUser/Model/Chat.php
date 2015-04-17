<?php
/**
 * Chat Model
 *
 * @category        Model
 * @package         BlogUser
 * @author          Mohammad Faisal Ahmed <faisal.ahmed0001@gmail.com>
 * @author          Md. Atiqul Haque <mailtoatiq@gmail.com>
 * @copyright       Copyright (c) 2013 Nokkhotro Blog. http://www.nokkhotroblog.com
 */
namespace BlogUser\Model;
use NBlog\Model\ServiceLocatorBlogDB;

class Chat extends ServiceLocatorBlogDB
{
    /**
     * @var     \BlogUser\Model\Dao\Chat
     */
    protected $dao = null;
    protected $userHelperModel = null;

    public function getChatterFriends($userId)
    {
        return $this->dao->getChatterFriends($userId);
    }

    public function getChatterFriendsHistory($userId)
    {
        $data = $this->dao->getChatterFriendsHistory($userId);
        $info = array();
        foreach($data AS $eachData) {
            $partnerId = ($eachData['from'] != $userId) ? $eachData['from'] : $eachData['to'];
            if (empty($info[$partnerId])) {
                $info[$partnerId] = array_merge($eachData, array('user_id' => $partnerId));
            }
        }
        $allMessages = $this->getUserHelperModel()->getShortProfilesOfUsers($info, array(
            'withProfile' => true,
            'userKey' => 'user_id'
        ));
        return $allMessages;
      }

    public function loadData($from, $to)
    {
        return $this->dao->loadData($from, $to);
    }

    public function loadChatBox($userId)
    {
        return $this->dao->loadChatBox($userId);
    }

    public function newMessage($from, $to, $message)
    {
        return $this->dao->newMessage($from, $to, $message);
    }

    public function getChatHistory($userId)
    {
        if (empty($userId)) {
            return array();
        }

        $allMessages = $this->getUserHelperModel()->getShortProfilesOfUsers($this->dao->getChatHistory($userId), array(
            'withProfile' => true,
            'userKey' => 'partner_id'
        ));
        $userMessages = array();
        foreach ($allMessages AS $message) {
            if ($message['user_id'] == $userId) {
                $userMessages[$message['from']][] = $message;
            } else {
                $userMessages[$message['user_id']][] = $message;
            }
        }

        return $userMessages;
    }

    public function getTopChatHistory($userId ,array $optios = array(),$isOnlyRead = true)
    {
        if (empty($userId)) {
            return array();
        }
        $allMessages = $this->getUserHelperModel()->getShortProfilesOfUsers($this->dao->getUnreadMessagePerson($userId,$optios,$isOnlyRead), array(
            'withProfile' => true,
            'userKey' => 'from'
        ));
        return $allMessages;
    }

    public function getChatHistoryByPerson($userId,array $optios = array()) {
        return $this->getTopChatHistory($userId,$optios,false);
    }

    public function countUnreadMessagePerson($userId) {
        return $this->dao->countUnreadMessagePerson($userId);
    }
    public function getChatAllMessage($userId)
    {
        if (empty($userId)) {
            return array();
        }

        $resultChat = $this->dao->getMessageBtId($userId);
        if(empty($resultChat)) {
            return array();
        }

        $options = array(
            'partner_id' =>$resultChat['partner_id'],
            'from'  => $resultChat['from']
        );
        $allMessages = $this->getUserHelperModel()->getShortProfilesOfUsers($this->dao->getChatAllMessage($options), array(
            'withProfile' => true,
            'userKey' => 'from'
        ));

        return $allMessages;
    }

    public function getLastMessagesOfUsers(array $userMessages, $userId)
    {
        if (empty($userMessages)) {
            return array();
        }
        $lastMessages = array();
        foreach ($userMessages AS $messages) {
            $lastMessage = end($messages);
            if ($lastMessage['partner_id'] == $userId) {
                $lastMessage['partner_id'] = $lastMessage['from'];
            }
            $lastMessages[] = $lastMessage;
        }

        return $this->getUserHelperModel()->getShortProfilesOfUsers($lastMessages, array(
            'withProfile' => true,
            'userKey' => 'partner_id'
        ));
    }


    public function setChatMessageRead($to,$from){
        if(empty($from) || empty($to) ){
            return false;
        }
        return $this->dao->setChatMessageRead($to,$from);
    }

    /**
     * @return \NBlog\Model\Helper\User
     */
    private function getUserHelperModel()
    {
        isset($this->userHelperModel) || $this->userHelperModel = $this->serviceManager->get('NBlog\Model\Helper\User');
        return $this->userHelperModel;
    }
}