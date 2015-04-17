<?php
/**
 * Mood Form
 *
 * @category        Form
 * @package         BlogUser
 * @author          Md. Atiqul Haque <mailtoatiq@gmail.com>
 * @copyright       Copyright (c) 2013 Nokkhotro Blog. http://www.nokkhotroblog.com
 */
namespace BlogUser\Form;

use NBlog\Form\BaseForm;
use NBlog\Model\WritingStatus;
use Zend\Form\Element;

class Mood extends BaseForm
{
    public function __construct(array $options)
    {
        parent::__construct('mood-form');
        $this->setTranslator($options['translator']);

        $this->add($this->createMoodIdElement());
        $this->add($this->createCurrentMoodElement());
        $this->add($this->createStatusElement($options['statuses']));
        $this->add($this->createSubmitButtonElement());
    }

    protected function createMoodIdElement()
    {
        $element = new Element('mood_id');
        $element->setAttributes(array(
            'type' => 'hidden'
        ));

        return $element;
    }

    protected function createCurrentMoodElement()
    {
        $element = new Element\Textarea('title');
        $element->setAttributes(array(
            'id' => 'current_mood',
            'class' => 'span14',
            'rows' => 3,
            'cols' => 63,
            'placeholder' => $this->getTranslator()->translate('About Your Mood')
        ));

        return $this->addClassForKeyboardLayout($element);
    }

    protected function createStatusElement(array $statuses)
    {
        $element = new Element\Select('status');
        $element->setAttributes(array(
            'class' => 'span5 select-picker form-control'
        ));

        $element->setValueOptions($statuses);
        $element->setValue(WritingStatus::PUBLISHED);
        return $element;
    }

    protected function createSubmitButtonElement()
    {
        return array(
            'name' => 'submit',
            'attributes' => array(
                'type' => 'submit',
                'value' => $this->getTranslator()->translate('Submit'),
                'id' => 'add_status',
                'class' => 'btn btn-black btn-xs'
            ),
        );
    }
}