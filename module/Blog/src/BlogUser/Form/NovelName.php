<?php

/**
 * Novel Name Form
 *
 * @category        Form
 * @package         BlogUser
 * @author          Md. Atiqul Haque <mailtoatiq@gmail.com>
 * @copyright       Copyright (c) 2012 Nokkhotro Blog. http://www.nokkhotroblog.com
 */
namespace BlogUser\Form;
use Zend\Form\Form;
use Zend\Form\Element;

class NovelName extends Form
{
    public function __construct()
    {
        parent::__construct('novel-name-form');
        $this->add($this->createNovelNameIdField());
        $this->add($this->createNovelNameField());
        $this->add($this->createSubmitButtonField());
        $this->add($this->createCancelButtonField());
    }

    protected function createNovelNameIdField()
    {
        $novelNameId = new Element('novel_name_id');
        $novelNameId->setAttributes(array(
            'type' => 'hidden'
        ));

        return $novelNameId;
    }

    protected function createNovelNameField()
    {
        $novelName = new Element('novel_name');
        $novelName->setLabel('Novel Name');
        $novelName->setAttributes(array(
            'type' => 'text',
            'class' => 'span11'
        ));

        return $novelName;
    }

    protected function createSubmitButtonField()
    {
        return array(
            'name' => 'submit',
            'attributes' => array(
                'type' => 'submit',
                'value' => 'Add',
                'id' => 'sign-up-button',
                'class' => 'btn btn-primary'
            ),
        );
    }

    protected function createCancelButtonField()
    {
        return array(
            'name' => 'cancel',
            'attributes' => array(
                'type' => 'button',
                'value' => 'Cancel',
                'class' => 'btn',
                'onClick' => 'window.location="/me/novels";return false;'
            ),
        );
    }
}