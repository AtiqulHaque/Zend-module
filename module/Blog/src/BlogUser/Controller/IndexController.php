<?php
/**
 * User Controller
 *
 * @category        Controller
 * @package         BlogUser
 * @author          Md. Atiqul Haque <mailtoatiq@gmail.com>
 * @copyright       Copyright (c) 2012 Nokkhotro Blog. http://www.nokkhotroblog.com
 */
namespace BlogUser\Controller;

use BlogUser\Form\AccountEmail;
use BlogUser\Form\AccountName;
use BlogUser\Form\AccountUserName;
use BlogUser\Form\AccountPassword;
use BlogUser\Form\Comment AS CommentForm;
use BlogUser\Form\Profile;
use BlogUser\Form\OtherSettings;
use BlogUser\Form\Report AS ReportForm;
use BlogUser\Model\Entity\AccountName AS EntityAccountName;
use BlogUser\Model\Entity\AccountUserName AS EntityAccountUserName;
use BlogUser\Model\Entity\AccountPassword AS EntityAccountPassword;
use BlogUser\Model\Entity\AccountEmail AS EntityAccountEmail;
use BlogUser\Model\Entity\ProfileInfo AS EntityProfileInfo;
use NBlog\Model\KeyboardLayout;
use NBlog\Model\ReportStatus;
use NBlog\Model\UserStatus;
use User\Form\PhoneVerifier;
use Zend\Json\Json;
use Zend\View\Model\ViewModel;
use NBlog\Model\ImageConfig;
use User\Form\CodeVerifier;
use NBlog\Model\WritingType;
use NBlog\Utility\FileHandler;
use BlogUser\Model\Entity\OtherSettings AS OtherSettingsEntity;

class IndexController extends UserBaseController
{
    protected $menuItem = 'profile';
    protected $settingType = '';
    protected $albumModel;
    protected $discussionModel;
    protected $moodModel;
    protected $hiddenModel;
    protected $commentModel;
    protected $categoryModel;
    protected $countryModel;
    protected $countryCodeModel;
    protected $districtModel;
    protected $divisionModel;
    protected $educationalDegreeModel;
    protected $genderModel;
    protected $keyboardLayoutModel;
    protected $noticeModel;
    protected $noticeUserModel;
    protected $otherSettingModel;
    protected $otherSettingsModel;
    protected $professionModel;
    protected $profileModel;
    protected $reportModel;
    protected $reportMessageModel;
    protected $roleModel;
    protected $settingModel;
    protected $userRoleModel;
    protected $userSettingModel;
    protected $userSocialMediaModel;
    protected $userWallModel;
    protected $writingStatusModel;
    protected $policeStationModel;
    protected $postOfficeModel;

    public function indexAction()
    {
        $userDetail = $this->getUserDetail();
        if (empty($userDetail)) {
            return $this->redirectForFailure('blog', $this->translate('Unauthorized access. Please login to access.'));
        }

        $this->menuItem = 'dashboard';
        $this->initialize(null, $userDetail);
        $viewModel = new ViewModel(array(
            'latestNotice' => $this->getNoticeModel()->getLatestActiveNotice($userDetail['user_id']),
            'reportForm' => new ReportForm(array('messages' => $this->getReportMessageModel()->getAll())),
            'professions' => $this->getProfessionModel()->getAll(),
            'username' => $userDetail['username']
        ));

        $viewModel->addChild($this->forward()->dispatch('BlogUser\Controller\Index', array(
            'action' => 'getUserWallData',
            'isCalled' => true
        )), 'latestAnything');

        $viewModel->addChild($this->forward()->dispatch('BlogUser\Controller\Moods', array(
            'action' => 'add',
            'isCalled' => true
        )), 'moodView');

        $viewModel->addChild($this->forward()->dispatch('BlogUser\Controller\Blog', array(
            'action' => 'add-post',
            'isCalled' => true
        )), 'blogView');

        return $viewModel;
    }

    public function getProfileAction()
    {
        $userDetail = $this->getUserDetail();
        if (empty($userDetail)) {
            return $this->redirectForFailure('blog', $this->translate('Unauthorized access. Please login to access.'));
        }

        $this->menuItem = 'dashboard';
        $viewModel = $this->dealWithProfile($userDetail);

        $viewModel->addChild($this->forward()->dispatch('BlogUser\Controller\Moods', array(
            'action' => 'add',
            'isCalled' => true
        )), 'moodView');

        $viewModel->addChild($this->forward()->dispatch('BlogUser\Controller\Blog', array(
            'action' => 'add-post',
            'isCalled' => true
        )), 'blogView');

        $this->initialize(null, $userDetail);
        $this->enableLayoutBanner();
        return $viewModel;
    }

    public function publicProfileAction()
    {
        $userDetail = $this->getUserDetail();
        if (empty($userDetail)) {
            return $this->redirectForFailure('blog', $this->translate('User has been deleted.'));
        }

        $viewModel = $this->dealWithProfile($userDetail);
        $this->layout()->setTemplate('profile/layout')->setVariables(array(
            'userDetail' => $userDetail
        ));
        $this->initializeLayout();
        $this->enableLayoutBanner();

        return $viewModel;
    }

