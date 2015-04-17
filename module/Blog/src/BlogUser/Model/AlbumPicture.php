<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Atik
 * Date: 5/13/13
 * Time: 12:54 PM
 * To change this template use File | Settings | File Templates.
 */

namespace BlogUser\Model;

use NBlog\Model\ImageBase;
use NBlog\Model\ImageUsagesType;

class AlbumPicture extends ImageBase
{
    /**
     * @var \BlogUser\Model\Dao\AlbumPicture
     */
    protected $dao = null;

    /**
     * @param       $albumId
     * @return      array
     */
    public function getAlbumPicByAlbumId($albumId)
    {
        if (empty($albumId)) {
            return array();
        }
        return $this->dao->getAlbumPicByAlbumId(array('album_id' => $albumId));
    }

    public function countAllByAlbumId($albumId)
    {
        if (empty($albumId)) {
            return 0;
        }
        return $this->dao->countAllByAlbumId(array('album_id' => $albumId));
    }

    public function getAlbumPic($albumId, $limit)
    {
        if (empty($albumId)) {
            return array();
        }

        return $this->dao->getAlbumPic(array('album_id' => $albumId), $limit);
    }

    public function countAlbumImage($options)
    {
        if (empty($options)) {
            return array();
        }

        return $this->dao->countAlbumImage($options);
    }

    public function save(array $data)
    {
        if (empty($data)) {
            return false;
        }
        $data['created'] = $this->getCurrentDateTime();

        if ($this->dao->save($data)) {
            $data['usages_type'] = ImageUsagesType::ALBUM;
            $data['image_url'] = $data['name'];
            $data['id_of_image_for'] = $data['album_id'];
            return $this->saveUniqueImage($data);
        } else {
            return false;
        }
    }
}