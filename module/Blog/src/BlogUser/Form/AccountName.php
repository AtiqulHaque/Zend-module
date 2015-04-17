<?php
/**
 * Account Setting Form
 *
 * @category        Form
 * @package         BlogUser
 * @author          Md.Atiqul Haque <md_atiqulhaque@yahoo.com>
 * @copyright       Copyright (c) 2012 Nokkhotro Blog. http://www.nokkhotroblog.com
 */
namespace BlogUser\Form;

use NBlog\Form\BaseForm;
use Zend\Form\Element;

class AccountName extends BaseForm
{
    public function __construct(array $options)
    {
        parent::__construct('account-name-setting', 'form-horizontal');
        $this->setTranslator($options['translator']);
        $this->add($this->createFirstNameElement());
        $this->add($this->createLastNameElement());
        $this->add($this->createMiddleNameElement());
        $this->add($this->createNickNameElement());
        $this->add($this->createPasswordElement());
        $this->add($this->createSubmitButtonElement());
        $this->add($this->createCancelButtonElement());
    }

    protected function createFirstNameElement()
    {
        $element = new Element('first_name');
        $element->setLabel('First Name');
        $element->setAttributes(array(
            'type' => 'text',
            'id' => 'first_name',
            'placeholder' => $this->getTranslator()->translate('First name'),
            'class' => 'span9 form-control'
        ));

        return $element;
    }

    protected function createLastNameElement()
    {
        $element = new Element('last_name');
        $element->setLabel('Last Name');
        $element->setAttributes(array(
            'type' => 'text',
            'id' => 'last_name',
            'placeholder' => $this->getTranslator()->translate('Last Name'),
            'class' => 'span9 form-control'
        ));

        return $element;
    }

    protected function createMiddleNameElement()
    {
        $element = new Element('middle_name');
        $element->setLabel('Middle Name');
        $element->setAttributes(array(
            'type' => 'text',
            'id' => 'middle_name',
            'placeholder' => $this->getTranslator()->translate('Middle Name'),
            'class' => 'span9 form-control'
        ));

        return $element;
    }

    protected function createNickNameElement()
    {
        $element = new Element('nickname');
        $element->setLabel('Display Name');
        $element->setAttributes(array(
            'type' => 'text',
            'id' => 'nickname',
            'placeholder' => $this->getTranslator()->translate('Display Name'),
            'class' => 'span9 form-control'
        ));

        return $element;
    }


    protected function createPasswordElement()
    {
        $element = new Element('old_password');
        $element->setLabel('Password');
        $element->setAttributes(array(
            'type' => 'password',
            'id' => 'old_password',
            'placeholder' => $this->getTranslator()->translate('Password'),
            'class' => 'span9 form-control'
        ));

        return $element;
    }

    protected function createSubmitButtonElement()
    {
        return array(
            'name' => 'submit',
            'attributes' => array(
                'type' => 'button',
                'value' => $this->getTranslator()->translate('Submit'),
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
                'class' => 'btn primary',
                'onClick' => 'window.location="/profile"; return false;'
            )
        );
    }

}