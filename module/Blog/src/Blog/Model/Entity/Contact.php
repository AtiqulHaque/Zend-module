<?php
namespace Blog\Model\Entity;

use NBlog\Model\Entity\ServiceLocatorBase;
use Zend\InputFilter\Factory AS InputFactory;
use Zend\InputFilter\InputFilter;
use Zend\Validator\Digits;
use Zend\Validator\NotEmpty;

/**
 * Contact Entity
 *
 * @category        Entity
 * @package         Blog
 * @author          Md. Atiqul Haque <mailtoatiq@gmail.com>
 * @author          Md. Atiqul Haque <mailtoatiq@gmail.com>
 * @copyright       Copyright (c) 2014 Nokkhotro Blog. http://www.nokkhotroblog.com
 */
class Contact extends ServiceLocatorBase
{
    public function getInputFilter()
    {
        if (!$this->inputFilter) {
            $inputFilter = new InputFilter();
            $factory = new InputFactory();

            $inputFilter->add($factory->createInput(array(
                'name' => 'reason',
                'validators' => array(
                    array(
                        'name' => 'NotEmpty',
                        'options' => array(
                            'messages' => array(
                                NotEmpty::IS_EMPTY => $this->translate('Please select a reason.')
                            )
                        )
                    )
                )
            )));

            $inputFilter->add($factory->createInput(array(
                'name' => 'subject',
                'required' => true,
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
                                NotEmpty::IS_EMPTY => $this->translate('Please enter the subject of contact for.'),
                            )
                        ),
                        'break_chain_on_failure' => true
                    ),
                    array(
                        'name' => 'StringLength',
                        'options' => array(
                            'encoding' => 'UTF-8',
                            'min' => 1,
                            'max' => 100,
                        ),
                    ),
                ),
            )));

            $inputFilter->add($factory->createInput(array(
                'name' => 'description',
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
                                NotEmpty::IS_EMPTY => $this->translate('Please enter description of contact.'),
                            )
                        )
                    )
                ),
            )));

            $inputFilter->add($factory->createInput(array(
                'name' => 'recaptcha_response_field',
                'validators' => array(
                    array(
                        'name' => 'NotEmpty',
                        'options' => array(
                            'messages' => array(
                                NotEmpty::IS_EMPTY => $this->translate('Please enter the above captcha.')
                            )
                        )
                    )
                )
            )));

            $inputFilter->add($factory->createInput(array(
                'name' => 'agree',
                'required' => true,
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
                                NotEmpty::IS_EMPTY => $this->translate('You must agree to the terms of use.')
                            ),
                        ),
                        'break_chain_on_failure' => true
                    ),
                    array(
                        'name' => 'Digits',
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