    private function dealWithProfile(array $userDetail)
    {
        $subscriberModel = $this->getSubscribeModel();
        $objFriendModel = $this->getFriendModel();

        $username = $this->params()->fromRoute('username', 'me');
        $viewModel = new ViewModel(array(
            'userDetail' => $userDetail,
            'username' => $username,
            'countBeingFavorite' => $subscriberModel->countBeingSubscribers($userDetail['user_id']),
            'countWritingsBeingFavorite' => $subscriberModel->countWritingsOfUser($userDetail['user_id']),
            'recentComments' => $this->getCommentModel()->getRecentComments($userDetail['user_id'], 5),
            'categories' => $this->getCategoryModel()->getAll(),
            'professions' => $this->getProfessionModel()->getAll(),
            'allFriendsForProfile' => $objFriendModel->getFriendsForProfile($userDetail['user_id']),
            'countAllFriends' => $objFriendModel->countAllFriends($userDetail['user_id']),
            'allPicturesForProfile' => $this->getAlbumModel()->allPicturesForProfile(array(
                'user_id' => $userDetail['user_id']
            )),
            'targetPath' => FileHandler::makePath(ImageConfig::VIEWPATH) . DIRECTORY_SEPARATOR . $userDetail['user_id'] . DIRECTORY_SEPARATOR . ImageConfig::THUMB . DIRECTORY_SEPARATOR,
            'reportForm' => new ReportForm(array('messages' => $this->getReportMessageModel()->getAll())),
        ));

        $viewModel->addChild($this->forward()->dispatch('BlogUser\Controller\Index', array(
            'action' => 'getProfileWallData',
            'username' => $username,
            'isCalled' => true
        )), 'latestAnything');

        $this->layout()->setVariables(array(
            'metaInfo' => array(
                'title' => $userDetail['nickname'],
                'description' => $this->getServiceLocator()->get('viewHelperManager')->get('Text')->word_limiter(strip_tags($userDetail['biography']), 100),
                'author' => $userDetail['nickname']
            ),
            'userBannerImagePath' => ImageConfig::VIEWPATH . '/' . $userDetail['user_id'] . '/' . ImageConfig::BANNER . '/',
            'bannerForPublic' => $this->getUserBannerModel()->getActiveBannerByUserId($userDetail['user_id']),
            'friendInfo' => $this->getFriendModel()->setFriendRequestText($this->getSessionContainer()->offsetGet('user_id'), $userDetail),
        ));

        return $viewModel->setTemplate('blog-user/index/deal-with-profile');
    }

    public function otherSettingsAction()
    {
        $request = $this->getRequest();
        $sessionContainer = $this->getSessionContainer();
        $userId = $sessionContainer->offsetGet('user_id');

        $otherSettingsModel = $this->getOtherSettingsModel();
        $otherSettingModel = $this->getOtherSettingModel();
        $otherSettingInfo = $otherSettingModel->getUserSetting($userId);

        $otherSettingsForm = new OtherSettings(array(
            'translator' => $this->getTranslatorHelper(),
            'key-board' => $this->getKeyboardLayoutModel()->getAll(),
            'language' => $otherSettingsModel->getAllLanguages(),
            'dateTimes' => $otherSettingsModel->getAllDateTimes()
        ));

        if ($request->isPost()) {
            $otherSettingEntity = new OtherSettingsEntity($this->getServiceLocator());
            $otherSettingsForm->setInputFilter($otherSettingEntity->getInputFilter());
            $otherSettingsForm->setData($request->getPost());
            if ($otherSettingsForm->isValid()) {
                $data = array_merge($otherSettingsForm->getData(), array(
                    'user_id' => $userId
                ));

                if ($otherSettingModel->getUserSetting($userId)) {
                    if ($otherSettingModel->userSettingsModifiedByConditions($data, array('user_id' => $userId))) {
                        $this->getSessionContainer()->offsetSet('keyboardLayout', (empty($data) ? KeyboardLayout::AVRO_PHONETIC : $data['keyboard']));
                        return $this->getResponse()->setContent(Json::encode(array(
                            'status' => 'success',
                            'data' => $this->translate('Data is updated successfully.')
                        )));
                    }

                } elseif ($otherSettingModel->save($data)) {
                    return $this->getResponse()->setContent(Json::encode(array(
                        'status' => 'success',
                        'data' => $this->translate('Data is updated successfully.')
                    )));
                }
            } else {
                return $this->redirectForFailure('other-settings', $this->translate('Something went wrong. Please try again.'));
            }

        } elseif (!empty($otherSettingInfo)) {
            $otherSettingsForm->setData($otherSettingInfo);
        }

        $viewModel = new ViewModel(array(
            'form' => $otherSettingsForm
        ));

        $viewModel->setTemplate('blog-user/index/other-settings');
        if ($request->isXmlHttpRequest()) {
            return $this->getResponse()->setContent(Json::encode(array(
                'html' => $this->getServiceLocator()->get('viewRenderer')->render($viewModel)
            )));
        } else {
            return $viewModel;
        }
    }

    public function changePasswordAction()
    {
        $passwordForm = new AccountPassword(array(
            'translator' => $this->getTranslatorHelper()
        ));

        $request = $this->getRequest();
        if ($request->isPost()) {
            $passwordEntity = new AccountPassword();
            $passwordForm->setInputFilter($passwordEntity->getInputFilter());
            $passwordForm->setData($request->getPost());
            if ($passwordForm->isValid()) {
                $data = $passwordForm->getData();
                $userId = $this->getSessionContainer()->offsetGet('user_id');
                $userModel = $this->getUserModel();

                if ($userModel->checkPasswordValid($data['password_info'], $userId)) {
                    $result = $userModel->updatePassword($data['password_info'], $userId);
                    if (empty($result)) {
                        return $this->redirectForFailure('change-password', $this->translate('Something went wrong. Please try again.'));
                    } else {
                        return $this->redirectForSuccess('profile-home', $this->translate('Your password has been reset successfully.'));
                    }
                } else {
                    $this->setFailureMessage($this->translate('Please enter correct password.'));
                }
            } else {
                $this->setFailureMessage($this->translate('Please check the following errors.'));
            }
        }

        $this->initialize();
        return new ViewModel(array('form' => $passwordForm));
    }

