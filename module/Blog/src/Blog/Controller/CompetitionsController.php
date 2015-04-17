<?php
namespace Blog\Controller;

use Blog\Form\Contact;
use NBlog\Model\VoteConfig;
use Zend\Json\Json;
use Zend\View\Model\ViewModel;

/**
 * Competitions Controller
 *
 * @category        Controller
 * @package         Blog
 * @author          Md. Nuruzzaman Bappi <bappi.cse562@gmail.com>
 * @author          Md. Atiqul Haque <mailtoatiq@gmail.com>
 * @author          Md. Atiqul Haque <mailtoatiq@gmail.com>
 * @copyright       Copyright (c) 2014 Nokkhotro Lab. http://www.nokkhotrolab.com
 */
class CompetitionsController extends BaseController
{
    protected $blogModel;
    protected $categoryModel;
    protected $professionModel;
    protected $postForVotingModel;
    protected $postVoterModel;

    public function indexAction()
    {
        $this->initializeLayout();
        return new ViewModel(array(
            'durations' => VoteConfig::getCompetitionDurations()
        ));
    }

    public function showAction()
    {
        $params = $this->params()->fromRoute();
        $contactForm = new Contact(array(
            'translator' => $this->getTranslatorHelper(),
            'reasons' => $this->getContactReasonModel()->getAll()
        ));

        $viewModel = new ViewModel(array(
            'contest' => $params['contest'],
            'contactForm' => $contactForm
        ));

        switch($params['contest']) {
            case VoteConfig::VoteForIndependent:
            case VoteConfig::RAIN_COMPETITION:
                $params['episode'] = VoteConfig::EPISODE_1;
                $viewModel->setVariable('topFiveTab', VoteConfig::VoteForIndependent == $params['contest']);
                break;

            case VoteConfig::BOOK_FAIR_2014:
            case VoteConfig::BOOK_FAIR_2015:
            default:
                $activeEpisodeId = VoteConfig::getActiveEpisode($params['contest']);
                $params['episode'] = empty($activeEpisodeId) ? VoteConfig::EPISODE_1 : $activeEpisodeId;
                $viewModel->setVariable('isVotingEnabled', !empty($activeEpisodeId));
        }

        $params['category'] = VoteConfig::EPISODE_1;
        $viewModel->setVariables(array(
            'activeEpisode' => $params['episode'],
            'episodeCount' => VoteConfig::getEpisodeCount($params['contest']),
            'blogPosts' => $this->getBlogModel()->getSelectedPostsForCompetition($params),
            'votingResult' => $this->getBlogModel()->getCompetitionResult($params['contest']),
            'voteCategories' => VoteConfig::getArticleCategoriesForCompetition($params['contest']),
            'tab' => $params['tab']
        ));

        $this->layout()->setVariable('metaInfo', VoteConfig::getCompetitionMetaInfo($params['contest']));
        return $this->initialize($viewModel);
    }

    public function getPostsEpisodeWiseAction()
    {
        $options = array_merge($this->params()->fromQuery(), $this->params()->fromRoute());
        !empty($options['contest']) || $options['contest'] = VoteConfig::BOOK_FAIR_2014;

        $activeEpisodeId = VoteConfig::getActiveEpisode($options['contest']);
        $selectedEpisodeId = $this->params()->fromRoute('episode', 1);
        !empty($selectedEpisodeId) || $selectedEpisodeId = $activeEpisodeId;
        $options['episode'] = $selectedEpisodeId;
        $options['category'] = empty($options['category']) ? VoteConfig::EPISODE_1 : $options['category'];

        $viewModel = new ViewModel(array(
            'blogPosts' => $this->getBlogModel()->getSelectedPostsForCompetition($options),
            'categories' => $this->getCategoryModel()->getAll(),
            'professions' => $this->getProfessionModel()->getAll(),
        ));

        return $viewModel->setTemplate('blog/competitions/partials/post-list')->setTerminal(true);
    }

