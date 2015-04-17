<?php
/**
 * Album Form
 *
 * @category        Form
 * @package         BlogUser
 * @author          Md.Atiqul Haque <md_atiqulhaque@yahoo.com>
 * @copyright       Copyright (c) 2012 Nokkhotro Blog. http://www.nokkhotroblog.com
 */
namespace BlogUser\Form;

use Zend\Form\Element;
use NBlog\Form\BaseForm;

class Album extends BaseForm
{
    public function __construct(array $options, $formName = 'album-form')
    {
        parent::__construct($formName);
        $this->setTranslator($options['translator']);
        $this->addElements($options);
    }

    protected function addElements(array $options)
    {
        $this->add($this->createBlogIdElement());
        $this->add($this->createAlbumNameElement());
        $this->add($this->createSubmitButtonElement());
        $this->add($this->createCancelButtonElement());
    }

    protected function createBlogIdElement()
    {
        return new Element\Hidden('post_id');
    }

    protected function createAlbumNameElement()
    {
        $element = new Element('album_name');
        $element->setLabel('Album name');
        $element->setAttributes(array(
            'type' => 'text',
            'class' => 'span12',
            'id' => 'name',
            'placeholder' => $this->getTranslator()->translate('Enter album name')
        ));

        return $this->addClassForKeyboardLayout($element);
    }

    protected function createSubmitButtonElement()
    {
        $element = new Element\Submit('submit');
        $element->setAttributes(array(
            'class' => 'btn',
            'value' => $this->getTranslator()->translate('Save')
        ));

        return $element;
    }

    protected function createCancelButtonElement()
    {
        $element = new Element\Button('cancel');
        $element->setLabel('Cancel');
        $element->setAttributes(array(
            'class' => 'btn',
            'data-dismiss' => "modal",
            'aria-hidden' => "true"
        ));
        return $element;
    }
}