    public function settingsAction()
    {
        $this->initialize();
        return new ViewModel();
    }

    public function changeProfileInfoAction()
    {
        $sessionContainer = $this->getSessionContainer();
        $userId = $sessionContainer->offsetGet('user_id');
        $userDetail = $this->getUserModel()->getDetailHavingProfile($userId, true);
        if (empty($userDetail)) {
            return $this->redirectForFailure('profile-home', $this->translate('User data has not been found.'));
        }
        $divisionModel = $this->getDivisionModel();
        $districtModel = $this->getDistrictModel();
        $stationModel = $this->getPoliceStationModel();
        $officeModel = $this->getPostOfficeModel();
        $userSettingsModel = $this->getUserSettingModel();

        $request = $this->getRequest();
        $postData = $request->getPost();
        $countryId = isset($postData['contact_info']['country_id']) && $postData['contact_info']['country_id'] != ''
            ? $postData['contact_info']['country_id'] : null;
        $divisionId = isset($postData['contact_info']['division_id']) && $postData['contact_info']['division_id'] != ''
            ? $postData['contact_info']['division_id'] : null;
        $districtId = isset($postData['contact_info']['district_id']) && $postData['contact_info']['district_id'] != ''
            ? $postData['contact_info']['district_id'] : null;
        $stationId = isset($postData['contact_info']['station_id']) && $postData['contact_info']['station_id'] != ''
            ? $postData['contact_info']['station_id'] : null;

        $genders = $this->getGenderModel()->getAll();
        $degrees = $this->getEducationalDegreeModel()->getAll();
        $professions = $this->getProfessionModel()->getAll();
        $profileForm = new Profile(array(
            'translator' => $this->getTranslatorHelper(),
            'numberConverter' => $this->getNumberHelper(),
            'genders' => $genders,
            'degrees' => $degrees,
            'professions' => $professions,
            'countries' => $this->getCountryModel()->getAll(),
            'divisions' => $divisionModel->getAll($countryId ,true),
            'districts' => $districtModel->getAll($divisionId,true),
            'stations' => $stationModel->getStationByDistrictId(array('districtId' => $districtId),true),
            'offices' => $officeModel->getOfficesByStationId(array('stationId' => $stationId),true),
        ));

        if ($request->isPost()) {
            $profileEntity = new EntityProfileInfo($this->getServiceLocator());
            $profileEntity->setFormInputChecker($request->getPost('form_submitted'));
            $profileEntity->setEmailAddressToBeExcluded($userDetail['email']);
            $contactInfo = $request->getPost('contact_info');
            $profileEntity->setDivisions($divisionModel->getAll($contactInfo['country_id']));
            $profileEntity->setDistricts($districtModel->getAll($contactInfo['division_id']));
            $profileEntity->setStations($stationModel->getStationByDistrictId(array('districtId' => $contactInfo['district_id'])));
            $profileEntity->setPostOffices($officeModel->getOfficesByStationId(array('stationId' => $contactInfo['station_id'])));
            $profileForm->setInputFilter($profileEntity->getInputFilter());
            $profileForm->setData($postData);

            if ($profileForm->isValid()) {
                $newDetail = array_merge($profileForm->getData(), array(
                    'username' => $sessionContainer->offsetGet('username'),
                    'user_id' => $userId,
                    'form_submitted' => $request->getPost('form_submitted')
                ));

                $newPrivacy = $request->getPost()->toArray();
                $userSettingsModel->savePrivacySettings($userId, $newPrivacy);
                $result = $this->getUserModel()->modifyProfileInfo($newDetail, $userDetail);

                if (empty($result)) {
                    return $this->getResponse()->setContent(Json::encode(array(
                        'status' => 'error',
                        'data' => $this->translate('Something went wrong. Please try again.')))
                    );
                } else {
                    $successMsg = $this->translate('Profile has updated successfully.');
                }
            } else {
                return $this->getResponse()->setContent(Json::encode(array(
                    'status' => 'error',
                    'data' => $this->translate('Please check the following errors.')))
                );
            }
        } else {
            $userDetail = array_merge($userDetail, $this->getUserSocialMediaModel()->getSocialMedia($userId));
            $privacy = $userSettingsModel->getPrivacyData($userId);
            $profileForm->setData(array(
                'profile_info' => $userDetail,
                'more_profile_info' => $userDetail,
                'contact_info' => $userDetail,
                'educational_info' => $userDetail,
                'professional_info' => $userDetail,
                'privacy' => $privacy['all'],
                'section_privacy' => $privacy['section'],
            ));
        }

        $viewModel = new ViewModel(array(
            'profileForm' => $profileForm,
            'userDetail' => array_merge($userDetail, array('roles' => $this->getUserRoleModel()->getByUserId($userDetail['user_id']))),
            'roles' => $this->getRoleModel()->getAll(),
            'genders' => $genders,
            'degrees' => $degrees,
            'professions' => $professions,
            'privacyOptions' => $this->getSettingModel()->getUserBasisPrivacyOptions()
        ));

        $viewModel->setTemplate('blog-user/index/change-profile-info');
        if ($request->isXmlHttpRequest()) {
            $viewModel->setVariable('isAjax', true);
            $response = array(
                'status' => 'success',
                'html' => $this->getServiceLocator()->get('viewRenderer')->render($viewModel),
            );
            empty($successMsg) || $response['data'] = $successMsg;
            return $this->getResponse()->setContent(Json::encode($response));
        } else {
            $this->initialize();
            return $viewModel;
        }
    }

