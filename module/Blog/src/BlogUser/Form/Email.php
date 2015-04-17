<?php
namespace BlogUser\Form;

use Zend\Form\Element;
use NBlog\Form\BaseForm;

class Email extends BaseForm
{
    public function __construct(array $options = array())
    {
        parent::__construct('Email-form','form-horizontal');
        $this->setTranslator($options['translator']);
        $this->addElements();
    }

    public function addElements()
    {
        $this->add($this->createIdElements());
        $this->add($this->createToElements());
        $this->add($this->createSubjectElements());
        $this->add($this->createBccElements());
        $this->add($this->createDetailElements());
        $this->add($this->createSubmitElements());
        $this->add($this->createCancelElements());
        $this->add($this->createDraftSubmitElements());

    }

    public function createIdElements(){
        return new Element\Hidden('email_id');
    }

    public function createToElements()
    {
        $element = new Element('to');
        $element -> setAttributes(array(
            'type' => 'text',
            'class' => 'span24',
            'id' => 'inputEmail',
            'placeholder' => ' ইমেইল ঠিকানা'
        ));
        return $element;
    }

    public function createSubjectElements()
    {
        $element = new Element('subject');
        $element ->setAttributes(array(
           'type' => 'text',
            'class' => 'span24',
            'id' => 'inputSubject',
            'placeholder' => 'বিষয়'
        ));
        return $element;
    }

    public function createBccElements()
    {
        $element = new Element('bcc');
        $element ->setAttributes(array(
            'type' => 'text',
            'class' => 'span24',
            'id' => 'inputBcc'
        ));
        return $element;

    }

    public function createDetailElements()
    {
        $element = new Element('message');
        $element ->setAttributes(array(
            'type' => 'textarea',
            'class' => 'span24 pull-left',
            'id' => 'inputSubject',
            'placeholder' =>$this->getTranslator()->translate('subject')
        ));
        return $element;
    }

    public function createDraftSubmitElements()
    {
        $element = new Element\Button('draft');
        $element->setAttributes(array(
            'class' => 'btn btn-small btn-primary',
            'value' => 'Draft'
        ));

        return $element;
    }

    public function createSubmitElements()
    {
        $element = new Element\Button('submit');
        $element->setAttributes(array(
            'class' => 'btn btn-small btn-primary',
            'value' => $this->getTranslator()->translate('send')
        ));

        return $element;
    }

    public function createCancelElements()
    {
        $element = new Element\Button('cancel');
        $element->setLabel(' বাতিল করুন');
        $element->setAttributes(array(
            'class' => 'btn btn-small',
        ));

        return $element;
    }
}