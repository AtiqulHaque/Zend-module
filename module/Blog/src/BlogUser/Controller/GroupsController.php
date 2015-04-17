<?php
/**
 * Groups Controller
 *
 * @category        Controller
 * @package         BlogUser
 * @author          Md. Atiqul Haque <mailtoatiq@gmail.com>
 * @copyright       Copyright (c) 2012 Nokkhotro Blog. http://www.nokkhotroblog.com
 */
namespace BlogUser\Controller;
use Zend\View\Model\ViewModel;

class GroupsController extends UserBaseController
{
    protected $groupModel;

    public function indexAction()
    {
        $groupModel = $this->getGroupModel();
        $subscriberModel = $this->getSubscribeModel();
        $userDetail = $this->getUserModel()->getDetailHavingProfile($this->getSessionContainer()->offsetGet('user_id'));

        $this->initialize();
        return new ViewModel(array(
            'userDetail' => $userDetail,
            'blogInfo' => $userDetail,
            'userGroups' => $groupModel->getGroupByUserName($userDetail['user_id']),
            'groups' => $groupModel->getAll(),
            'favoriteWriters' => $subscriberModel->getFavoriteWriters($userDetail['user_id']),
            'favoritePosts' => $subscriberModel->getFavoritePosts($userDetail['user_id'])
        ));
    }

    /**
     * @return \BlogUser\Model\Group
     */
    private function getGroupModel()
    {
        isset($this->groupModel) || $this->groupModel = $this->getServiceLocator()->get('BlogUser\Model\Group');
        return $this->groupModel;
    }
}