    public function changeAccountAction()
    {
        $formAccountName = new AccountName(array('translator' => $this->getTranslatorHelper()));
        $formAccountPassword = new AccountPassword(array('translator' => $this->getTranslatorHelper()));
        $formAccountUseName = new AccountUserName(array('translator' => $this->getTranslatorHelper()));
        $formAccountEmail = new AccountEmail(array('translator' => $this->getTranslatorHelper()));
        $this->settingType = $this->params()->fromRoute('tab');
        $request = $this->getRequest();
        $userDetail = $this->getUserModel()->getDetailHavingProfile($this->getSessionContainer()->offsetGet('user_id'), true);
        if ($request->isPost()) {
            $data = $request->getPost()->toArray();
            switch ($data['change-type']) {
                case 'name-change' :
                    $this->settingType = 'name';
                    $AccountSettingName = new EntityAccountName($this->getServiceLocator());
                    $formAccountName->setInputFilter($AccountSettingName->getInputFilter());
                    $formAccountName->setData($data);
                    if ($formAccountName->isValid()) {
                        $userId = $this->getSessionContainer()->offsetGet('user_id');
                        if (!$this->getUserModel()->checkPasswordByRolId($data, $userId, $this->getSessionContainer()->offsetGet('roles'))) {
                            return $this->getResponse()->setContent(Json::encode(array(
                                'status' => 'error',
                                'settingType' => $this->settingType,
                                'data' => $this->translate('Invalid password. Please try again.')
                            )));
                        }

                        if ($this->getProfileModel()->updateUserNickName($data, $userId)) {
                            $successMsg = $this->translate('Your name has been change successfully.');
                            $userDetail = array_merge($userDetail, $data);
                        } else {
                            $error = true;
                        }
                    } else {
                        $error = true;
                    }

                    break;

                case 'password-change' :
                    $this->settingType = 'password';
                    $passwordEntity = new EntityAccountPassword($this->getServiceLocator());
                    $formAccountPassword->setInputFilter($passwordEntity->getInputFilter());
                    $formAccountPassword->setData($request->getPost());
                    if ($formAccountPassword->isValid()) {
                        $data = $formAccountPassword->getData();
                        $formAccountPassword->setData(array());
                        $userId = $this->getSessionContainer()->offsetGet('user_id');
                        $userModel = $this->getUserModel();
                        if (!$userModel->checkPasswordByRolId($data, $userId, $this->getSessionContainer()->offsetGet('roles'))) {
                            return $this->getResponse()->setContent(Json::encode(array(
                                'status' => 'error',
                                'settingType' => $this->settingType,
                                'data' => $this->translate('Invalid password. Please try again.')
                            )));
                        }

                        if ($userModel->updatePassword($data, $userId)) {
                            $successMsg = $this->translate('Your password has been reset successfully.');
                            $userDetail = array_merge($userDetail, $data);
                        } else {
                            $error = true;
                        }
                    } else {
                        $error = true;
                    }

                    break;

                case 'username-change' :
                    $this->settingType = 'username';
                    $AccountUserName = new EntityAccountUserName($this->getServiceLocator());
                    $userId = $this->getSessionContainer()->offsetGet('user_id');
                    $AccountUserName->setUserIdToBeExcluded($userId);
                    $formAccountUseName->setInputFilter($AccountUserName->getInputFilter());
                    $formAccountUseName->setData($data);
                    if ($formAccountUseName->isValid()) {
                        $userModel = $this->getUserModel();
                        if (!$userModel->checkPasswordByRolId($data, $userId, $this->getSessionContainer()->offsetGet('roles'))) {
                            return $this->getResponse()->setContent(Json::encode(array(
                                'status' => 'error',
                                'settingType' => $this->settingType,
                                'data' => $this->translate('Invalid password. Please try again.')
                            )));
                        }
                        if ($data['username'] === $userDetail['username']) {
                            $successMsg = $this->translate('Your username has been reset successfully.');
                        }
                        if ($userModel->isUserNameExist(array('username' => $data['username']), $userId)) {
                            return $this->getResponse()->setContent(Json::encode(array(
                                'status' => 'error',
                                'settingType' => $this->settingType,
                                'data' => $this->translate('This username is already used. Please choose another.')
                            )));
                        }
                        if ($userModel->updateUserName(array('username' => $data['username']), $userId)) {
                            $successMsg = $this->translate('Your username has been reset successfully.');
                            $userDetail = array_merge($userDetail, array('username' => strtolower($data['username'])));
                        } else {
                            $error = true;
                        }
                    } else {
                        $error = true;
                    }

                    break;

                case 'email-change' :
                    $this->settingType = 'email';
                    $entityEmail = new EntityAccountEmail($this->getServiceLocator());
                    $formAccountEmail->setInputFilter($entityEmail->getInputFilter());
                    $formAccountEmail->setData($data);
                    if ($formAccountEmail->isValid()) {
                        $userId = $this->getSessionContainer()->offsetGet('user_id');
                        $userModel = $this->getUserModel();
                        if ($data['email'] === $data['new_email']) {
                            return $this->getResponse()->setContent(Json::encode(array(
                                'status' => 'error',
                                'settingType' => $this->settingType,
                                'data' => $this->translate('Same email address has been sent.')
                            )));
                        }
                        if ($userModel->isEmailExist($data, $userId)) {
                            return $this->getResponse()->setContent(Json::encode(array(
                                'status' => 'error',
                                'settingType' => $this->settingType,
                                'data' => $this->translate('This email is already used. Please choose another.')
                            )));
                        }

                        $data = array_merge($userDetail, $data);
                        if ($userModel->updateTemporaryEmail($data, $userId)) {
                            $successMsg = $this->translate('Email has been sent in your new email address. Please follow the instructions to change email address.');
                            $userDetail = $data;
                        } else {
                            $error = true;
                        }
                    } else {
                        $error = true;
                    }
                    break;

                default:
                    $this->settingType = '';
            }
            if (!empty($error)) {
                return $this->getResponse()->setContent(Json::encode(array(
                    'status' => 'error',
                    'settingType' => $this->settingType,
                    'data' => $this->translate('Something went wrong. Please try again.')
                )));
            }
        }

        $viewModel = new ViewModel(array(
            'formName' => $formAccountName->setData($userDetail),
            'formUserName' => $formAccountUseName->setData($userDetail),
            'formPassword' => $formAccountPassword,
            'formEmail' => $formAccountEmail->setData($userDetail),
            'formPhoneVerifier' => new PhoneVerifier(array(
                'translator' => $this->getTranslatorHelper(),
                'countryCodes' => $this->getCountryCodeModel()->getAll()
            )),
            'formCodeVerifier' => new CodeVerifier(array('translator' => $this->getTranslatorHelper())),
            'userDetails' => $userDetail,
            'isVerificationOpen' => (!empty($userDetail['mobile_no_for_sms']) && empty($userDetail['is_sms_verified'])) ? 1 : 0,
            'smsExpireTime' => strtotime($userDetail['sms_validity']) - strtotime(date(DATE_W3C))
        ));
        isset($successMsg) || $viewModel->setVariable('settingType', $this->settingType);

        $viewModel->setTemplate('blog-user/index/change-account');
        if ($request->isXmlHttpRequest()) {
            $viewModel->setVariable('isAjax', true);
            $response = array(
                'status' => 'success',
                'settingType' => $this->settingType,
                'html' => $this->getServiceLocator()->get('viewRenderer')->render($viewModel),
            );
            empty($successMsg) || $response['data'] = $successMsg;
            return $this->getResponse()->setContent(Json::encode($response));
        } else {
            $this->initialize();
            return $viewModel;
        }
    }

