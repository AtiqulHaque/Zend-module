<?php
/**
 * Images Controller
 *
 * @category        Controller
 * @package         BlogUser
 * @author          Md.Atiqul Haque <mailtoatiqul@gmail.com>
 * @copyright       Copyright (c) 2012 Nokkhotro Blog. http://www.nokkhotroblog.com
 */
namespace BlogUser\Controller;

use BlogUser\Model\Entity\Album AS AlbumEntity;
use NBlog\Library\Image\ImageGD;
use NBlog\Model\ImageUsagesType;
use NBlog\Model\ImageConfig;
use NBlog\Utility\FileHandler;
use NBlog\Utility\FileUploader;
use NBlog\Utility\Image;
use Zend\Config\Reader\Ini;
use Zend\View\Model\ViewModel;
use Zend\Json\Json;
use BlogUser\Form\Album;

class ImagesController extends UserBaseController
{
    protected $albumModel;
    protected $imageUtility;
    protected $imageModel;
    protected $imageUsagesModel;
    protected $imageUsagesTypeModel;
    protected $profileModel;
    protected $profilePictureModel;
    protected $menuItem = 'image';
    const DS = DIRECTORY_SEPARATOR;


    public function indexAction()
    {
        if ($this->params()->fromRoute('username', null) === 'me' && !$this->getSessionContainer()->offsetGet('user_id')) {
            return $this->redirectNow('login', array('next' => urlencode($_SERVER['REQUEST_URI'])));
        }

        $userDetail = $this->getUserDetail();
        if (empty($userDetail)) {
            return $this->redirectForFailure('profile-home', $this->translate('Unauthorized access. Please login to access.'));
        }

        $this->menuItem = 'dashboard';
        $this->initialize(null, $userDetail);
        $this->enableLayoutBanner();
        $albumForm = new Album(array('translator' => $this->getTranslatorHelper()));
        $viewModel = new ViewModel(array(
            'userDetail' => $userDetail,
            'albumForm' => $albumForm
        ));
        return $viewModel;
    }

    public function showAlbumsAction()
    {
        $request = $this->getRequest();
        if (!$request->isXmlHttpRequest()) {
            return $this->redirectForFailure('profile-home', $this->translate('Something went wrong. Please try again.'));
        }

        $params = $request->getPost()->toArray();
        if (empty($params)) {
            return $this->getResponse()->setContent(Json::encode(array(
                'status' => 'error',
                'html' => $this->translate('Something went wrong. Please try again.')), true)
            );
        }

        $userDetail = $this->getUserDetail();
        if (empty($userDetail)) {
            return $this->getResponse()->setContent(Json::encode(array(
                'status' => 'error',
                'html' => $this->translate('User detail has not been found.')), true)
            );
        }
        $userId = $userDetail['user_id'];
        $path = FileHandler::makeRelativePath(ImageConfig::VIEWPATH . self::DS . $userId . self::DS . ImageConfig::THUMB . self::DS);

        if (!empty($params['album-type']) && $params['album-type'] == ImageUsagesType::ALBUM) {
            $result = $this->getAlbumModel()->getUserAllAlbum(array('user_id' => $userId));
            return $this->getResponse()->setContent(Json::encode(array(
                'status' => 'success',
                'type' => ImageUsagesType::ALBUM,
                'data' => array('result' => $result, 'path' => $path)
            ), true));

        } else {
            $result = $this->getImageModel()->getByUsagesType(array(
                'user_id' => $userId,
                'usages_type' => $params['album-type']
            ));
            return $this->getResponse()->setContent(Json::encode(array(
                'status' => 'success',
                'type' => $params['album-type'],
                'data' => array('result' => $result, 'path' => $path)
            ), true));
        }
    }

    public function addAlbumAction()
    {
        $albumForm = new Album(array('translator' => $this->getTranslatorHelper()));
        $request = $this->getRequest();
        if ($this->getRequest()->isXmlHttpRequest()) {
            $response = array();
            if ($request->isPost()) {
                $entity = new AlbumEntity($this->getServiceLocator());
                $albumForm->setInputFilter($entity->getInputFilter());
                $albumForm->setData($request->getPost());
                if ($albumForm->isValid()) {
                    $albumModel = $this->getAlbumModel();
                    $options = $this->getRequest()->getPost()->toArray();
                    $userId = $this->getSessionContainer()->offsetGet('user_id');
                    $params = array('user_id' => $userId, 'album_name' => $options['album_name']);
                    $totalAlbumByName = $albumModel->countAllByAlbumName($params);
                    if (empty($totalAlbumByName)) {
                        if ($albumModel->save($params)) {
                            $response['status'] = 'success';
                            $response['html'] = 'Album Successfully saved.';
                        } else {
                            $response['status'] = 'error';
                            $response['html'] = 'Album not saved.';
                        }
                    } else {
                        $response['status'] = 'error';
                        $response['html'] = 'Album already exists.';
                    }

                } else {
                    $response['status'] = 'error';
                    $response['html'] = 'Please Select album name.';
                }
            } else {
                $response['status'] = 'error';
                $response['html'] = 'Error found.';
            }
            return $this->getResponse()->setContent(Json::encode($response, true));
        } else {
            exit($this->translate('Direct Access is Denied.'));
        }
    }

