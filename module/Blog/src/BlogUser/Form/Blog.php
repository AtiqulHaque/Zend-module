<?php
/**
 * Blog Form
 *
 * @category        Form
 * @package         BlogUser
 * @author          Md. Atiqul Haque <mailtoatiq@gmail.com>
 * @author          Mohammad Faisal Ahmed <faisal.ahmed0001@gmail.com>
 * @copyright       Copyright (c) 2012 Nokkhotro Blog. http://www.nokkhotroblog.com
 */
namespace BlogUser\Form;

use NBlog\Model\PostType;
use Zend\Form\Element;
use NBlog\Form\BaseForm;
use NBlog\Model\WritingStatus;

class Blog extends BaseForm
{
    public function __construct(array $options, $formName = 'blog-form')
    {
        parent::__construct($formName);
        $this->setTranslator($options['translator']);
        $this->addElements($options);
    }

    protected function addElements(array $options)
    {
        $this->setUseInputFilterDefaults(false);
        $this->add($this->createBlogPostTypeElement());
        $this->add($this->createBlogIdElement());
        $this->add($this->createEpisodeIdElement());
        $this->add($this->createTitleInputElement());
        $this->add($this->createTitleSelectElement());
        $this->add($this->createEpisodeSerialTagElement());
        $this->add($this->createDetailElement());
        $this->add($this->createCategoryElement($options['categories']));
        $this->add($this->createNoteElement());
        $this->add($this->createStatusElement($options['statuses']));
        $this->add($this->createSubmitButtonElement());
        $this->add($this->createCancelButtonElement());
    }

    protected function createBlogIdElement()
    {
        return new Element\Hidden('post_id');
    }

    protected function createEpisodeIdElement()
    {
        return new Element\Hidden('episode_id');
    }

    protected function createTitleInputElement()
    {
        $element = new Element('title');
        $element->setLabel('Blog Title');
        $element->setAttributes(array(
            'type' => 'text',
            'class' => 'span12 input-title',
            'id' => 'input-title',
            'placeholder' => $this->getTranslator()->translate('Blog Title')
        ));

        return $this->addClassForKeyboardLayout($element);
    }

    protected function createTitleSelectElement()
    {
        $element = new Element\Select('select-title');
        $element->setLabel('Blog Title');
        $element->setAttributes(array(
            'class' => 'form-control select-title',
            'placeholder' => $this->getTranslator()->translate('Episode Title')
        ));
        return $element->setEmptyOption($this->getTranslator()->translate('Episode Title'));
    }

    protected function createEpisodeSerialTagElement()
    {
        $element = new Element\Text('episode_tag');
        $element->setLabel('Episode Number');
        $element->setAttributes(array(
            'class' => 'form-control',
            'id' => 'sequence_no',
            'disabled'=>'disabled',
            'placeholder' => $this->getTranslator()->translate('Episode Number')
        ));
        return $this->addClassForKeyboardLayout($element);
    }


    protected function createCategoryElement($categories)
    {
        $element = new Element\MultiCheckbox('category_id');
        $element->setLabel('Blog Category');
        $element->setAttributes(array(
            'class' => 'span3'
        ));

        $element->setValueOptions($categories);
        return $element;
    }

    protected function createDetailElement()
    {
        $element = new Element\Textarea('details');
        $element->setLabel('Description');
        $element->setAttributes(array(
            'class' => 'span24 post',
            'id' => 'details',
            'rows' => 5,
            'cols' => 66,
            'placeholder' => $this->getTranslator()->translate('Start writing...')
        ));

        return $this->addClassForKeyboardLayout($element);
    }

    protected function createNoteElement()
    {
        $element = new Element\Textarea('note');
        $element->setLabel('Note');
        $element->setAttributes(array(
            'class' => 'prekkhapot bd bds3',
            'id' => 'note',
            'rows' => 5,
            'cols' => 66,
            'placeholder' => $this->getTranslator()->translate('You can inform about the writing...')
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

    protected function createBlogPostTypeElement()
    {
        $element = new Element\Checkbox('type');
        $element->setAttributes(array(
            'id' => 'seq-1'
        ));
        $element->setUseHiddenElement(true);
        $element->setCheckedValue(PostType::EPISODE);
        $element->setUncheckedValue(PostType::BLOG);

        return $element;
    }

    protected function createSubmitButtonElement()
    {
        $element = new Element\Submit('submit');
        $element->setAttributes(array(
            'class' => 'btn btn-black btn-xs',
            'id' => 'submit-post',
            'value' => $this->getTranslator()->translate('Send')
        ));

        return $element;
    }

    protected function createCancelButtonElement()
    {
        $element = new Element\Button('cancel');
        $element->setLabel('Cancel');
        $element->setAttributes(array(
            'class' => 'btn btn-gray btn-xs',
            'id'=>'cancel-post'
        ));

        return $element;
    }
}