    public function checkUsernameUniqueAction()
    {
        $request = $this->getRequest();
        if ($request->isXmlHttpRequest()) {
            $data = $request->getPost()->toArray();
            $isUnique = $this->getUserModel()->checkUsernameUnique($data['username'], $this->getSessionContainer()->offsetGet('user_id'));
            return $this->getResponse()->setContent(Json::encode($isUnique, true));
        }

        exit($this->translate('Direct Access is Denied.'));
    }

    public function activateEmailAction()
    {
        $params = $this->params()->fromRoute();
        if (empty($params['user_id']) || empty($params['activate_code'])) {
            return $this->redirectForFailure('change-account', $this->translate('Something went wrong. Please try again.'));
        }
        $userModel = $this->getUserModel();
        $userId = $this->getSessionContainer()->offsetGet('user_id');
        $userInfo = $userModel->getDetailsByActivationCode($userId, $params['activate_code']);
        if (empty($userInfo)) {
            return $this->redirectForFailure('change-account', $this->translate('Invalid code. Please try again.'));
        }
        if ($this->isTimeExpire($userInfo['activation_validity'])) {
            return $this->redirectForFailure('change-account', $this->translate('Invalid code time expire. Please try again.'));
        }
        if ($userModel->updateUserEmail($userInfo, $userId)) {
            return $this->redirectForSuccess('change-account', $this->translate('Your email has been change successfully.'));
        } else {
            return $this->redirectForFailure('change-account', $this->translate('Something went wrong. Please try again.'));
        }
    }

    public function isTimeExpire($givenDateTime)
    {
        return (strtotime($givenDateTime) > time()) ? false : true;
    }

    public function getDivisionsAction()
    {
        $request = $this->getRequest();
        if ($request->isXmlHttpRequest()) {
            $divisions = $this->getDivisionModel()->getAll($request->getPost('countryId'),true);
            return $this->getResponse()->setContent(Json::encode(array('status' => 'success', 'data' => $divisions), true));
        }

        exit($this->translate('Direct Access is Denied.'));
    }

    public function getDistrictsAction()
    {
        $request = $this->getRequest();
        if ($request->isXmlHttpRequest()) {
            $districts = $this->getDistrictModel()->getAll((int)$request->getPost('divisionId'),true);
            return $this->getResponse()->setContent(Json::encode(array('status' => 'success', 'data' => $districts), true));
        }

        exit($this->translate('Direct Access is Denied.'));
    }

    public function closeNoticeAction()
    {
        $request = $this->getRequest();
        if ($request->isXmlHttpRequest()) {
            $notice = $this->getNoticeModel()->getByPermalink($this->params()->fromRoute('permalink'));
            if (empty($notice)) {
                return $this->getResponse()->setContent(Json::encode(array('status' => 'invalid', 'data' => $this->translate('Notice has not been found.')), true));
            } else {
                if ($this->getNoticeUserModel()->save(array(
                    'notice_id' => $notice['notice_id'],
                    'user_id' => $this->getSessionContainer()->offsetGet('user_id'),
                    'is_closed' => 1
                ))
                ) {
                    return $this->getResponse()->setContent(Json::encode(array('status' => 'success'), true));
                } else {
                    return $this->getResponse()->setContent(Json::encode(array('status' => 'error', 'msg' => $this->translate('Something went wrong. Please try again.')), true));
                }
            }
        }

        exit($this->translate('Direct Access is Denied.'));
    }

