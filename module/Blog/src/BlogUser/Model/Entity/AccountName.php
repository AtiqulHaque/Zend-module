<?php

/**
 * Account Name Change Entity
 *
 * Used for dealing with the Account Change info.
 *
 * @category        Entity
 * @package         BlogUser
 * @author          Md.Atiqul Haque <md_atiqulhaque@yahoo.com>
 * @copyright       Copyright (c) 2012 Nokkhotro Blog. http://www.nokkhotroblog.com
 */
namespace BlogUser\Model\Entity;

use NBlog\Model\Entity\ServiceLocatorBase;
use Zend\InputFilter\Factory as InputFactory;
use Zend\InputFilter\InputFilter;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Validator\Hostname;
use Zend\Validator\NotEmpty;

class AccountName extends ServiceLocatorBase
{
    public function __construct(ServiceLocatorInterface $serviceManager)
    {
        parent::__construct($serviceManager);
        $this->dbAdapter = $this->serviceManager->get('user-db-driver');
    }

    public function getInputFilter()
    {
        if (!$this->inputFilter) {
            $factory = new InputFactory();
            $this->inputFilter = $this->getNameInfoFilter($factory);
        }

        return $this->inputFilter;
    }

    private function getNameInfoFilter(InputFactory $factory)
    {
        $inputFilter = new InputFilter();
        $inputFilter->add($factory->createInput(array(
            'name' => 'first_name',
            'required' => true,
            'filters' => array(
                array('name' => 'NBlog\Filter\StripSlashes'),
                array('name' => 'StripTags'),
                array('name' => 'StringTrim'),
            )
        )));

        $inputFilter->add($factory->createInput(array(
            'name' => 'middle_name',
            'required' => false,
            'filters' => array(
                array('name' => 'NBlog\Filter\StripSlashes'),
                array('name' => 'StripTags'),
                array('name' => 'StringTrim'),
            )
        )));

        $inputFilter->add($factory->createInput(array(
            'name' => 'last_name',
            'required' => true,
            'filters' => array(
                array('name' => 'NBlog\Filter\StripSlashes'),
                array('name' => 'StripTags'),
                array('name' => 'StringTrim'),
            ),
            'validators' => array(
                array(
                    'name' => 'StringLength',
                    'options' => array(
                        'encoding' => 'UTF-8',
                        'min' => 1,
                        'max' => 100,
                    ),
                )
            )
        )));

        $inputFilter->add($factory->createInput(array(
            'name' => 'nickname',
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
                            NotEmpty::IS_EMPTY => $this->translate('Please select nick name.')
                        )
                    )
                )
            )
        )));

        $inputFilter->add($factory->createInput(array(
            'name' => 'old_password',
            'required' => true,
            'filters' => array(
                array('name' => 'StripTags'),
                array('name' => 'StringTrim'),
            ),
            'validators' => array(
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

        return $inputFilter;
    }
}