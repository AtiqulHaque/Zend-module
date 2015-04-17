<?php
/**
 * Mood Entity
 *
 * @category        Entity
 * @package         BlogUser
 * @author          Md. Atiqul Haque <mailtoatiq@gmail.com>
 * @copyright       Copyright (c) 2013 Nokkhotro Blog. http://www.nokkhotroblog.com
 */
namespace BlogUser\Model\Entity;
use NBlog\Model\Entity\ServiceLocatorBase;
use Zend\InputFilter\Factory AS InputFactory;
use Zend\InputFilter\InputFilter;
use Zend\Validator\NotEmpty;

class Mood extends ServiceLocatorBase
{
    public $moodId;
    public $userId;
    public $title;
    public $createdDate;

    public function exchangeArray($data)
    {
        $this->moodId = (isset($data['mood_id'])) ? $data['mood_id'] : null;
        $this->userId = (isset($data['user_id'])) ? $data['user_id'] : null;
        $this->title = (isset($data['title'])) ? $data['title'] : null;
        $this->createdDate = (isset($data['create_date'])) ? $data['create_date'] : null;
    }

    public function getInputFilter()
    {
        if (!$this->inputFilter) {
            $inputFilter = new InputFilter();
            $factory = new InputFactory();

            $inputFilter->add($this->getMoodFilter($factory));
            $this->inputFilter = $inputFilter;
        }

        return $this->inputFilter;
    }

    protected function getMoodFilter(InputFactory $factory)
    {
        return $factory->createInput(array(
            'name' => 'title',
            'filters' => array(
                array('name' => 'NBlog\Filter\StripSlashes'),
                array(
                    'name' => 'StripTags',
                    'options' => array(
                        'allowTags' => array('br','img'),
                        'allowAttribs' => array('style', 'src', 'alt', 'title', 'width','height')
                    ),
                ),
                array('name' => 'StringTrim'),
            ),
            'validators' => array(
                array(
                    'name' => 'NotEmpty',
                    'options' => array(
                        'messages' => array(
                            NotEmpty::IS_EMPTY => $this->translate('Please enter your mood.')
                        )
                    )
                )
            )
        ));
    }
}