    public function hideContentAction()
    {
        $permalink = $this->params()->fromRoute('permalink');
        $contentType = $this->params()->fromRoute('content_type');
        $hiddenModel = $this->getHiddenModel();
        switch ($contentType) {
            case WritingType::POST:
                $content = $this->getBlogModel()->getByPermalink($permalink);
                $hiddenDetail = $hiddenModel->getDetails($contentType, $content['post_id']);
                $data = array('content_id' => $content['post_id']);
                break;

            case WritingType::COMMENT :
                $content = $this->getCommentModel()->getDetail($permalink);
                $hiddenDetail = $hiddenModel->getDetails($contentType, $content['comment_id']);
                $data = array('content_id' => $content['comment_id'], 'id_of_hidden_for' => $content['id_of_comment_for']);
                break;

            case WritingType::DISCUSSION:
                $content = $this->getDiscussionModel()->getByPermalink($permalink);
                $hiddenDetail = $hiddenModel->getDetails($contentType, $content['discussion_id']);
                $data = array('content_id' => $content['discussion_id']);
                break;

            case WritingType::MOOD:
                $content = $this->getMoodModel()->getByPermalink($permalink);
                $hiddenDetail = $hiddenModel->getDetails($contentType, $content['mood_id']);
                $data = array('content_id' => $content['mood_id']);
                break;

            case WritingType::NOTICE:
                $content = $this->getNoticeModel()->getByPermalink($permalink);
                $hiddenDetail = $hiddenModel->getDetails($contentType, $content['notice_id']);
                $data = array('content_id' => $content['notice_id']);
                break;

            default:
                $data = array();
        }

        if (empty($content)) {
            return $this->redirectForFailure('blog', $this->translate('Data has not been found.'));
        } else {
            if (empty($hiddenDetail)) {
                $data = array_merge($data, array('hidden_by' => $this->getSessionContainer()->offsetGet('user_id'), 'content_type' => $contentType));
                $result = $this->getHiddenModel()->save($data);
            } else {
                $result = $this->getHiddenModel()->modify(array('status' => 1), $hiddenDetail['hidden_id']);
            }

            if (empty($result)) {
                return $this->redirectToPreviousUrlForFailure($this->translate('Something went wrong. Please try again.'));
            } else {
                return $this->redirectToPreviousUrlForSuccess($this->translate('Data has been Hidden successfully.'));
            }
        }
    }

    public function unhideContentAction()
    {
        $permalink = $this->params()->fromRoute('permalink', null);
        $contentType = $this->params()->fromRoute('content_type', null);
        $hiddenModel = $this->getHiddenModel();

        switch ($contentType) {
            case WritingType::POST:
                $writing = $this->getBlogModel()->getByPermalink($permalink);
                $content = $hiddenModel->getDetails($contentType, $writing['post_id'], UserStatus::ACTIVE);
                break;

            case WritingType::COMMENT :
                $content = $hiddenModel->getDetails($contentType, $permalink, UserStatus::ACTIVE);
                break;

            case WritingType::DISCUSSION:
                $writing = $this->getDiscussionModel()->getByPermalink($permalink);
                $content = $hiddenModel->getDetails($contentType, $writing['discussion_id'], UserStatus::ACTIVE);
                break;

            case WritingType::MOOD:
                $writing = $this->getMoodModel()->getByPermalink($permalink);
                $content = $hiddenModel->getDetails($contentType, $writing['mood_id'], UserStatus::ACTIVE);
                break;

            case WritingType::NOTICE:
                $writing = $this->getNoticeModel()->getByPermalink($permalink);
                $content = $hiddenModel->getDetails($contentType, $writing['notice_id'], UserStatus::ACTIVE);
                break;
        }

        if (empty($content)) {
            return $this->redirectForFailure('blog', $this->translate('Data has not been found.'));
        } else {
            $result = $this->getHiddenModel()->modify(array('status' => 0), $content['hidden_id']);
            if (empty($result)) {
                return $this->redirectToPreviousUrlForFailure($this->translate('Something went wrong. Please try again.'));
            } else {
                return $this->redirectToPreviousUrlForSuccess($this->translate('Data has been Unhidden successfully.'));
            }
        }
    }

    public function reportAction()
    {
        $request = $this->getRequest();
        if ($request->isXmlHttpRequest()) {
            $reportForm = new ReportForm(array(
                'messages' => $this->getReportMessageModel()->getAll()
            ));

            $reportEntity = new \BlogUser\Model\Entity\Report($this->getServiceLocator());
            $reportForm->setInputFilter($reportEntity->getInputFilter());
            $reportForm->setData($request->getPost());

            if ($reportForm->isValid()) {
                $data = array_merge($reportForm->getData(), array(
                    'status' => ReportStatus::NO_ACTION,
                    'user_id' => $this->getSessionContainer()->offsetGet('user_id')
                ));
                $result = $this->getReportModel()->save($data);
                if (empty($result)) {
                    $result = array(
                        'status' => 'not-reported',
                        'html' => $this->translate('Something went wrong. Please try again.')
                    );
                } else {
                    $result = array(
                        'status' => 'success',
                        'html' => $this->translate('Comment has been reported successfully.')
                    );
                }
            } else {
                $result = array(
                    'status' => 'not-valid',
                    'html' => $this->translate('Please check the errors with the form.')
                );
            }
            return $this->getResponse()->setContent(Json::encode($result, true));
        } else {
            return $this->redirectToPreviousUrlForFailure($this->translate('Direct Access is Denied.'));
        }
    }

    public function setNotificationCheckedAction()
    {
        $request = $this->getRequest();
        if ($request->isXmlHttpRequest()) {
            $params = $request->getPost();
            $result = $this->getNotificationUserModel()->setAsChecked($params['notificationId'], $this->getSessionContainer()->offsetGet('user_id'));
            if (empty($result)) {
                $result = array('status' => 'error', 'html' => $this->translate('Something went wrong. Please try again.'));
            } else {
                $result = array('status' => 'success', 'html' => $this->translate('Notification has been set as seen.'));
            }
            return $this->getResponse()->setContent(Json::encode($result, true));
        }

        exit($this->translate('Direct Access is Denied.'));
    }

    public function getUserWallDataAction()
    {
        return $this->dealWithWallData(array());
    }

