<?php

/**
 * Report Entity
 * @category        Entity
 * @package         BlogUser
 * @author          Md. Atiqul Haque <mailtoatiq@gmail.com>
 * @copyright       Copyright (c) 2012 Nokkhotro Blog. http://www.nokkhotroblog.com
 */
namespace BlogUser\Model\Entity;

use NBlog\Model\Entity\ServiceLocatorBase;
use Zend\InputFilter\Factory as InputFactory;
use Zend\InputFilter\InputFilter;

class Report extends ServiceLocatorBase
{
    public $userId;
    public $messageId;

    public function exchangeArray($data)
    {
        $this->messageId = (isset($data['message_id'])) ? $data['message_id'] : null;
    }

    public function getInputFilter()
    {
        if (!$this->inputFilter) {
            $inputFilter = new InputFilter();
            $factory = new InputFactory();

            $inputFilter->add($factory->createInput(array(
                'name' => 'message_id',
                'filters' => array(
                    array('name' => 'HtmlEntities'),
                    array('name' => 'StringTrim'),
                ),
                'validators' => array(
                    array(
                        'name' => 'NotEmpty',
                        'options' => array(
                            'messages' => array(
                                'isEmpty' => $this->translate('Please select your report message.')
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