    public function addAlbumPicAction()
    {
        if ($this->getRequest()->isXmlHttpRequest()) {
            $params = $this->getRequest()->getPost();

            $albumModel = $this->getAlbumModel();
            if (empty($params['permalink'])) {
                return $this->getResponse()->setContent(Json::encode(array('error' => $this->translate('Invalid album')), true));
            }
            $userId = $this->getSessionContainer()->offsetGet('user_id');
            $albumInfo = $albumModel->getAlbumByPermalink(array('user_id' => $userId, 'permalink' => $params['permalink']));
            if (empty($albumInfo['permalink'])) {
                return $this->getResponse()->setContent(Json::encode(array('error' => $this->translate('Album not found')), true));
            }
            $fileUpLoader = new FileUploader();
            $fileHandler = new FileHandler();
            $imageHandler = $this->getImageUtility($fileHandler);
            $configInfo = $this->getImageConfigInfo();
            $minDimension = $imageHandler->getDimension($configInfo[ImageConfig::TEMP][ImageConfig::TEMPMIN]);
            $targetPath = $userId . DS . ImageConfig::ORIGINAL;
            $pathInformation = $imageHandler->createUploadPath($targetPath);
            $result = $imageHandler->uploadAccordingToConfig($fileUpLoader, $pathInformation['saveChunkPath'], $pathInformation['saveUploadPath'], $minDimension);
            if (empty($result['error'])) {
                $result['uploadName'] = $fileUpLoader->getUploadName();
                $imageHandler->resizeInitialWithConfig($pathInformation['saveUploadPath'] . DS . $result['uploadName'], $configInfo[ImageConfig::INITIAL_SIZE][ImageConfig::SIZE],$configInfo[ImageConfig::INITIAL_SIZE][ImageConfig::QUALITY]);
                $targetPathForThumbFolders = $userId;
                $originalImg = $pathInformation['saveUploadPath'] . DS . $result['uploadName'];
                $imageHandler->createThumb($originalImg, $targetPathForThumbFolders, $configInfo);
                $imgInfo = array(
                    'id_of_image_for' => 0,
                    'user_id' => $userId,
                    'album_id' => $albumInfo['album_id'],
                    'usages_type' => ImageUsagesType::ALBUM,
                    'images' => array(
                        ImageUsagesType::UNIQUE => array(
                            $result['uploadName']
                        )
                    )
                );
                $this->getImageModel()->saveContentImage($imgInfo);
                $totalPic = $this->getImageUsagesModel()->countImagesByAlbumId(array(
                    'album_id' => $albumInfo['album_id']
                ));
                $albumModel->updateTotalPic(array('total' => $totalPic), $albumInfo['album_id']);
                return $this->getResponse()->setContent(Json::encode($result));
            }else {
                return $this->getResponse()->setContent(Json::encode(array(
                    'status'=>'error',
                    'html'=>$result['error']), true));
            }

        } else {
            exit($this->translate('Direct Access is Denied.'));
        }
    }

    public function removeAlbumPicAction()
    {
        if ($this->getRequest()->isXmlHttpRequest()) {
            $params = $this->getRequest()->getPost()->toArray();
            $userId = $this->getSessionContainer()->offsetGet('user_id');
            $imageProcessor = $this->ContentImageProcessor();
            if (!empty($params)) {
                $removeFiles = $this->getImageModel()->deleteAlbumImage(array_merge($params, array('user_id' => $userId)));
                $imageProcessor->removeImages($userId, $removeFiles);
            }
            return $this->getResponse()->setContent(Json::encode(array(
                'status' => 'success',
                'html' => $this->translate('Successfully remove.')
            ), true));
        } else {
            exit($this->translate('Direct Access is Denied.'));
        }
    }