    public function voteForPostAction()
    {
        $request = $this->getRequest();
        if (!$request->isXmlHttpRequest()) {
            return $this->redirectToPreviousUrlForFailure($this->translate('You are not authenticated.'));
        }

        $currentUser = $this->getSessionContainer()->offsetGet('user_id');
        $activeCompetition = VoteConfig::getActiveCompetition();
        $activeEpisode = VoteConfig::getActiveEpisode($activeCompetition);
        $voteCount = $this->getPostVoterModel()->getVoteCount($currentUser, $activeCompetition, $activeEpisode);

        if ($voteCount >= VoteConfig::VOTE_LIMIT) {
            $result = array(
                'status' => 'error',
                'msg' => sprintf($this->translate('You have already given %s votes.'), $this->getNumberHelper()->convert(VoteConfig::VOTE_LIMIT))
            );
        } else {
            $params = $request->getPost()->toArray();
            $postDetail = $this->getBlogModel()->getByPermalink($params['permalink']);
            $checkVoteForPost = $this->getPostForVotingModel()->checkVoteForUsers($postDetail, $currentUser, $activeCompetition, $activeEpisode);
            if (empty($checkVoteForPost)) {
                $result = $this->getPostVoterModel()->voteForPost($postDetail['post_id'], $currentUser);
                if (empty($result)) {
                    $result = array(
                        'status' => 'error',
                        'msg' => $this->translate('Something went wrong. Please try again.')
                    );
                } else {
                    $result = array(
                        'status' => 'success',
                        'countVoteForPost' => $this->translate('You have voted'),
                        'msg' => $this->translate('Your vote has been successfully taken for this post.')
                    );
                }
            } else {
                $result = array(
                    'status' => 'error',
                    'msg' => $this->translate('You have already voted this post.')
                );
            }
        }

        return $this->getResponse()->setContent(Json::encode($result, true));
    }

    /**
     * Set some default values for the given view object.
     *
     * @param   ViewModel $viewModel
     * @return  ViewModel
     */
    protected function initialize(ViewModel $viewModel)
    {
        $viewModel->setVariables(array(
            'categories' => $this->getCategoryModel()->getAll(),
            'professions' => $this->getProfessionModel()->getAll(),
        ));

        $this->initializeLayout();
        return $viewModel;
    }

    /**
     * @return \NBlog\Model\Category
     */
    private function getCategoryModel()
    {
        isset($this->categoryModel) || $this->categoryModel = $this->getServiceLocator()->get('NBlog\Model\Category');
        return $this->categoryModel;
    }

    /**
     * @return \NBlog\Model\Profession
     */
    private function getProfessionModel()
    {
        isset($this->professionModel) || $this->professionModel = $this->getServiceLocator()->get('NBlog\Model\Profession');
        return $this->professionModel;
    }

    /**
     * @return      \Blog\Model\Blog
     */
    protected function getBlogModel()
    {
        isset($this->blogModel) || ($this->blogModel = $this->getServiceLocator()->get('Blog\Model\Blog'));
        return $this->blogModel;
    }

    /**
     * @return \Blog\Model\ContactReason
     */
    private function getContactReasonModel()
    {
        return $this->getServiceLocator()->get('Blog\Model\ContactReason');
    }

    /**
     * @return      \NBlog\Model\PostForVoting
     */
    protected function getPostForVotingModel()
    {
        isset($this->postForVotingModel) || ($this->postForVotingModel = $this->getServiceLocator()->get('NBlog\Model\PostForVoting'));
        return $this->postForVotingModel;
    }

    /**
     * @return      \Blog\Model\PostVoter
     */
    protected function getPostVoterModel()
    {
        isset($this->postVoterModel) || ($this->postVoterModel = $this->getServiceLocator()->get('Blog\Model\PostVoter'));
        return $this->postVoterModel;
    }
}
