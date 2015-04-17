<?php
/**
 * Novels Controller
 *
 * @category        Controller
 * @package         BlogUser
 * @author          Md. Atiqul Haque <mailtoatiq@gmail.com>
 * @copyright       Copyright (c) 2012 Nokkhotro Blog. http://www.nokkhotroblog.com
 */
namespace BlogUser\Controller;
use BlogUser\Form\NovelName;
use Zend\View\Model\ViewModel;

class NovelsController extends UserBaseController
{
    protected $novelNameModel;

    public function indexAction()
    {
        $userDetail = $this->getUserModel()->getDetailHavingProfile($this->getSessionContainer()->offsetGet('user_id'));
        $this->initialize(null, $userDetail);
        return new ViewModel(array(
            'userDetail' => $userDetail,
            'novels' => $this->getNovelNameModel()->getByUser($userDetail['user_id']),
            'favoriteNovels' => $this->getNovelNameModel()->getByUser($userDetail['user_id'])
        ));
    }

    public function addAction()
    {
        $request = $this->getRequest();
        $novelForm = new NovelName();

        if ($request->isPost()) {
            $novelNameEntity = new \BlogUser\Model\Entity\NovelName($this->getServiceLocator());
            $novelForm->setInputFilter($novelNameEntity->getInputFilter());
            $novelForm->setData($request->getPost());

            if ($novelForm->isValid()) {
                $data = array_merge($novelForm->getData(), array('user_id' => $this->getSessionContainer()->offsetGet('user_id')));
                $result = $this->getNovelNameModel()->save($data);
                if (empty($result)) {
                    $this->setFailureMessage($this->translate('Something went wrong. Please try again.'));
                } else {
                    return $this->redirectForSuccess('my-novels', $this->translate('Your novel name has been submitted.'));
                }
            } else {
                $this->setFailureMessage($this->translate('Please check the following error.'));
            }
        }

        $this->initialize();
        return new ViewModel(array(
            'novelForm' => $novelForm
        ));
    }

    public function editAction()
    {
        $request = $this->getRequest();
        $novelNameForm = new NovelName();
        $novelNameForm->get('submit')->setValue('Update');

        if ($request->isPost()) {
            $novelNameEntity = new \BlogUser\Model\Entity\NovelName($this->getServiceLocator());
            $novelNameForm->setInputFilter($novelNameEntity->getInputFilter());
            $novelNameForm->setData($request->getPost());
            if ($novelNameForm->isValid()) {
                $data = array_merge($novelNameForm->getData(), array('user_id' => $this->getSessionContainer()->offsetGet('user_id')));
                $result = $this->getNovelNameModel()->modify($data, $data['novel_name_id']);
                if (empty($result)) {
                    $this->setFailureMessage($this->translate('Something went wrong. Please try again.'));
                } else {
                    return $this->redirectForSuccess('my-novels', $this->translate('Novel has been updated successfully.'));
                }
            }
        } else {
            $permalink = $this->params()->fromRoute('permalink', null);
            if (empty($permalink)) {
                return $this->redirectForFailure('my-novels', $this->translate('Novel data has not been given.'));
            } else {
                $novelData = $this->getNovelNameModel()->getByPermalink($permalink);
                if (empty($novelData)) {
                    $this->setFailureMessage($this->translate('Something went wrong. Please try again.'));
                } else {
                    $novelNameForm->setData($novelData);
                }
            }
        }

        $this->initialize();
        return new ViewModel(array(
            'novelForm' => $novelNameForm
        ));
    }

    public function deleteAction()
    {
        $permalink = $this->params()->fromRoute('permalink', null);
        $novelDetail = $this->getNovelNameModel()->getByPermalink($permalink);
        if (empty($novelDetail)) {
            return $this->redirectForFailure('my-novels', $this->translate('Novel data has not given.'));
        }

        $status = $this->getNovelNameModel()->remove($novelDetail['novel_name_id']);
        if (empty($status)) {
            return $this->redirectForFailure('my-novels', $this->translate('Something went wrong. Please try again.'));
        } else {
            return $this->redirectForSuccess('my-novels', $this->translate('Novel name has been deleted successfully.'));
        }
    }

    /**
     * @return  \BlogUser\Model\NovelName
     */
    protected function getNovelNameModel()
    {
        isset($this->novelNameModel) || $this->novelNameModel = $this->getServiceLocator()->get('BlogUser\Model\NovelName');
        return $this->novelNameModel;
    }
}