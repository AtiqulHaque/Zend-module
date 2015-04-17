<?php
/**
 * Password Change Form
 *
 * @category        Form
 * @package         BlogUser
 * @author          Md. Atiqul Haque <mailtoatiq@gmail.com>
 * @copyright       Copyright (c) 2012 Nokkhotro Blog. http://www.nokkhotroblog.com
 */
namespace BlogUser\Form;

use NBlog\Form\BaseForm;
use Zend\Form\Element;

class AccountPassword extends BaseForm
{
    public function __construct($options = array())
    {
        parent::__construct('user-password-form', 'form-horizontal');
        $this->setTranslator($options['translator']);
        $this->addPasswordInfoFieldSet();
        $this->add(array(
            'name' => 'change-type',
            'attributes' => array(
                'type' => 'submit',
                'value' => 'password-change',
                'id' => 'password-change-button',
                'class' => 'btn btn-info'
            )
        ));
        $this->add(array(
            'name' => 'cancel',
            'attributes' => array(
                'type' => 'button',
                'value' => 'Cancel',
                'class' => 'btn close-collapse'
            )
        ));
    }

    protected function addPasswordInfoFieldSet()
    {
        $this->add($this->createPasswordElement());
        $this->add($this->createNewPasswordElement());
        $this->add($this->createDoublePasswordElement());
        return $this;
    }

    protected function createPasswordElement()
    {
        $element = new Element('old_password');
        $element->setLabel('Old Password');
        $element->setAttributes(array(
            'type' => 'password',
            'class' => 'form-control',
            'id' => 'old_passwords',
            'placeholder' => $this->getTranslator()->translate('Old Password')
        ));

        return $element;
    }

    protected function createNewPasswordElement()
    {
        $element = new Element('password');
        $element->setLabel('New Password');
        $element->setAttributes(array(
            'type' => 'password',
            'class' => 'form-control',
            'id' => 'password',
            'placeholder' => $this->getTranslator()->translate('New Password')
        ));

        return $element;
    }

    protected function createDoublePasswordElement()
    {
        $element = new Element('double_password');
        $element->setLabel('Confirm Password');
        $element->setAttributes(array(
            'type' => 'password',
            'class' => 'form-control',
            'id' => 'double_password',
            'placeholder' => $this->getTranslator()->translate('Confirm Password')
        ));

        return $element;
    }
}