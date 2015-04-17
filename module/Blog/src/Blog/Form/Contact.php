<?php
namespace Blog\Form;

use NBlog\Form\BaseForm;
use Zend\Form\Element;

/**
 * Contact Form
 *
 * @category        Form
 * @package         Blog
 * @author          Md. Atiqul Haque <mailtoatiq@gmail.com>
 * @author          Md. Atiqul Haque <mailtoatiq@gmail.com>
 * @copyright       Copyright (c) 2014 Nokkhotro Blog. http://www.nokkhotroblog.com
 */
class Contact extends BaseForm
{
    public function __construct($options = array())
    {
        parent::__construct('contact-us', 'registration');
        $this->setUseInputFilterDefaults(false);

        $this->setTranslator($options['translator']);
        $this->add($this->createReasonElement($options['reasons']));
        $this->add($this->createSubjectElement());
        $this->add($this->createDetailElement());
        $this->add($this->createCaptchaElement());
        $this->add($this->createTermConditionElement());
        $this->add($this->createSubmitElement());
        $this->add($this->createGoHomeElement());
    }

    protected function createReasonElement($reasons)
    {
        $element = new Element\Select('reason');
        $element->setLabel('Reason of Contact');
        $element->setEmptyOption('Reason');
        $element->setValueOptions($reasons);
        $element->setAttribute('class', 'form-control');
        return $this->addControlLabelAttribute($element);
    }

    protected function createSubjectElement()
    {
        $element = new Element\Text('subject');
        $element->setLabel('Subject of Contact');
        $element->setAttributes(array(
            'id' => 'subject',
            'class' => 'form-control',
            'placeholder' => $this->translate('Subject of Contact')
        ));

        return $this->addClassForKeyboardLayout($element);
    }

    protected function createDetailElement()
    {
        $element = new Element\Textarea('description');
        $element->setLabel('Message');
        $element->setAttributes(array(
            'id' => 'description',
            'class' => 'form-control',
            'rows' => 7,
            'cols' => 100,
            'placeholder' => $this->translate('Write Detail')
        ));

        return $this->addClassForKeyboardLayout($element);
    }

    protected function createCaptchaElement()
    {
        $element = new Element\Text('recaptcha_response_field');
        return $element->setAttributes(array(
            'id' => 'recaptcha_response_field',
            'class' => 'form-control',
            'placeholder' => $this->translate('Write the above captcha')
        ));
    }

    protected function createTermConditionElement()
    {
        $element = new Element\Checkbox('agree');
        $element->setLabel('I agree with the term and conditions.');
        $element->setUseHiddenElement(false)
            ->setCheckedValue(1)
            ->setAttribute('id', 'agree');

        return $element;
    }

    protected function createSubmitElement()
    {
        $element = new Element\Submit('submit');
        $element->setValue('Submit');
        return $element->setAttributes(array(
            'id' => 'sign-up-button',
            'class' => 'btn btn-primary'
        ));
    }

    protected function createGoHomeElement()
    {
        $element = new Element\Button('cancel');
        $element->setLabel($this->translate('Go Home'));
        return $element->setAttributes(array(
            'class' => 'btn btn-inverse',
            'onclick' => 'window.location="/home"; return false;'
        ));
    }
}