    public function bannerPicTempUploadAction()
    {
        $request = $this->getRequest();
        if (!$request->isXmlHttpRequest()) {
            return $this->redirectForFailure('profile-home', $this->translate('Something went wrong. Please try again.'));
        }

        $fileHandler = new FileHandler();
        $fileImage = new ImageGD();
        $imageHandler = $this->getImageUtility($fileHandler);
        $configInfo = $this->getImageConfigInfo();
        $userId = $this->getSessionContainer()->offsetGet('user_id');
        $tempModel = $this->getTempPictureModel();
        $params = $this->getRequest()->getPost()->toArray();
        if (!empty($params['isReuse']) && $params['isReuse'] == 1) {
            if (empty($params['image_id'])) {
                return $this->getResponse()->setContent(Json::encode(array(
                    'status' => 'error',
                    'html' => $this->translate('Something went wrong. Please try again.')
                ), true));
            }
            $imageInfo = $this->getImageModel()->getImageName($userId,$params['image_id']);
            if (!empty($imageInfo['image_url'])) {
                $targetPath = $userId . DS . ImageConfig::ORIGINAL . DS . $imageInfo['image_url'];
                $dimension = $imageHandler->getDimension($configInfo[ImageConfig::BANNER][ImageConfig::BANNERFIT]);
                $uploadPath = $imageHandler->createSaveUploadPath($targetPath);
                if ($fileHandler->isFileExist($uploadPath)) {
                    if ($imgDetails = $fileHandler->getImageDetails($uploadPath)) {
                        if (!($imgDetails[0] >= $dimension['width'])  ||
                            !($imgDetails[1] >= $dimension['height'])) {
                            return $this->getResponse()->setContent(Json::encode(array(
                                'status' => 'error',
                                'html'=>$this->translate('Minimum dimension is (1058 X 320)')), true)
                            );
                        }
                        $currentDimension = getimagesize($uploadPath);
                        $targetDimension = $fileImage->resizeValues($dimension['width'],$dimension['height'],$currentDimension[0],$currentDimension[1],true);
                        if (!empty($targetDimension)) {
                            $uploadTempPath = $imageHandler->createSaveUploadPath( $userId . DS .ImageConfig::TEMP);
                            $fileImage->setFile($uploadPath);
                            $fileImage->resize($uploadTempPath .DS . $imageInfo['image_url'], $targetDimension['width'], $targetDimension['height']);
                            $tempModel->savePictureIntoDb($userId, $imageInfo['image_url'], ImageConfig::BANNERSTATUS);
                            $result = array('success' => true,'file'=>$uploadTempPath .DS . $imageInfo['image_url']);
                        } else {
                            return $this->getResponse()->setContent(Json::encode(array(
                                'status' => 'error',
                                'html' => $this->translate('Dimension not specified')
                            ), true));
                        }
                    } else {
                        return $this->getResponse()->setContent(Json::encode(array(
                            'status' => 'error',
                            'html' => $this->translate('File not found')
                        ), true));
                    }
                } else {
                    return $this->getResponse()->setContent(Json::encode(array(
                        'status' => 'error',
                        'html' => $this->translate('File not found')
                    ), true));
                }
            } else {
                return $this->getResponse()->setContent(Json::encode(array(
                    'status' => 'error',
                    'html' => $this->translate('File not found')
                ), true));
            }
        } else {
            $fileUpLoader = new FileUploader();
            $targetPath = $userId . DS  . ImageConfig::TEMP;
            $pathInfo = $imageHandler->createUploadPath($targetPath);
            $minDimension = $imageHandler->getDimension($configInfo[ImageConfig::BANNER][ImageConfig::MIN]);
            $result = $imageHandler->uploadAccordingToConfig($fileUpLoader, $pathInfo['saveChunkPath'], $pathInfo['saveUploadPath'], $minDimension);
            if (empty($result['error'])) {
                $result['uploadName'] = $fileUpLoader->getUploadName();
                $targetDimension = $imageHandler->getDimension($configInfo[ImageConfig::BANNER][ImageConfig::BANNERFIT]);
                $currentDimension = getimagesize($pathInfo['saveUploadPath'] . DS . $result['uploadName']);
                $targetDimension = $fileImage->resizeValues($targetDimension['width'],$targetDimension['height'],$currentDimension[0],$currentDimension[1],true);
                if (!empty($targetDimension)) {
                    $fileImage->setFile($pathInfo['saveUploadPath'] . DS . $result['uploadName']);
                    $fileImage->resize($pathInfo['saveUploadPath'] . DS . $result['uploadName'], $targetDimension['width'], $targetDimension['height']);
                }
                $tempModel->savePictureIntoDb($userId, $result['uploadName'], ImageConfig::BANNERSTATUS);
                $result = array('success' => true,'file'=>$pathInfo['saveUploadPath'] . DS . $result['uploadName']);
            }
        }
        return $this->getResponse()->setContent(Json::encode($result, true));
    }

    /**
     * @param FileHandler $fileHandler
     *
     * @return Image
     */
    protected function getImageUtility(FileHandler $fileHandler)
    {
        isset($this->imageUtility) || $this->imageUtility = new Image($fileHandler);
        return $this->imageUtility;
    }

    private function getImageConfigInfo()
    {
        $reader = new Ini();
        return $reader->fromFile('config/image-config.ini');
    }

    public function bannerPicTempUploadCancelAction()
    {
        $request = $this->getRequest();
        if (!$request->isXmlHttpRequest()) {
            return $this->redirectForFailure('profile-home', $this->translate('Something went wrong. Please try again.'));
        }
        $userId = $this->getSessionContainer()->offsetGet('user_id');
        $tempModel = $this->getTempPictureModel();
        $fileHandler = new FileHandler();
        $imageInfo = $tempModel->getAll(array('user_id' => $userId, 'type' => ImageConfig::BANNERSTATUS));
        if (!empty($imageInfo)) {
            $rootUploadPath = $fileHandler->makePath(ImageConfig::UPLOADPATH);
            $targetPath = $userId . DS .  ImageConfig::TEMP;
            $saveUploadPath = $fileHandler->makePath($rootUploadPath . DS . $targetPath . DS . $imageInfo['name']);
            $fileHandler->removeSingleFile($saveUploadPath);
            $tempModel->removeTempFile($userId, ImageConfig::BANNERSTATUS);
            return $this->getResponse()->setContent(Json::encode(array('status' => 'success'), true));
        } else {
            return $this->getResponse()->setContent(Json::encode(array(
                'status' => 'success',
                'html' => $this->translate('Something went wrong. Please try again.')), true));
        }
    }

