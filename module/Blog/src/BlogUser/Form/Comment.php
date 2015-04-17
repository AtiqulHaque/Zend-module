<?php
/**
 * Comment Form
 *
 * @category        Form
 * @package         BlogUser
 * @author          Md. Atiqul Haque <mailtoatiq@gmail.com>
 * @copyright       Copyright (c) 2012 Nokkhotro Blog. http://www.nokkhotroblog.com
 */
namespace BlogUser\Form;

use NBlog\Form\BaseForm;
use Zend\Form\Element;

class Comment extends BaseForm
{
    public function __construct(array $options = array())
    {
        parent::__construct('comment-form', 'comment-form');
        $this->setTranslator($options['translator']);
        
        $this->add($this->createCommentIdField());
        $this->add($this->createCommentField());
        $this->add($this->createCommentTypeField());
        $this->add($this->createCommentForField());
        $this->add($this->createParentCommentIdField());
        $this->add($this->createSubmitButtonField());
        $this->add($this->createCancelButtonField());
    }

    protected function createCommentIdField()
    {
        return new Element\Hidden('comment_id');
    }

    protected function createCommentField()
    {
        $element = new Element\Textarea('comment');
        $element->setLabel('Enter your comments here: ');
        $element->setAttributes(array(
            'class' => 'comment comments-editor keyboard',
            'placeholder' => $this->getTranslator()->translate('Write your comments'),
            'id' => 'comment-editor'
        ));
        return $element;
    }

    protected function createCommentTypeField()
    {
        $element = new Element\Hidden('type');
        return $element->setValue(\Blog\Model\Comment::TYPE_NEW);
    }

    protected function createCommentForField()
    {
        return new Element\Hidden('comment_for');
    }

    protected function createParentCommentIdField()
    {
        return new Element\Hidden('parent_id');
    }

    protected function createSubmitButtonField()
    {
        return array(
            'name' => 'submit',
            'attributes' => array(
                'type' => 'submit',
                'value' => 'Submit',
                'class' => 'btn btn-primary btn-xs'
            ),
        );
    }

    protected function createCancelButtonField()
    {
        $element = new Element\Button('cancel');
        $element->setLabel('Cancel');
        return $element->setAttributes(array(
            'class' => 'btn'
        ));
    }
}