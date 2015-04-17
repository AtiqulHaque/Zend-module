<?php
/**
 * Discussion Entity
 *
 * @category        Entity
 * @package         BlogUser
 * @author          Md. Atiqul Haque <mailtoatiq@gmail.com>
 * @copyright       Copyright (c) 2012 Nokkhotro Blog. http://www.nokkhotroblog.com
 */
namespace BlogUser\Model\Entity;

use NBlog\Model\Entity\ServiceLocatorBase;
use Zend\InputFilter\Factory as InputFactory;
use Zend\InputFilter\InputFilter;
use Zend\Validator\Digits;
use Zend\Validator\NotEmpty;

class Discussion extends ServiceLocatorBase
{
    public $discussionId;
    public $title;
    public $details;
    public $categories = array();
    public $status;
    public $createdDate;
    public $modifiedDate;

    public function exchangeArray($data)
    {
        $this->discussionId = (isset($data['discussion_id'])) ? $data['discussion_id'] : null;
        $this->title = (isset($data['title'])) ? $data['title'] : null;
        $this->details = (isset($data['details'])) ? $data['details'] : null;
        $this->categories = (isset($data['categories'])) ? $data['categories'] : array();
        $this->status = (isset($data['status'])) ? $data['status'] : null;
        $this->createdDate = (isset($data['created'])) ? $data['created'] : null;
        $this->modifiedDate = (isset($data['modified'])) ? $data['modified'] : null;
    }

    public function getInputFilter()
    {
        if (!$this->inputFilter) {
            $inputFilter = new InputFilter();
            $factory = new InputFactory();

            $inputFilter->add($factory->createInput(array(
                'name' => 'title',
                'filters' => array(
                    array('name' => 'NBlog\Filter\StripSlashes'),
                    array('name' => 'StripTags'),
                    array('name' => 'StringTrim'),
                ),
                'validators' => array(
                    array(
                        'name' => 'NotEmpty',
                        'options' => array(
                            'messages' => array(
                                NotEmpty::IS_EMPTY => $this->translate('Please enter discussion title.')
                            )
                        )
                    )
                )
            )));

            $inputFilter->add($factory->createInput(array(
                'name' => 'category_id',
                'required' => true,
                'validators' => array(
                    array(
                        'name' => 'NotEmpty',
                        'options' => array(
                            'messages' => array(
                                NotEmpty::IS_EMPTY => $this->translate('Please select a category of the discussion.')
                            )
                        )
                    )
                )
            )));

            $inputFilter->add($factory->createInput(array(
                'name' => 'details',
                'filters' => array(
                    array('name' => 'NBlog\Filter\StripSlashes'),
                    array(
                        'name' => 'StripTags',
                        'options' => array(
                            'allowTags' => array(
                                'p', 'strong', 'em', 'span', 'sub', 'sup', 'a', 'br', 'hr', 'img',
                                'table', 'thead', 'th', 'tbody', 'tr', 'td', 'tfoot',
                            ),
                            'allowAttribs' => array(
                                'style', 'src', 'alt', 'title', 'width', 'height', 'href', 'target', 'border'
                            )
                        )
                    ),
                    array('name' => 'StringTrim'),
                ),
                'validators' => array(
                    array(
                        'name' => 'NotEmpty',
                        'options' => array(
                            'messages' => array(
                                NotEmpty::IS_EMPTY => $this->translate('Please enter discussion details.')
                            )
                        )
                    )
                )
            )));

            $inputFilter->add($factory->createInput(array(
                'name' => 'agree',
                'required' => true,
                'validators' => array(
                    array(
                        'name' => 'Digits',
                        'break_chain_on_failure' => true,
                        'options' => array(
                            'messages' => array(
                                Digits::NOT_DIGITS => $this->translate('You must agree to the terms of use.')
                            )
                        )
                    )
                )
            )));

            $inputFilter->add($factory->createInput(array(
                'name' => 'status',
                'filters' => array(
                    array('name' => 'NBlog\Filter\StripSlashes'),
                    array('name' => 'StripTags'),
                    array('name' => 'StringTrim'),
                ),
                'validators' => array(
                    array(
                        'name' => 'NotEmpty',
                        'options' => array(
                            'messages' => array(
                                NotEmpty::IS_EMPTY => $this->translate('Please select any status.')
                            )
                        )
                    )
                )
            )));

            $this->inputFilter = $inputFilter;
        }

        return $this->inputFilter;
    }
}