<?php
/**
 * Notice Model
 *
 * @category        Model
 * @package         BlogUser
 * @author          Md.Atiqul Haque <md_atiqulhaque@yahoo.com>
 * @author          Md. Atiqul Haque <mailtoatiq@gmail.com>
 * @copyright       Copyright (c) 2013 Nokkhotro Blog. http://www.nokkhotroblog.com
 */
namespace BlogUser\Model;

use NBlog\Model\AlbumConfig;
use NBlog\Model\ImageDB;
//use BlogUser\Model\Album AS AlbumModel;
use NBlog\Model\ImageUsagesType;

class Album extends ImageDB
{
    /**
     * @var \BlogUser\Model\Dao\Album
     */
    protected $dao = null;

    /**
     * @param array $options
     * @return mixed
     */
    public function getByAlbumId(array $options = array())
    {
        return $this->dao->getById($options);
    }

    public function countAllByAlbumName(array $options = array())
    {
        return $this->dao->countAllByAlbumName($options);
    }

    public function getAlbumByPermalink(array $options = array())
    {
        if(empty($options['permalink']) || empty($options['user_id'])) {
            return array();
        }
        return $this->dao->getAlbumByPermalink($options);
    }

    public function getUserAllAlbum(array $options = array())
    {
        if (empty($options)) {
            return array();
        }
        return $this->dao->getUserAllAlbum($options);
        }

    public function allPicturesForProfile(array $options = array())
    {
        $options = array_merge($options,array(
            'group_by'=>'image_usages.usages_type,image_usages.album_id',
            'order_by'=>'image_usages.usages_type DESC',
            'limit'=> ImageUsagesType::REQUIRED_IMAGE));
        $images = $this->getImageModel()->getImageDetailsByUserId($options);
        if (empty($images)) {
            return array();
        } else if (($total_image = count($images))  == ImageUsagesType::REQUIRED_IMAGE) {
            return $images;
        } else if ($total_image  < ImageUsagesType::REQUIRED_IMAGE) {
            $required_image = ceil($total_image / ImageUsagesType::REQUIRED_IMAGE);
            $required_loop  = ImageUsagesType::REQUIRED_IMAGE - $total_image ;
            $imagesContainer =  $images;
            $pending = 0;
            foreach ($images AS $image) {
                $container = explode(',',$image['images_urls']);
                if(($check_images = count($container)) > $required_image) {
                    if($pending) {
                        $pending += $required_image;
                        $limit = $pending;
                    } else {
                        $limit = $required_image;
                    }
                    $img = array_slice($container,0,$limit);$i = 0;
                    foreach($img AS $im) {
                        $i++;
                        if($required_loop == 0) break;
                        /*if($im == $image['image_url']) {
                            $pending += 1;
                            continue;
                        }*/
                        $imagesContainer[] = array(
                            'usages_type'  =>   $image['usages_type'],
                            'album_id'     =>   $image['album_id'],
                            'image_url'    =>   $im,
                            'user_id'      =>   $image['user_id']
                        );
                        $required_loop --;
                    }
                    if($i < $limit) {
                        $pending += $i - $limit;
                    } else {
                        $pending = 0;
                    }
                } else {
                    $pending += $required_image;
                }
                if($required_loop == 0) break;
            }
            return $imagesContainer;
        } else {
            return array();
        }
    }

    public function countImagesForProfile(array $options = array())
    {
        //$objImageModel = new Image($this->controller);
        $userImageCount = $this->getAlbumPictureModel()->countAlbumImage($options);// + $objImageModel->totalImages($options);
        return $userImageCount;
    }

    public function save(array $data)
    {
        if (empty($data)) {
            return false;
        }
        $data['created'] = $this->getCurrentDateTime();
        $data['permalink'] = md5($data['user_id'] . AlbumConfig::ALBUMHASHKEY . $data['album_name']);
        $data['type'] = AlbumConfig::ALBUM;
        return $this->dao->save($data);
    }

    public function updateTotalPic(array $data, $albumId)
    {
        return $this->dao->updateTotalPic($data, $albumId);
    }

    /**
     * @return  \BlogUser\Model\AlbumPicture
     */
    private function getAlbumPictureModel()
    {
        isset($this->imageUsagesModel) || $this->imageUsagesModel = $this->serviceManager->get('BlogUser\Model\AlbumPicture');
        return $this->imageUsagesModel;
    }
}