    public function bannerPicCropAction()
    {
        $request = $this->getRequest();
        if (!$request->isXmlHttpRequest()) {
            return $this->redirectForFailure('profile-home', $this->translate('Something went wrong. Please try again.'));
        }
        $params = $this->getRequest()->getPost();
        if (empty($params)) {
            return $this->getResponse()->setContent(Json::encode(array(
                    'status' => 'error',
                    'html' => $this->translate('Something went wrong. Please try again.')), true)
            );
        }
        $fileHandler = new FileHandler();
        $configInfo = $this->getImageConfigInfo();
        $imageHandler = $this->getImageUtility($fileHandler);
        $userId = $this->getSessionContainer()->offsetGet('user_id');
        $imageInfo = $this->getTempPictureModel()->getAll(array(
            'user_id' => $userId,
            'type' => ImageConfig::BANNERSTATUS
        ));

        if (empty($imageInfo)) {
            return $this->getResponse()->setContent(Json::encode(array(
                    'status' => 'error',
                    'html' => $this->translate('Something went wrong. Please try again.')), true)
            );
        }
        $serviceLib = new ImageGD();
        $rootUploadPath = $fileHandler->makePath(ImageConfig::UPLOADPATH);
        $saveTempPath = $fileHandler->makePath($rootUploadPath . DS . $userId . DS .  ImageConfig::TEMP);
        $activeBannerPath = $userId . DS . ImageConfig :: ACTIVEBANNER;
        $pathInformation = $imageHandler->createUploadPath($activeBannerPath);
        $picTempPath = $saveTempPath . DS . $imageInfo['name'];
        $newPicPath = $fileHandler->makePath($pathInformation['saveUploadPath']. DS . $imageInfo['name']);
        $serviceLib->setFile($saveTempPath . DS . $imageInfo['name']);
        $requireDimension = $imageHandler->getDimension($configInfo[ImageConfig::BANNER_SIZES]);

        foreach($requireDimension AS $key => $eachDimension) {
            if ($key == ImageConfig::BANNER_SMALL) {
                $serviceLib->setFile($pathInformation['saveUploadPath']. DS . $fileHandler->renameFile($imageInfo['name'], true, ImageConfig::BANNER));
                $convertedValue = $serviceLib->resizeValues($eachDimension['width'], $eachDimension['height'], $serviceLib->getWidth(), $serviceLib->getHeight());
                $fileName = $fileHandler->makePath($pathInformation['saveUploadPath']. DS . $fileHandler->renameFile($newPicPath, true, $key));
                $serviceLib->resize($fileName, $convertedValue['width'], $convertedValue['height']);
            } else {
                $fileName = $fileHandler->makePath($pathInformation['saveUploadPath']. DS . $fileHandler->renameFile($newPicPath, true, $key));
                $serviceLib->crop($fileName, $eachDimension['width'], $eachDimension['height'], false, -(int)$params['left'], -(int)$params['top']);
            }
        }
        $imageModel = $this->getImageModel();
        $currentBannerImage = $imageModel->getCurrentBannerPic(array('user_id' => $userId));
        if (!empty($currentBannerImage)) {
               $this->removeResizePictures($currentBannerImage['image_url'], $requireDimension, $fileHandler,ImageConfig :: ACTIVEBANNER);
        }
        $imgInfo = array(
            'image_url'=> $imageInfo['name'],
            'user_id' => $userId,
            'usages_type' => ImageUsagesType::BANNER,
        );
        if ($imageModel->saveBannerImage($imgInfo)) {
            if (!empty($params['isReuse']) && $params['isReuse'] == 2) {
                $imageHandler->createThumb($picTempPath, $userId, $configInfo, $serviceLib);
                $imageHandler->copy($picTempPath, $userId,$imageInfo['name'],array(),50);
            }
            $fileHandler->deleteDirectoryRecursively($saveTempPath);
            return $this->getResponse()->setContent(Json::encode(array(
                    'status' => 'success',
                    'html' => $this->translate('Banner successfully set.')), true)
            );
        } else {
            return $this->getResponse()->setContent(Json::encode(array(
                    'status' => 'error',
                    'html' => $this->translate('Something went wrong. Please try again.')), true)
            );
        }
    }

    public function cropSubmitAction()
    {
        if ($this->getRequest()->isXmlHttpRequest()) {
//            $params = $this->getRequest()->getPost();
            $tempModel = $this->getTempPictureModel();
            $fileHandler = new FileHandler();
            $userId = $this->getSessionContainer()->offsetGet('user_id');
            $imageInfo = $tempModel->getAll(array('user_id' => $userId, 'type' => ImageConfig::PROFILESTATUS));
            $imageInfo = current($imageInfo);
            $rootUploadPath = $fileHandler->makePath(ImageConfig::UPLOADPATH);
            $targetPath = $userId . DS . ImageConfig::TEMP;
            $saveUploadPath = $fileHandler->makePath($rootUploadPath . DS . $targetPath);
            $newTargetPath = $fileHandler->makePath($userId . DS . ImageConfig::PROFILE);
            $fileHandler->createDirectories($rootUploadPath, $newTargetPath);
            $picPath = $rootUploadPath . DS . $newTargetPath . DS . $imageInfo['name'];
            $imageName = $fileHandler->renameFile($picPath);

            $serviceLib = new ImageGD();
            $serviceLib->setFile($saveUploadPath . DS . $imageInfo['name']);
//            $serviceLib->newCropMethod($rootUploadPath . DS . $newTargetPath . DS . $imageName, $params['left'], $params['top'], $params['newWidth'], $params['newHeight']);

            $profile_pic_id = $this->getProfilePictureModel()->save(array(
                'user_id' => $userId,
                'name' => $imageName,
                'is_active' => 1
            ));

            $messageArray = array();
            if ($profile_pic_id) {
                $this->getProfileModel()->addProfilePic(array('image_id' => $profile_pic_id), $userId);
                $fileHandler->deleteDirectoryRecursively($saveUploadPath);
                $tempModel->removeTempFile($userId, ImageConfig::PROFILESTATUS);
                $messageArray['success'] = 1;
            } else {
                $messageArray['success'] = 0;
            }
            return $this->getResponse()->setContent(Json::encode($messageArray, true));
        } else {
            exit($this->translate('Direct Access is Denied.'));
        }
    }

