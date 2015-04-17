<?php
/**
 * Report Form
 *
 * @category        Form
 * @package         BlogUser
 * @author          Md. Atiqul Haque <mailtoatiq@gmail.com>
 * @copyright       Copyright (c) 2012 Nokkhotro Blog. http://www.nokkhotroblog.com
 */
namespace BlogUser\Form;
use Zend\Form\Form;
use Zend\Form\Element;
class Report extends Form
{
    public function __construct(array $options = array())
    {
        parent::__construct('report-form');
        $this->setAttribute('class', 'report-form');
        $this->add($this->createReportMessageField($options['messages']));
        $this->add($this->createCreatorIdField());
        $this->add($this->createIdOfReportedForField());
        $this->add($this->createStatusField());
        $this->add($this->createReportedOnField());
        $this->add($this->createSubmitButtonField());
        $this->add($this->createCancelButtonField());
    }

    protected function createReportMessageField(array $messages = array())
    {
        $element = new Element\Radio('message_id');
        $element->setLabel('Please inform us about your report: ');
        $element->setValueOptions($messages);
        $element->setLabelAttributes(array('class' => 'radio inline'));
        return $element;
    }

    protected function createCreatorIdField()
    {
        return new Element\Hidden('user_id');
    }

    protected function createIdOfReportedForField()
    {
        return new Element\Hidden('id_of_reported_on');
    }

    protected function createStatusField()
    {
        return new Element\Hidden('status');
    }

    protected function createReportedOnField()
    {
        return new Element\Hidden('reported_on');
    }

    protected function createSubmitButtonField()
    {
        $element = new Element\Submit('submit');
        $element->setLabel('Report About This');
        $element->setAttributes(array(
            'class' => 'btn btn-primary btn-xs'
        ));

        return $element;
    }

    protected function createCancelButtonField()
    {
        $element = new Element\Button('cancel');
        $element->setLabel('Cancel');
        $element->setAttributes(array(
            'class' => 'btn btn-default btn-xs leave-it'
        ));

        return $element;
    }
}