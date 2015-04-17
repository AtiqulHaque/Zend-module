<?php
/**
 * Account Email Change
 *
 * @category        Form
 * @package         BlogUser
 * @author          Md.Atiqul Haque <md_atiqulhaque@yahoo.com>
 * @copyright       Copyright (c) 2012 Nokkhotro Blog. http://www.nokkhotroblog.com
 */
namespace BlogUser\Form;

use NBlog\Form\BaseForm;
use Zend\Form\Element;

class AccountEmail extends BaseForm
{
    public function __construct(array $options)
    {
        parent::__construct('account-email-change', 'form-horizontal');
        $this->setTranslator($options['translator']);
        $this->add($this->createChangeEmailElement());
        $this->add($this->createOldEmailElement());
        $this->add($this->createCancelButtonElement());
    }

    protected function createOldEmailElement()
    {
        $element = new Element('email');
        $element->setLabel('Current email');
        $element->setAttributes(array(
            'type' => 'text',
            'id' => 'email',
            'readonly' => 'false',
            'placeholder' => $this->getTranslator()->translate('Old email'),
            'class' => 'span9 form-control'
        ));

        return $element;
    }

    protected function createChangeEmailElement()
    {
        $element = new Element('new_email');
        $element->setLabel('New email');
        $element->setAttributes(array(
            'type' => 'text',
            'id' => 'new_email',
            'placeholder' => $this->getTranslator()->translate('New email'),
            'class' => 'span9 form-control'
        ));

        return $element;
    }


    protected function createSubmitButtonElement()
    {
        return array(
            'name' => 'change-type',
            'attributes' => array(
                'type' => 'submit',
                'value' => 'password-change',
                'class' => 'btn btn-primary'
            )
        );
    }

    protected function createCancelButtonElement()
    {
        return array(
            'name' => 'cancel',
            'attributes' => array(
                'type' => 'button',
                'value' => 'Cancel',
                'class' => 'btn primary'
            )
        );
    }

}