    public function autoCropAndResizeAction()
    {
        if ($this->getRequest()->isXmlHttpRequest()) {
            $fileUpLoader = new FileUploader();
            $fileHandler = new FileHandler();
            $imageHandler = $this->getImageUtility($fileHandler);
            $configInfo = $this->getImageConfigInfo();
            $userId = $this->getSessionContainer()->offsetGet('user_id');
            $minDimension = $imageHandler->getDimension($configInfo[ImageConfig::TEMP][ImageConfig::TEMPMIN]);
            $targetPath = $userId . DS . ImageConfig::ORIGINAL ;
            $pathInformation = $imageHandler->createUploadPath($targetPath);
            $result = $imageHandler->uploadAccordingToConfig($fileUpLoader, $pathInformation['saveChunkPath'], $pathInformation['saveUploadPath'], $minDimension);

            if (empty($result['error'])) {
                $result['uploadName'] = $fileUpLoader->getUploadName();
                $imageHandler->resizeInitialWithConfig($pathInformation['saveUploadPath'] . DS . $result['uploadName'], $configInfo[ImageConfig::INITIAL_SIZE][ImageConfig::SIZE]);
                $targetPathForThumbFolders = $userId ;
                $originalImg = $pathInformation['saveUploadPath'] . DS . $result['uploadName'];
                $imageHandler->createThumb($originalImg, $targetPathForThumbFolders, $configInfo);
                $targetPathForResizeFolders = $userId ;
                $arDimension = $imageHandler->getDimension($configInfo[ImageConfig::AUTOCROP]);
                $this->multipleCropWithResize($originalImg, $targetPathForResizeFolders, $configInfo);
                $imageModel = $this->getImageModel();
                $arCurrentImageInfo = $imageModel->getCurrentProfilePic(array('user_id' => $userId));
                if (!empty($arCurrentImageInfo)) {
                    $this->removeResizePictures($arCurrentImageInfo['image_url'], $arDimension, $fileHandler);
                }
                $imgInfo = array(
                    'image_url'=> $result['uploadName'],
                    'user_id' => $userId,
                    'usages_type' => ImageUsagesType::PROFILE,
                );
                $imageModel->saveProfileImage($imgInfo);
                $this->getSessionContainer()->offsetSet('profile_image', $this->getProfileHelper()->getImage(array(
                    'user_id' => $userId,
                    'image_source' => $result['uploadName']
                )));
            }
            return $this->getResponse()->setContent(Json::encode($result, true));
        } else {
            exit($this->translate('Direct Access is Denied.'));
        }
    }

    /**
     * @param $imageSource
     * @param $targetPath
     * @param $configInfo
     */
    public function multipleCropWithResize($imageSource, $targetPath, $configInfo)
    {
        $fileHandler = new FileHandler();
        $serviceLib = new ImageGD();
        $serviceLib->setFile($imageSource);
        $saveUploadPath = $this->getImageUtility($fileHandler)->createSaveUploadPath($targetPath);
        $arDimension = $this->getImageUtility($fileHandler)->getDimension($configInfo[ImageConfig::AUTOCROP]);
        $path = $fileHandler->makePath(ImageConfig::ACTIVEPROFILE);
        $fileHandler->createDirectories($saveUploadPath, $path);
        foreach ($arDimension AS $key => $eachDimension) {
            $serviceLib->setFile($imageSource);
            $calculatedValue = $serviceLib->calculateCropRatio($serviceLib->getWidth(), $serviceLib->getHeight());
            $convertedValue = $serviceLib->resizeValues($arDimension[$key]['width'], $arDimension[$key]['height'], $calculatedValue['width'], $calculatedValue['height']);
            $fileName = $fileHandler->renameFile($imageSource, true, $key);
            $serviceLib->cropWithIntelligently($saveUploadPath . DS . $path . DS . $fileName, $calculatedValue['offsetX'], $calculatedValue['offsetY'], $convertedValue['width'], $convertedValue['height'], $calculatedValue['width'], $calculatedValue['height'], $configInfo[ImageConfig::INITIAL_SIZE][ImageConfig::QUALITY_THUMB]);
        }
    }

    private function removeResizePictures($originalImage, $arDimension, FileHandler $fileHandler = null,$usages = ImageConfig::ACTIVEPROFILE)
    {
        if (empty($originalImage)) {
            return false;
        }
        isset($fileHandler) || $fileHandler = new FileHandler();
        $rootUploadPath = $fileHandler->makePath(ImageConfig::UPLOADPATH);
        $userId = $this->getSessionContainer()->offsetGet('user_id');
        $saveUploadPath = $rootUploadPath . DS . $userId . DS . $usages;
        foreach ($arDimension AS $key => $eachDimension) {
            $fileName = $fileHandler->renameFile($saveUploadPath . DS . $originalImage, true, $key);
            $fileHandler->removeSingleFile($saveUploadPath . DS . $fileName);
        }
        return true;
    }

    public function showAlbumAction()
    {
        $request = $this->getRequest();
        if (!$request->isXmlHttpRequest()) {
            return $this->redirectForFailure('profile-home', $this->translate('Something went wrong. Please try again.'));
        }

        $params = $this->getRequest()->getPost();
        $userId = $this->getSessionContainer()->offsetGet('user_id');
        $albumInfo = $this->getAlbumModel()->getAlbumByPermalink(array('user_id' => $userId, 'permalink' => $params['permalink']));
        $result = $this->getImageModel()->getImageDetailsByAlbumId(array(
            'usages_type' => $params['usages_type'],
            'user_id' => $userId,
            'album_id' => $albumInfo['album_id']
        ));
        $targetPath = FileHandler::makePath(ImageConfig::VIEWPATH) . self::DS . $userId . self::DS . ImageConfig::THUMB . self::DS;
        return $this->getResponse()->setContent(Json::encode(array(
            'status' => 'success',
            'data' => array('result' => $result, 'path' => $targetPath)
        )));
    }

