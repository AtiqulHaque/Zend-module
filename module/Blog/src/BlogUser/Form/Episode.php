<?php
/**
 * Episode Form
 *
 * @category        Form
 * @package         BlogUser
 * @author          Md. Atiqul Haque <mailtoatiq@gmail.com>
 * @author          Mohammad Faisal Ahmed <faisal.ahmed0001@gmail.com>
 * @copyright       Copyright (c) 2012 Nokkhotro Blog. http://www.nokkhotroblog.com
 */
namespace BlogUser\Form;

use BlogUser\Model\EpisodeStyle;
use NBlog\Form\BaseForm;
use Zend\Form\Element;

class Episode extends BaseForm
{
    public function __construct(array $options)
    {
        parent::__construct('episode-form');
        $this->add($this->createEpisodeIdElement());
        $this->add($this->createTitleElement());
//        $this->add($this->createEpisodeStyleElement($options['styles']));
        $this->add($this->createEpisodeStyleIdElement());
        $this->add($this->createCategoryElement($options['categories']));
        $this->add($this->createTermConditionElement());
        $this->add($this->createSubmitButtonElement());
        $this->add($this->createCancelButtonElement());
    }

    protected function createEpisodeIdElement()
    {
        $element = new Element('episode_id');
        $element->setAttributes(array(
            'type' => 'hidden'
        ));

        return $element;
    }

    protected function createTitleElement()
    {
        $element = new Element('title');
        $element->setLabel('Episode Title');
        $element->setAttributes(array(
            'type' => 'text',
            'id' => 'title',
            'class' => 'input-xxlarge',
            'placeholder' => 'Episode Title'
        ));

        return $this->addClassForKeyboardLayout($element);
    }

    protected function createEpisodeStyleElement($styles)
    {
        $element = new Element\Select('episodic_style_id');
        $element->setLabel('Episode Serial Style');
        $element->setAttributes(array(
            'class' => 'span3'
        ));

        $element->setValueOptions(array('' => 'Select') + $styles);
        return $element;
    }

    protected function createEpisodeStyleIdElement()
    {
        $element = new Element\Hidden('episodic_style_id');
        $element->setValue(EpisodeStyle::CUSTOM);

        return $element;
    }

    protected function createCategoryElement($categories)
    {
        $element = new Element\Select('category_id');
        $element->setLabel('Category');
        $element->setAttributes(array(
            'class' => 'span3',
            'multiple' => true
        ));

        $element->setValueOptions($categories);
        return $element;
    }

    protected function createSubmitButtonElement()
    {
        return array(
            'name' => 'submit',
            'attributes' => array(
                'type' => 'submit',
                'value' => 'Add',
                'id' => 'episode-button',
                'class' => 'btn btn-primary'
            ),
        );
    }

    protected function createCancelButtonElement()
    {
        return array(
            'name' => 'cancel',
            'attributes' => array(
                'type' => 'button',
                'value' => 'Cancel',
                'class' => 'btn',
                'onClick' => 'window.location="/me/episodes";return false;'
            ),
        );
    }
}