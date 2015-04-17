<?php
/**
 * Episode Entity
 * @category        Entity
 * @package         BlogUser
 * @author          Mohammad Faisal Ahmed <faisal.ahmed0001@gmail.com>
 * @author          Md. Atiqul Haque <mailtoatiq@gmail.com>
 * @copyright       Copyright (c) 2012 Nokkhotro Blog. http://www.nokkhotroblog.com
 */
namespace BlogUser\Model\Entity;
use NBlog\Model\Entity\ServiceLocatorBase;
use Zend\InputFilter\Factory as InputFactory;
use Zend\InputFilter\InputFilter;
use Zend\Validator\Digits;
use Zend\Validator\NotEmpty;

class Episode extends ServiceLocatorBase
{
    public $episodeId;
    public $title;
    public $episodicStyle;
    public $isPublished;
    public $createdDate;

    public function exchangeArray($data)
    {
        $this->episodeId = (isset($data['episode_id'])) ? $data['episode_id'] : null;
        $this->title = (isset($data['title'])) ? $data['title'] : null;
        $this->episodicStyle = (isset($data['episodic_style'])) ? $data['episodic_style'] : null;
        $this->isPublished = (isset($data['isPublished'])) ? $data['isPublished'] : null;
        $this->createdDate = (isset($data['create_date'])) ? $data['create_date'] : null;
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
                                'isEmpty' => $this->translate('Please enter episode title.')
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
                'name' => 'episodic_style_id',
                'required' => true,
                'validators' => array(
                    array(
                        'name' => 'NotEmpty',
                        'options' => array(
                            'messages' => array(
                                NotEmpty::IS_EMPTY => $this->translate('Please select a style for the episode.')
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

            $this->inputFilter = $inputFilter;
        }

        return $this->inputFilter;
    }
}