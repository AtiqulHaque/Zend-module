<?php

/**
 * Account Email Change Entity
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

class AccountEmail extends ServiceLocatorBase
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
            $this->inputFilter = $this->getUserEmailInfoFilter($factory);
        }

        return $this->inputFilter;
    }

    private function getUserEmailInfoFilter(InputFactory $factory)
    {
        $inputFilter = new InputFilter();
        $inputFilter->add($factory->createInput(array(
            'name' => 'email',
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
                        'min' => 5,
                        'max' => 120,
                    ),
                ),
            ),
        )));

        $inputFilter->add($factory->createInput(array(
            'name' => 'new_email',
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
                        'min' => 7,
                        'max' => 120,
                    ),
                ),
                array('name' => 'EmailAddress'),
            ),
        )));

        return $inputFilter;
    }
}