    public function getProfileWallDataAction()
    {
        return $this->dealWithWallData(array('profileWall' => 'profileWall'));
    }

    private function dealWithWallData(array $params = array())
    {
        $request = $this->getRequest();
        if ($request->isXmlHttpRequest() && $request->isPost()) {
            $params = array_merge($request->getPost()->toArray(), $params);
        } elseif ($this->params()->fromRoute('isCalled')) {
            $params = array_merge(array('page' => 1, 'rowPerPage' => 5), $params);
        } else {
            return $this->redirectToPreviousUrlForFailure('Direct Access is Denied.');
        }
        $userDetail = $this->getUserDetail();
        $reportMessages = $this->getReportMessageModel()->getAll();
        $viewModel = new ViewModel(array(
            'latestAnything' => $this->getUserWallModel()->getAll($userDetail['user_id'], $params),
            'categories' => $this->getCategoryModel()->getAll(),
            'commentForm' => new CommentForm(array('translator' => $this->getTranslatorHelper())),
            'reportForm' => new ReportForm(array('messages' => $reportMessages)),
            'reportMessages' => $reportMessages,
            'professions' => $this->getProfessionModel()->getAll(),
        ));

        $viewModel->setTemplate('blog-user/index/partials/user_wall_single_content');
        if ($request->isXmlHttpRequest()) {
            return $this->getResponse()->setContent(Json::encode(array(
                'html' => $this->getServiceLocator()->get('viewRenderer')->render($viewModel),
                'status' => 'success',
            ), true));
        } elseif ($this->params()->fromRoute('isCalled')) {
            return $viewModel;
        } else {
            return $this->redirectToPreviousUrlForFailure('Direct Access is Denied.');
        }
    }

    public function changeKeyboardLayoutAction()
    {
        $params = $this->getRequest()->getPost()->toArray();
        $sessionContainer = $this->getSessionContainer();
        $sessionContainer->offsetSet('keyboardLayout', (empty($params) ? KeyboardLayout::AVRO_PHONETIC : $params['id']));
        return $this->getResponse()->setContent(Json::encode(array(
            'html' => $this->translate('Keyboard layout is successfully set'),
            'status' => 'success',
        ), true));
    }

    public function getNotificationsAction()
    {
        $request = $this->getRequest();
        if ($request->isXmlHttpRequest()) {
            $page = $this->getRequest()->getPost('page');
            $deletePreviousNotifications = false;
        } elseif ($this->params()->fromRoute('isCalled')) {
            $page = $this->params()->fromRoute('page');
            $deletePreviousNotifications = true;
        } else {
            return $this->redirectToPreviousUrlForFailure('Direct Access is Denied.');
        }
        $viewModel = new ViewModel(array(
            'notifications' => $this->getNotificationUserModel()->getByUserId(
                $this->getSessionContainer()->offsetGet('user_id'), $page, $deletePreviousNotifications
            ),
        ));

        $viewModel->setTemplate('layout/partials/notifications');
        if ($request->isXmlHttpRequest()) {
            return $this->getResponse()->setContent(Json::encode(array(
                'status' => 'success',
                'html' => $this->getServiceLocator()->get('viewRenderer')->render($viewModel),
            ), true));
        } elseif ($this->params()->fromRoute('isCalled')) {
            return $viewModel;
        } else {
            return $this->redirectToPreviousUrlForFailure('Direct Access is Denied.');
        }
    }

    /**
     * @return \Blog\Model\Comment
     */
    private function getCommentModel()
    {
        isset($this->commentModel) || $this->commentModel = $this->getServiceLocator()->get('Blog\Model\Comment');
        return $this->commentModel;
    }

    /**
     * @return \Blog\Model\Notice
     */
    private function getNoticeModel()
    {
        isset($this->noticeModel) || $this->noticeModel = $this->getServiceLocator()->get('Blog\Model\Notice');
        return $this->noticeModel;
    }

    /**
     * @return  \BlogUser\Model\Album
     */
    protected function getAlbumModel()
    {
        isset($this->albumModel) || $this->albumModel = $this->getServiceLocator()->get('BlogUser\Model\Album');
        return $this->albumModel;
    }

    /**
     * @return \BlogUser\Model\Discussion
     */
    protected function getDiscussionModel()
    {
        isset($this->discussionModel) || $this->discussionModel = $this->getServiceLocator()->get('BlogUser\Model\Discussion');
        return $this->discussionModel;
    }

    /**
     * @return \BlogUser\Model\Mood
     */
    private function getMoodModel()
    {
        isset($this->moodModel) || $this->moodModel = $this->getServiceLocator()->get('BlogUser\Model\Mood');
        return $this->moodModel;
    }

    /**
     * @return \BlogUser\Model\Hidden
     */
    protected function getHiddenModel()
    {
        isset($this->hiddenModel) || $this->hiddenModel = $this->getServiceLocator()->get('BlogUser\Model\Hidden');
        return $this->hiddenModel;
    }

    /**
     * @return \Blog\Model\UserWall
     */
    private function getUserWallModel()
    {
        isset($this->userWallModel) || $this->userWallModel = $this->getServiceLocator()->get('Blog\Model\UserWall');
        return $this->userWallModel;
    }

    /**
     * @return \BlogUser\Model\OtherSettings
     */
    private function getOtherSettingsModel()
    {
        isset($this->otherSettingsModel) || $this->otherSettingsModel = $this->getServiceLocator()->get('BlogUser\Model\OtherSettings');
        return $this->otherSettingsModel;
    }

    /**
     * @return \BlogUser\Model\EducationalDegree
     */
    private function getEducationalDegreeModel()
    {
        isset($this->educationalDegreeModel) || $this->educationalDegreeModel = $this->getServiceLocator()->get('BlogUser\Model\EducationalDegree');
        return $this->educationalDegreeModel;
    }

