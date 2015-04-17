<?php
/**
 * OtherSettings Form
 *
 * @category        Form
 * @package         BlogUser
 * @author          Md. Nuruzzaman Bappi <bappi.cse562@gmail.com>
 * @copyright       Copyright (c) 2014 Nokkhotro Blog. http://www.nokkhotroblog.com
 */
namespace BlogUser\Form;
use NBlog\Form\BaseForm;
use NBlog\Model\KeyboardLayout;
use Zend\Form\Element;

class OtherSettings extends BaseForm
{
    public function __construct(array $options)
    {
        parent::__construct('other-settings', 'form-horizontal');
        $this->setTranslator($options['translator']);
        $this->add($this->createKeyBoardElement($options['key-board']));
        $this->add($this->createLanguagesElement($options['language']));
        $this->add($this->createDateTimeElement($options['dateTimes']));
        $this->add($this->createSubmitButtonElement());
        $this->add($this->createCancelButtonElement());
    }

    protected function createKeyBoardElement($keyboardLayouts)
    {
        $element = new Element\Select('keyboard');
        $element->setLabel('Key Board change');
        $element->setAttributes(array(
            'type' => 'select',
            'class' => 'form-control'
        ));
        if (!empty($keyboardLayouts)) {
            $layouts = array();
            foreach($keyboardLayouts AS $key => $layout) {
                $layouts[$key] = $layout['name'];
            }
            $element->setValueOptions($layouts);
        }
        $element->setValue(KeyboardLayout::AVRO_PHONETIC);
        return $element;
    }

    protected function createLanguagesElement($language)
    {
        $element = new Element\Select('language');
        $element->setLabel('Languages change');
        $element->setAttributes(array(
            'type' => 'select',
            'class' => 'form-control'
        ));
        $element->setValueOptions($language);
        return $element;
    }

    protected function createDateTimeElement($dateTimes)
    {
        $element = new Element\Select('date_time');
        $element->setLabel('Date Time change');
        $element->setAttributes(array(
            'type' => 'select',
            'class' => 'form-control'
        ));
        $element->setValueOptions($dateTimes);
        return $element;
    }

    protected function createSubmitButtonElement()
    {
        return array(
            'name' => 'submit',
            'attributes' => array(
                'type' => 'button',
                'value' => $this->getTranslator()->translate('Submit'),
                'class' => 'btn btn-primary'
            )
        );
    }

    protected function createCancelButtonElement()
    {
        return array(
            'name' => 'cancel',
            'attributes' => array(
                'type' => 'button',
                'value' => 'Cancel',
                'class' => 'btn primary'
            )
        );
    }
}