    public function profilePicRemoveAction()
    {
        if ($this->getRequest()->isXmlHttpRequest()) {
            $messageArray = array();
            $params = $this->getRequest()->getPost();
            $iProfilePicId = (int)$params['picId'];
            if (empty($iProfilePicId)) {
                $messageArray['success'] = 0;
            } else {
                $profilePicModel = $this->getProfilePictureModel();
                $fileHandler = new FileHandler();
                $userId = $this->getSessionContainer()->offsetGet('user_id');
                $arPicInfo = $profilePicModel->getById(array('user_id' => $userId, 'picId' => $iProfilePicId));
                $rootUploadPath = $fileHandler->makePath(ImageConfig::UPLOADPATH);
                $targetPath = $userId . DS . ImageConfig::PROFILE;
                $removePath = $fileHandler->makePath($rootUploadPath . DS . $targetPath);
                $this->removeThumbAndOriginalPic($arPicInfo, $removePath, $fileHandler);
                if ($profilePicModel->removeProfilePic($userId, $iProfilePicId)) {
                    $messageArray['success'] = 1;
                } else {
                    $messageArray['success'] = 0;
                }
            }
            return $this->getResponse()->setContent(Json::encode($messageArray, true));
        } else {
            exit($this->translate('Direct Access is Denied.'));
        }
    }

    private function removeThumbAndOriginalPic($arPicInfo, $removePath, FileHandler $fileHandler)
    {
        $fileHandler->removeSingleFile($removePath . DS . ImageConfig::THUMB . DS . $fileHandler->renameFile($arPicInfo['name'], true, ImageConfig::THUMB));
        $fileHandler->removeSingleFile($removePath . DS . ImageConfig::ORIGINAL . DS . $arPicInfo['name']);
    }

    public function profilePicSetAction()
    {
        $request = $this->getRequest();
        if (!$request->isXmlHttpRequest()) {
            return $this->redirectForFailure('profile-home', $this->translate('Something went wrong. Please try again.'));
        }

        $params = $this->getRequest()->getPost();
        $image_id = (int)$params['image_id'];
        if (empty($image_id)) {
            return $this->getResponse()->setContent(Json::encode(array(
                'status' => 'error',
                'html'=>$this->translate('Something went wrong. Please try again.')), true)
            );
        } else {
            $userId = $this->getSessionContainer()->offsetGet('user_id');
            $imageModel = $this->getImageModel();
            $fileHandler = new FileHandler();
            $imageHandler = $this->getImageUtility($fileHandler);
            $configInfo = $this->getImageConfigInfo();

            $imageInfo = $imageModel->getImageName($userId,$image_id);
            if (!empty($imageInfo['image_url'])) {
                $targetPath = $userId . DS . ImageConfig::ORIGINAL . DS . $imageInfo['image_url'];
                $dimension = $imageHandler->getDimension($configInfo[ImageConfig::AUTOCROP]);
                $uploadPath = $this->getImageUtility($fileHandler)->createSaveUploadPath($targetPath);
                if ($fileHandler->isFileExist($uploadPath)) {
                    if ($imgDetails = $fileHandler->getImageDetails($uploadPath)) {
                        if (!($imgDetails[0] >= $dimension[ImageConfig::PROFILE]['width'])  ||
                            !($imgDetails[1] >= $dimension[ImageConfig::PROFILE]['height'])) {
                            return $this->getResponse()->setContent(Json::encode(array(
                                    'status' => 'error',
                                    'html'=>$this->translate('Minimum dimension is (180 X 180)')), true)
                            );
                        }
                        $this->multipleCropWithResize($uploadPath, $userId, $configInfo);
                        $arCurrentImageInfo = $imageModel->getCurrentProfilePic(array('user_id' => $userId));
                        if (!empty($arCurrentImageInfo['image_url'])) {
                            $this->removeResizePictures($arCurrentImageInfo['image_url'], $dimension, $fileHandler);
                        }
                        $imgInfo = array(
                            'image_url'=> $imageInfo['image_url'],
                            'user_id' => $userId,
                            'usages_type' => ImageUsagesType::PROFILE,
                        );
                        $imageModel->saveProfileImage($imgInfo, true);
                        $this->getSessionContainer()->offsetSet('profile_image', $this->getProfileHelper()->getImage(array(
                            'user_id' => $userId,
                            'image_source' => $imageInfo['image_url']
                        )));
                        return $this->getResponse()->setContent(Json::encode(array(
                                'status' => 'success',
                                'html'=>$this->translate('Profile picture change successfully')), true)
                        );
                    }
                }
            }
            return $this->getResponse()->setContent(Json::encode(array(
                    'status' => 'error',
                    'html'=>$this->translate('Something went wrong. Please try again.')), true)
            );
        }
    }

    public function bannerPicSetAction()
    {
        if ($this->getRequest()->isXmlHttpRequest()) {
            $messageArray = array();
            $params = $this->getRequest()->getPost();
            $iBannerPicId = (int)$params['picId'];
            if (empty($iBannerPicId)) {
                $messageArray['success'] = 0;
            } else {
                $userId = $this->getSessionContainer()->offsetGet('user_id');
                if ($this->getUserBannerModel()->setBannerPic($userId, $iBannerPicId)) {
                    $messageArray['success'] = 1;
                } else {
                    $messageArray['success'] = 0;
                }
            }
            return $this->getResponse()->setContent(Json::encode($messageArray, true));
        } else {
            exit($this->translate('Direct Access is Denied.'));
        }
    }