    /**
     * @return \BlogUser\Model\NoticeUser
     */
    private function getNoticeUserModel()
    {
        isset($this->noticeUserModel) || $this->noticeUserModel = $this->getServiceLocator()->get('BlogUser\Model\NoticeUser');
        return $this->noticeUserModel;
    }

    /**
     * @return \BlogUser\Model\OtherSetting
     */
    private function getOtherSettingModel()
    {
        isset($this->otherSettingModel) || $this->otherSettingModel = $this->getServiceLocator()->get('BlogUser\Model\OtherSetting');
        return $this->otherSettingModel;
    }

    /**
     * @return \BlogUser\Model\Report
     */
    private function getReportModel()
    {
        isset($this->reportModel) || $this->reportModel = $this->getServiceLocator()->get('BlogUser\Model\Report');
        return $this->reportModel;
    }

    /**
     * @return  \BlogUser\Model\User
     */
    protected function getUserModel()
    {
        isset($this->userModel) || $this->userModel = $this->getServiceLocator()->get('BlogUser\Model\User');
        return $this->userModel;
    }

    /**
     * @return \BlogUser\Model\UserSocialMedia
     */
    private function getUserSocialMediaModel()
    {
        isset($this->userSocialMediaModel) || $this->userSocialMediaModel = $this->getServiceLocator()->get('BlogUser\Model\UserSocialMedia');
        return $this->userSocialMediaModel;
    }

    /**
     * @return \NBlog\Model\Category
     */
    private function getCategoryModel()
    {
        isset($this->categoryModel) || $this->categoryModel = $this->getServiceLocator()->get('NBlog\Model\Category');
        return $this->categoryModel;
    }

    /**
     * @return \NBlog\Model\Country
     */
    private function getCountryModel()
    {
        isset($this->countryModel) || $this->countryModel = $this->getServiceLocator()->get('NBlog\Model\Country');
        return $this->countryModel;
    }

    /**
     * @return \NBlog\Model\CountryCode
     */
    private function getCountryCodeModel()
    {
        isset($this->countryCodeModel) || $this->countryCodeModel = $this->getServiceLocator()->get('NBlog\Model\CountryCode');
        return $this->countryCodeModel;
    }

    /**
     * @return \NBlog\Model\District
     */
    private function getDistrictModel()
    {
        isset($this->districtModel) || $this->districtModel = $this->getServiceLocator()->get('NBlog\Model\District');
        return $this->districtModel;
    }

    /**
     * @return \NBlog\Model\Division
     */
    private function getDivisionModel()
    {
        isset($this->divisionModel) || $this->divisionModel = $this->getServiceLocator()->get('NBlog\Model\Division');
        return $this->divisionModel;
    }

    /**
     * @return \NBlog\Model\Gender
     */
    private function getGenderModel()
    {
        isset($this->genderModel) || $this->genderModel = $this->getServiceLocator()->get('NBlog\Model\Gender');
        return $this->genderModel;
    }

    /**
     * @return \NBlog\Model\KeyboardLayout
     */
    private function getKeyboardLayoutModel()
    {
        isset($this->keyboardLayoutModel) || $this->keyboardLayoutModel = $this->getServiceLocator()->get('NBlog\Model\KeyboardLayout');
        return $this->keyboardLayoutModel;
    }

    /**
     * @return \NBlog\Model\Profession
     */
    private function getProfessionModel()
    {
        isset($this->professionModel) || $this->professionModel = $this->getServiceLocator()->get('NBlog\Model\Profession');
        return $this->professionModel;
    }

    /**
     * @return \NBlog\Model\Profile
     */
    private function getProfileModel()
    {
        isset($this->profileModel) || $this->profileModel = $this->getServiceLocator()->get('NBlog\Model\Profile');
        return $this->profileModel;
    }

    /**
     * @return \NBlog\Model\Role
     */
    private function getRoleModel()
    {
        isset($this->roleModel) || $this->roleModel = $this->getServiceLocator()->get('NBlog\Model\Role');
        return $this->roleModel;
    }

    /**
     * @return \NBlog\Model\UserRole
     */
    private function getUserRoleModel()
    {
        isset($this->userRoleModel) || $this->userRoleModel = $this->getServiceLocator()->get('NBlog\Model\UserRole');
        return $this->userRoleModel;
    }

    /**
     * @return \NBlog\Model\UserSetting
     */
    private function getUserSettingModel()
    {
        isset($this->userSettingModel) || $this->userSettingModel = $this->getServiceLocator()->get('NBlog\Model\UserSetting');
        return $this->userSettingModel;
    }

    /**
     * @return \NBlog\Model\ReportMessage
     */
    private function getReportMessageModel()
    {
        isset($this->reportMessageModel) || $this->reportMessageModel = $this->getServiceLocator()->get('NBlog\Model\ReportMessage');
        return $this->reportMessageModel;
    }

    /**
     * @return \NBlog\Model\Setting
     */
    private function getSettingModel()
    {
        isset($this->settingModel) || $this->settingModel = $this->getServiceLocator()->get('NBlog\Model\Setting');
        return $this->settingModel;
    }

    /**
     * @return \Geo\Model\PoliceStation
     */
    private function getPoliceStationModel()
    {
        isset($this->policeStationModel) || $this->policeStationModel = $this->getServiceLocator()->get('Geo\Model\PoliceStation');
        return $this->policeStationModel;
    }

    /**
     * @return \Geo\Model\PostOffice
     */
    private function getPostOfficeModel()
    {
        isset($this->postOfficeModel) || $this->postOfficeModel = $this->getServiceLocator()->get('Geo\Model\PostOffice');
        return $this->postOfficeModel;
    }
}