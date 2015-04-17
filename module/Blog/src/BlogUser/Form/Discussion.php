<?php
/**
 * Discussion Form
 *
 * @category        Form
 * @package         BlogUser
 * @author          Md. Atiqul Haque <mailtoatiq@gmail.com>
 * @copyright       Copyright (c) 2012 Nokkhotro Blog. http://www.nokkhotroblog.com
 */
namespace BlogUser\Form;

use NBlog\Form\BaseForm;
use Zend\Form\Element;
use NBlog\Model\WritingStatus;

class Discussion extends BaseForm
{
    public function __construct(array $options)
    {
        parent::__construct('discussion-form');
        $this->setTranslator($options['translator']);

        $this->add($this->createDiscussionIdElement());
        $this->add($this->createTitleElement());
        $this->add($this->createCategoryElement($options['categories']));
        $this->add($this->createDescriptionElement());
        $this->add($this->createTermConditionElement());
        $this->add($this->createStatusElement($options['statuses']));
        $this->add($this->createSubmitButtonElement());
        $this->add($this->createCancelButtonElement());
    }

    protected function createDiscussionIdElement()
    {
        return new Element\Hidden('discussion_id');
    }

    protected function createTitleElement()
    {
        $element = new Element\Text('title');
        $element->setLabel('Main Topic');
        $element->setAttributes(array(
            'id' => 'title',
            'class' => 'input-xxlarge',
            'placeholder' => $this->getTranslator()->translate('Main Topic')
        ));

        return $this->addClassForKeyboardLayout($element);
    }

    protected function createCategoryElement($categories)
    {
        $element = new Element\MultiCheckbox('category_id');
        $element->setLabel('Category');
        $element->setAttributes(array(
            'class' => 'span3'
        ));

        $element->setValueOptions($categories);
        return $element;
    }

    protected function createDescriptionElement()
    {
        $element = new Element\Textarea('details');
        $element->setLabel('Description');
        $element->setAttributes(array(
            'id' => 'details',
            'class' => 'input-block-level discussion',
            'rows' => 16,
            'cols' => 100,
            'placeholder' => $this->getTranslator()->translate('Start The Writing...')
        ));

        return $this->addClassForKeyboardLayout($element);
    }

    protected function createStatusElement(array $statuses)
    {
        $element = new Element\Select('status');
        $element->setAttributes(array(
            'class' => 'span5 select-picker'
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
                'value' => $this->getTranslator()->translate('Send'),
                'class' => 'btn btn-primary'
            ),
        );
    }

    protected function createCancelButtonElement()
    {
        $element = new Element\Button('cancel');
        $element->setLabel('Cancel');
        $element->setAttributes(array(
            'class' => 'btn btn-link'
        ));

        return $element;
    }
}