    public function bannerPicRemoveAction()
    {
        if ($this->getRequest()->isXmlHttpRequest()) {
            $messageArray = array();
            $params = $this->getRequest()->getPost();
            $iBannerPicId = (int)$params['picId'];
            if (empty($iBannerPicId)) {
                $messageArray['success'] = 0;
            } else {
                $bannerPicModel = $this->getUserBannerModel();
                $userId = $this->getSessionContainer()->offsetGet('user_id');
                $arPicInfo = $bannerPicModel->getById(array('user_id' => $userId, 'picId' => $iBannerPicId));
                $rootUploadPath = FileHandler::makePath(ImageConfig::UPLOADPATH);
                $targetPath = $userId . DS . ImageConfig::BANNER;
                $removePath = FileHandler::makePath($rootUploadPath . DS . $targetPath);
                if (FileHandler::removeSingleFile($removePath . DS . $arPicInfo['name'])) {
                    if ($bannerPicModel->removeProfilePic($userId, $iBannerPicId)) {
                        $messageArray['success'] = 1;
                    } else {
                        $messageArray['success'] = 0;
                    }
                } else {
                    $messageArray['success'] = 0;
                }
            }
            return $this->getResponse()->setContent(Json::encode($messageArray, true));
        } else {
            exit($this->translate('Direct Access is Denied.'));
        }
    }

    public function getUploadBoxAction()
    {
        $viewModel = new ViewModel();
        $viewModel->setTerminal(true);
        return $viewModel;
    }

    public function getImageManagerAction()
    {
        $viewModel = new ViewModel();
        $viewModel->setTerminal(true);
        return $viewModel;
    }

    public function uploadBlogImageAction()
    {
        if ($this->getRequest()->isXmlHttpRequest()) {
            $params = $this->getRequest()->getPost()->toArray();
            $fileUpLoader = new FileUploader();
            $fileHandler = new FileHandler();
            $result = array();
            $imageHandler = $this->getImageUtility($fileHandler);
            $configInfo = $this->getImageConfigInfo();
            $userId = $this->getSessionContainer()->offsetGet('user_id');

            if (preg_match('/post/', $params['upload_for'])) {
                $environment = ImageConfig::POST;
            } else if (preg_match('/comment/', $params['upload_for'])) {
                $environment = ImageConfig::COMMENT;
            } else if (preg_match('/mood/', $params['upload_for'])) {
                $environment = ImageConfig::MOOD;
            } else if (preg_match('/discussion/', $params['upload_for'])) {
                $environment = ImageConfig::DISCUSSION;
            } else {
                $environment = false;
            }
            if ($environment) {
                $minDimension = $this->getImageUtility($fileHandler)->getDimension($configInfo[$environment][ImageConfig::MIN]);
                $targetPath = $userId . DS . ImageConfig::TEMP . DS . ImageConfig::ORIGINAL;
                $pathInformation = $imageHandler->createUploadPath($targetPath);
                $result = $imageHandler->uploadAccordingToConfig($fileUpLoader, $pathInformation['saveChunkPath'], $pathInformation['saveUploadPath'], $minDimension);
                if (empty($result['error'])) {
                    $result['uploadName'] = $fileUpLoader->getUploadName();
                    $imageHandler->resizeInitialWithConfig($pathInformation['saveUploadPath'] . DS . $result['uploadName'], $configInfo[$environment][ImageConfig::SIZE],$configInfo[$environment][ImageConfig::QUALITY]);
                    $thumbTargetPath = $userId . DS . ImageConfig::TEMP;
                    $originalImg = $imageHandler->createThumb($pathInformation['saveUploadPath'] . DS . $result['uploadName'], $thumbTargetPath, $configInfo);
                    $originalImg = $fileHandler->makeRelativePath($originalImg);
                    $result['success'] = true;
                    $result['url'] = str_replace('public', '', str_replace('/.', '', $originalImg));
                }
                return $this->getResponse()->setContent(Json::encode($result, true));
            } else {
                $result['error'] = true;
                $result['message'] = 'Invalid Image';
                return $this->getResponse()->setContent(Json::encode($result, true));
            }
        } else {
            exit($this->translate('Direct Access is Denied.'));
        }
    }

    public function getAllImagesAction()
    {
        if ($this->getRequest()->isXmlHttpRequest()) {
            $userId = $this->getSessionContainer()->offsetGet('user_id');
            $result = $this->processImageDetails($this->getImageModel()->getImageDetailsByUserId(array('user_id' => $userId)));
            return $this->getResponse()->setContent(Json::encode($result, true));
        } else {
            exit($this->translate('Direct Access is Denied.'));
        }
    }

    public function getUserAllAlbumsAction()
    {
        $request = $this->getRequest();
        if (!$request->isXmlHttpRequest()) {
            return $this->redirectForFailure('profile-home', $this->translate('Something went wrong. Please try again.'));
        }

        $params = $this->getRequest()->getPost();
        if (empty($params)) {
            return $this->getResponse()->setContent(Json::encode(array(
                    'status' => 'error',
                    'html' => $this->translate('Something went wrong. Please try again.')), true)
            );
        }

        $userId = $this->getSessionContainer()->offsetGet('user_id');
        $result = $this->processImageDetails($this->getImageModel()->getImageDetailsByUserId(array('user_id' => $userId)));
        $path = FileHandler::makeRelativePath(ImageConfig::VIEWPATH . self::DS . $userId . self::DS . ImageConfig::THUMB . self::DS);
        return $this->getResponse()->setContent(Json::encode(array(
            'status' => 'success',
            'html' => $result,
            'path'=>$path
        ), true));
    }

