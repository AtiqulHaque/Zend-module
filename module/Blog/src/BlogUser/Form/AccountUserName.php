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

class AccountUserName extends BaseForm
{
    public function __construct(array $options)
    {
        parent::__construct('account-username-setting', 'form-horizontal');
        $this->setTranslator($options['translator']);
        $this->add($this->createUserNameElement());
        $this->add($this->createPasswordElement());
        $this->add($this->createSubmitButtonElement());
        $this->add($this->createCancelButtonElement());
    }

    protected function createUserNameElement()
    {
        $element = new Element('username');
        $element->setLabel('Username');
        $element->setAttributes(array(
            'type' => 'text',
            'id' => 'username',
            'placeholder' => $this->getTranslator()->translate('Username'),
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
            'id' => 'password_for_username',
            'placeholder' => $this->getTranslator()->translate('Password'),
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