<?php
/**
 * Episodic Post Form
 *
 * @category        Form
 * @package         BlogUser
 * @author          Md. Atiqul Haque <mailtoatiq@gmail.com>
 * @copyright       Copyright (c) 2013 Nokkhotro Blog. http://www.nokkhotroblog.com
 */
namespace BlogUser\Form;
use Zend\Form\Element;
class EpisodicPost extends Blog
{
    public function __construct(array $options)
    {
        parent::__construct($options, 'episodic-post-form');
    }

    protected function addElements(array $options)
    {
        $this->add($this->createBlogIdElement());
        $this->add($this->createEpisodeIdElement($options['episode_id']));
        $this->add($this->createSerialElement($options['episode_tag'], !empty($options['tag_readonly'])));
        $this->add($this->createDetailElement());
        $this->add($this->createCategoryElement($options['categories']));
        $this->add($this->createNoteElement());
        $this->add($this->createStatusElement($options['statuses']));
        $this->add($this->createSubmitButtonElement());
        $this->add($this->createCancelButtonElement());
    }

    protected function createEpisodeIdElement($episodeId)
    {
        $element = new Element\Hidden('episode_id');
        $element->setValue($episodeId);
        return $element;
    }

    protected function createSerialElement($subtitle, $readOnly = true)
    {
        $element = new Element\Text('episode_tag');
        $element->setLabel('Post Subtitle');
        $element->setAttributes(array(
            'class' => 'span12',
            'id' => 'title',
            'placeholder' => $this->getTranslator()->translate('Blog Subtitle')
        ));

        empty($subtitle) || $element->setValue($subtitle);
        empty($readOnly) || $element->setAttribute('readonly', true);
        return $this->addClassForKeyboardLayout($element);
    }
}