    private function processImageDetails(array $images = array())
    {
        if (empty($images))
            return array();

        $imagesContainer = array();
        $usageTypeModel = $this->getImageUsagesTypeModel();
        foreach ($images AS $image) {
            $imagesContainer[$usageTypeModel->getImageUsagesTypeById($image['usages_type'])][] = $image;
        }
        return $imagesContainer;
    }

    public function getAllAlbumAction()
    {
        if ($this->getRequest()->isXmlHttpRequest()) {
            $userId = $this->getSessionContainer()->offsetGet('user_id');
            $result = $this->getAlbumModel()->getUserAllAlbum(array('user_id' => $userId));
            return $this->getResponse()->setContent(Json::encode($result, true));
        } else {
            exit($this->translate('Direct Access is Denied.'));
        }
    }

    public function getAlbumDetailsAction()
    {
        if ($this->getRequest()->isXmlHttpRequest()) {
            $params = $this->getRequest()->getPost();
            $userId = $this->getSessionContainer()->offsetGet('user_id');
            $result = $this->getImageModel()->getImageDetailsByAlbumId(array('user_id' => $userId, 'album_id' => $params['album_id']));
            if(empty($params['for-user-wall'])) {
                return $this->getResponse()->setContent(Json::encode($result, true));
            } else {
                $path = FileHandler::makeRelativePath(ImageConfig::VIEWPATH . self::DS . $userId . self::DS . ImageConfig::THUMB . self::DS);
                return $this->getResponse()->setContent(Json::encode(array(
                    'status' => 'success',
                    'html' => array('result' => $result, 'path' => $path)
                ), true));
            }
        } else {
            exit($this->translate('Direct Access is Denied.'));
        }
    }

    public function getEachAlbumAction()
    {
        $request = $this->getRequest();
        if (!$request->isXmlHttpRequest()) {
            return $this->redirectForFailure('profile-home', $this->translate('Something went wrong. Please try again.'));
        } else if (!$this->validateUser()) {
            return $this->getResponse()->setContent(Json::encode(array('status' => 'not-logged-in'), true));
        }
        $params = $this->getRequest()->getPost();
        $userId = $this->getSessionContainer()->offsetGet('user_id');
        if($params['usages_type'] == ImageUsagesType::ALBUM) {
            $results = $this->getAlbumModel()->getUserAllAlbum(array('user_id' => $userId));
        } else {
            $imageModel = $this->getImageModel();
            $results = $imageModel->getByUsagesType(array(
                'user_id' => $userId,
                'usages_type' => $params['usages_type']
            ));
            if (!empty($params['usages_type']) && $params['usages_type'] ==  ImageUsagesType::PROFILE) {
                $currentImg = $imageModel->getCurrentProfilePic(array('user_id' => $userId));
                $images = array();
                foreach($results AS $eachImage) {
                    if ($currentImg['image_id'] == $eachImage['image_id']) {
                        $eachImage = array_merge($eachImage,array('isProfile'=>1));
                    }
                    $images[] = $eachImage;
                }
                $results =  $images;
            }
        }
        if (empty($results)) {
            return $this->getResponse()->setContent(Json::encode(array(
                    'status' => 'error',
                    'html' => $this->translate('Something went wrong. Please try again.')), true)
            );
        }

        if(empty($params['for-user-wall'])) {
            return $this->getResponse()->setContent(Json::encode($results, true));
        } else {
            $path = FileHandler::makeRelativePath(ImageConfig::VIEWPATH . self::DS . $userId . self::DS . ImageConfig::THUMB . self::DS);
            return $this->getResponse()->setContent(Json::encode(array(
                    'status' => 'success',
                    'html' => array('result' => $results, 'path' => $path)), true)
            );
        }
    }

    /**
     * @return  \BlogUser\Model\ProfilePicture
     */
    protected function getProfilePictureModel()
    {
        isset($this->profilePictureModel) || $this->profilePictureModel = $this->getServiceLocator()->get('BlogUser\Model\ProfilePicture');
        return $this->profilePictureModel;
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
     * @return  \BlogUser\Model\Album
     */
    protected function getAlbumModel()
    {
        isset($this->albumModel) || $this->albumModel = $this->getServiceLocator()->get('BlogUser\Model\Album');
        return $this->albumModel;
    }

    /**
     * @return \NBlog\Model\Image
     */
    private function getImageModel()
    {
        isset($this->imageModel) || $this->imageModel = $this->getServiceLocator()->get('NBlog\Model\Image');
        return $this->imageModel;
    }

    /**
     * @return \NBlog\Model\ImageUsagesType
     */
    private function getImageUsagesTypeModel()
    {
        isset($this->imageUsagesTypeModel) || $this->imageUsagesTypeModel = $this->getServiceLocator()->get('NBlog\Model\ImageUsagesType');
        return $this->imageUsagesTypeModel;
    }

    /**
     * @return \NBlog\Model\ImageUsages
     */
    private function getImageUsagesModel()
    {
        isset($this->imageUsagesModel) || $this->imageUsagesModel = $this->getServiceLocator()->get('NBlog\Model\ImageUsages');
        return $this->imageUsagesModel;
    }

    /**
     * @return \NBlog\Model\Profile
     */
    private function getProfileModel()
    {
        isset($this->profileModel) || $this->profileModel = $this->getServiceLocator()->get('NBlog\Model\Profile');
        return $this->profileModel;
    }
}