<?php
/**
 * Blog Module Config
 *
 * @category        Config
 * @package         Blog
 * @copyright       Copyright (c) 2012 Nokkhotro Blog. http://www.nokkhotroblog.com
 * @author          Md. Atiqul Haque <mailtoatiq@gmail.com>
 * @author          Mohammad Faisal Ahmed <faisal.ahmed0001@gmail.com>
 */
return array(
    'controllers' => array(
        'invokables' => array(
            'Blog\Controller\Index'             => 'Blog\Controller\IndexController',
            'Blog\Controller\Notices'           => 'Blog\Controller\NoticesController',
            'Blog\Controller\Posts'             => 'Blog\Controller\PostsController',
            'BlogUser\Controller\Index'         => 'BlogUser\Controller\IndexController',
            'BlogUser\Controller\Blog'          => 'BlogUser\Controller\BlogController',
            'BlogUser\Controller\Comments'      => 'BlogUser\Controller\CommentsController',
            'BlogUser\Controller\Subscribers'   => 'BlogUser\Controller\SubscribersController',
            'BlogUser\Controller\Novels'        => 'BlogUser\Controller\NovelsController',
            'BlogUser\Controller\Discussions'   => 'BlogUser\Controller\DiscussionsController',
            'BlogUser\Controller\Episodes'      => 'BlogUser\Controller\EpisodesController',
            'BlogUser\Controller\Moods'         => 'BlogUser\Controller\MoodsController',
            'BlogUser\Controller\Images'        => 'BlogUser\Controller\ImagesController',
            'BlogUser\Controller\Friends'       => 'BlogUser\Controller\FriendsController',
//            'BlogUser\Controller\Groups'        => 'BlogUser\Controller\GroupsController',
            'BlogUser\Controller\OnlineUsers'   => 'BlogUser\Controller\OnlineUsersController',
            'Blog\Controller\Pages'             => 'Blog\Controller\PagesController',
            'Blog\Controller\Discussions'       => 'Blog\Controller\DiscussionsController',
            'BlogUser\Controller\Emails'        => 'BlogUser\Controller\EmailsController',
            'Blog\Controller\Competitions'      => 'Blog\Controller\CompetitionsController',
        ),
    ),
    'router' => array(
        'routes' => array(
            'blog' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/blog[/page/:page]',
                    'constraints' => array(
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ),
                    'defaults' => array(
                        'controller' => 'Blog\Controller\Index',
                        'action' => 'index',
                        'page'       => 1
                    ),
                ),
            ),
            'inform-user-available' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/set-user-available',
                    'defaults' => array(
                        'controller' => 'Blog\Controller\Index',
                        'action'     => 'setUserAvailable'
                    ),
                ),
            ),
            'get-user-friends' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/get-user-friends',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\OnlineUsers',
                        'action'     => 'getOnlineFriendOfAUser'
                    ),
                ),
            ),
            'category' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/categorical[/:permalink[/:page]]',
                    'defaults' => array(
                        'controller' => 'Blog\Controller\Posts',
                        'action' => 'get-by-category',
                        'page' => 1
                    )
                )
            ),
            'get-random-post' => array(
                'type' => 'literal',
                'options' => array(
                    'route' => '/get-random-post',
                    'defaults' => array(
                        'controller' => 'Blog\Controller\Posts',
                        'action' => 'get-random-post'
                    )
                )
            ),
            'get-selected-posts' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/posts/get-selected-posts[/page/:page]',
                    'defaults' => array(
                        'controller' => 'Blog\Controller\Posts',
                        'action'     => 'getSelectedPosts',
                        'page'       => 1
                    )
                )
            ),
            'get-most-anything-about-posts' => array(
                'type' => 'literal',
                'options' => array(
                    'route' => '/posts/get-most-anything-about-posts',
                    'defaults' => array(
                        'controller' => 'Blog\Controller\Posts',
                        'action' => 'getMostAnythingAboutPosts'
                    )
                )
            ),
            'check-non-queued-post' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/check-non-queued-post/[page/:page]',
                    'defaults' => array(
                        'controller' => 'Blog\Controller\Posts',
                        'action'     => 'deque-post',
                        'page'       => 1
                    ),
                ),
            ),
            'specific-post' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/post/:permalink',
                    'defaults' => array(
                        'controller' => 'Blog\Controller\Posts',
                        'action'     => 'show',
                    ),
                ),
            ),
            'search' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/search[/:page]',
                    'defaults' => array(
                        'controller' => 'Blog\Controller\Index',
                        'action'     => 'search',
                        'page'       => 1
                    ),
                ),
            ),
            'view-all-published-posts' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/view-all-published-posts[/:page]',
                    'defaults' => array(
                        'controller' => 'Blog\Controller\Posts',
                        'action'     => 'show-all-published-posts',
                        'page'       => 1
                    ),
                ),
            ),
            'view-all-selected-posts' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/view-all-selected-posts[/:page]',
                    'defaults' => array(
                        'controller' => 'Blog\Controller\Posts',
                        'action'     => 'show-all-selected-posts',
                        'page'       => 1
                    ),
                ),
            ),
            'view-all-sticky-posts' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/view-all-sticky-posts[/:page]',
                    'defaults' => array(
                        'controller' => 'Blog\Controller\Posts',
                        'action'     => 'show-all-sticky-posts',
                        'page'       => 1
                    ),
                ),
            ),
            'show-all-discussion' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/discussions',
                    'defaults' => array(
                        'controller' => 'Blog\Controller\Discussions',
                        'action'     => 'index',
                    ),
                ),
            ),
            'specific-discussion' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/discussion/:permalink',
                    'defaults' => array(
                        'controller' => 'Blog\Controller\Discussions',
                        'action'     => 'show',
                    ),
                ),
            ),
            'trash-my-discussion' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/me/trash-discussion/:permalink',
                    'defaults' => array(
                        'controller' => 'Blog\Controller\Discussions',
                        'action'     => 'trash',
                    ),
                ),
            ),
            'delete-blog-discussion' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/delete/discussion/:permalink',
                    'defaults' => array(
                        'controller' => 'Blog\Controller\Discussions',
                        'action'     => 'delete',
                    ),
                ),
            ),
            'all-groups' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/groups[/:groupName]',
                    'constraints' => array(
                        'groupName' => '[a-zA-Z0-9]+'
                    ),
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Index',
                        'action'     => 'groups',
                    ),
                ),
            ),
            'all-novels' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/novels[/:novelName]',
                    'constraints' => array(
                        'groupName' => '[a-zA-Z0-9]+'
                    ),
                    'defaults' => array(
                        'controller' => 'Blog\Controller\Index',
                        'action'     => 'novels',
                    ),
                ),
            ),
            /* Blog User Info */
            'profile-home' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/me',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Index',
                        'action'     => 'index'
                    ),
                ),
            ),
            /* Blog User Profile */
            'user-profile-home' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/profile',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Index',
                        'action'     => 'getProfile'
                    ),
                ),
            ),
            'friends' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/friends',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Index',
                        'action'     => 'online-friend'
                    ),
                ),
            ),
            'other-settings' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/other/settings',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Index',
                        'action'     => 'other-settings'
                    ),
                ),
            ),
            'sent' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/sent',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Index',
                        'action'     => 'new-message'
                    ),
                ),
            ),
            'get-friend-detail' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/get-friend-detail',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\OnlineUsers',
                        'action'     => 'getOnlineFriend'
                    ),
                ),
            ),
            'settings' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/me/settings',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Index',
                        'action'     => 'settings'
                    ),
                ),
            ),
            'compose-email' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/compose',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Emails',
                        'action'     => 'compose'
                    ),
                ),
            ),
            'specific-email' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/email/[:status]',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Emails',
                        'action'     => 'specific-email'
                    ),
                ),
            ),
            'get-email' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/get-email',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Emails',
                        'action'     => 'get-email'
                    ),
                ),
            ),
            'delete-email' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/delete-email',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Emails',
                        'action'     => 'delete-email'
                    ),
                ),
            ),
            'draft-email' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/delete-email',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Emails',
                        'action'     => 'draft-email'
                    ),
                ),
            ),
            'search-email' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/search-email',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Emails',
                        'action'     => 'search-email'
                    ),
                ),
            ),
            'close-notice' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/me/close-notice/:permalink',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Index',
                        'action'     => 'closeNotice',
                    ),
                ),
            ),

            'all-like-single-posts'=>array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/ajax/likes',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Subscribers',
                        'action'     => 'like-in-single-posts'
                    ),
                ),
            ),
            'user-short-popover'=>array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/ajax/users-profile',
                    'defaults' => array(
                        'controller' => 'Blog\Controller\Index',
                        'action'     => 'user-short-profile-with-popover'
                    ),
                ),
            ),
            'friends-subscribers-followers' => array(
                'type' => 'regex',
                'options' => array(
                    'regex' => '/(?<username>[a-zA-Z0-9\.]+)/friends',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Friends',
                        'action'     => 'index'
                    ),
                    'spec'  => '/%username%/friends',
                ),
            ),
            'get-all-friends'=>array(
                'type' => 'regex',
                'options' => array(
                    'regex' => '/(?<username>[a-zA-Z0-9\.]+)/get-friends',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Friends',
                        'action'     => 'get-friends'
                    ),
                    'spec'  => '/%username%/get-friends',
                ),
            ),
            'set-message-read'=>array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/set-message-read',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\OnlineUsers',
                        'action'     => 'set-message-read'
                    ),
                ),
            ),
            'set-notification-checked'=>array(
                'type' => 'literal',
                'options' => array(
                    'route'    => '/set-notification-checked',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Index',
                        'action'     => 'setNotificationChecked'
                    ),
                ),
            ),

            /*Profile pic routes Start*/
            'show-user-pictures' => array(
                'type' => 'regex',
                'options' => array(
                    'regex' => '/(?<username>[a-zA-Z0-9\.]+)/pictures',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Images',
                        'action'     => 'index'
                    ),
                    'spec'  => '/%username%/pictures',
                ),
            ),
            'show-pic-album'=>array(
                'type' => 'regex',
                'options' => array(
                    'regex' => '/(?<username>[a-zA-Z0-9\.]+)/pic/album',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Images',
                        'action'     => 'showAlbums'
                    ),
                    'spec'  => '/%username%/pic/album',
                ),
            ),
            'profile-pic-upload'=>array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/me/profile-pic[/:is-upload]',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Images',
                        'action'     => 'upload-profile-pic',
                        'is-upload'  => 0
                    ),
                ),
            ),
            'profile-pic-crop-submit'=>array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/me/profile-pic/crop-submit',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Images',
                        'action'     => 'crop-submit'
                    ),
                ),
            ),
            'profile-pic-remove'=>array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/me/profile-pic-remove',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Images',
                        'action'     => 'profile-pic-remove'
                    ),
                ),
            ),
            'profile-pic-set'=>array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/me/profile-pic-set',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Images',
                        'action'     => 'profile-pic-set'
                    ),
                ),
            ),
            'banner-pic-set'=>array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/me/banner-pic-set',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Images',
                        'action'     => 'banner-pic-set'
                    ),
                ),
            ),
            'banner-pic-remove'=>array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/me/banner-pic-remove',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Images',
                        'action'     => 'banner-pic-remove'
                    ),
                ),
            ),
            'banner-pic-temp-upload-cancel'=>array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/me/banner-pic/crop-cancel',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Images',
                        'action'     => 'banner-pic-temp-upload-cancel'
                    ),
                ),
            ),
            'banner-pic-temp-upload'=>array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/me/banner-pic/temp-upload',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Images',
                        'action'     => 'banner-pic-temp-upload'
                    ),
                ),
            ),
            'banner-pic-crop'=>array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/me/banner-pic/crop',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Images',
                        'action'     => 'banner-pic-crop'
                    ),
                ),
            ),

            'profile-pic-auto-crop'=>array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/me/profile-pic/auto-crop-submit',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Images',
                        'action'     => 'autoCropAndResize'
                    ),
                ),
            ),'add-album'=>array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/add-album',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Images',
                        'action'     => 'addAlbum'
                    ),
                ),
            ),'show-album'=>array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/me/album/show',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Images',
                        'action'     => 'showAlbum'
                    ),
                ),
            ),'add-album-pic'=>array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/me/album/pic',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Images',
                        'action'     => 'addAlbumPic'
                    ),
                ),
            ),'remove-album-pic'=>array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/me/album/pic/remove',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Images',
                        'action'     => 'removeAlbumPic'
                    ),
                ),
            ),
            'get-image-upload-manager' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/me/get-image-manager',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Images',
                        'action'     => 'get-image-manager'
                    ),
                ),
            ),'get-upload-box' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/me/get-upload-box',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Images',
                        'action'     => 'get-upload-box'
                    ),
                ),
            ),
            'upload-blog-image' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/me/blog-pic-temp',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Images',
                        'action'     => 'upload-blog-image'
                    ),
                ),
            ),
            'get-all-images' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/me/get-all-images',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Images',
                        'action'     => 'get-all-images'
                    ),
                ),
            ),'get-user-all-albums' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/me/get-user-all-albums',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Images',
                        'action'     => 'getUserAllAlbums'
                    ),
                ),
            ),
            'get-all-album' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/me/get-all-album',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Images',
                        'action'     => 'get-all-album'
                    ),
                ),
            ),
            'get-album-details' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/me/get-album-details',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Images',
                        'action'     => 'get-album-details'
                    ),
                ),
            ),
            'get-each-album' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/me/get-each-album',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Images',
                        'action'     => 'get-each-album'
                    ),
                ),
            ),
            /*Profile pic routes End*/

            'send-friend-request'=>array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/friend/request-send',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Friends',
                        'action'     => 'sendFriendRequest'
                    ),
                ),
            ),

            'accept-friend-request'=>array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/friend/request-accept',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Friends',
                        'action'     => 'friend-request-accept'
                    ),
                ),
            ),
            'public-profile' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/user[/:username[/:page]]',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Index',
                        'action'     => 'public-profile',
                        'page'       => 1
                    ),
                ),
            ),
            'my-all-posts' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/me/post[/:page]',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Blog',
                        'action'     => 'index',
                        'page'       => 1
                    ),
                ),
            ),
            'get-post-status' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/:username/post/by-status[/:page]',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Blog',
                        'action'     => 'get-post-by-status',
                        'page'       => 1
                    ),
                ),
            ),
            'search-my-posts' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/me/search-my-posts[/:page]',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Blog',
                        'action'     => 'search',
                        'page'       => 1
                    ),
                ),
            ),
            'view-my-post' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/me/show-post/[:permalink]',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Blog',
                        'action'     => 'view-my-post'
                    ),
                ),
            ),
            'show-single-post' => array(
                'type' => 'regex',
                'options' => array(
                    'regex' => '/(?<username>[a-zA-Z0-9\.]+)/show-single-post',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Blog',
                        'action'     => 'showSinglePost'
                    ),
                    'spec'  => '/%username%/show-single-post',
                ),
            ),
            'show-user-posts' => array(
                'type' => 'regex',
                'options' => array(
                    'regex' => '/(?<username>(?!me)[a-zA-Z0-9_-]*)/post',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Blog',
                        'action'     => 'showUserPosts',
                        'page'       => 1
                    ),
                    'spec'  => '/%username%/post',
                ),
            ),
            'do-bulk-action' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/me/do-bulk-action',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Blog',
                        'action'     => 'do-bulk-action'
                    ),
                ),
            ),
            'block-commenter' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/[:username]/block-commenter/[:permalink]/[:commenter]',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Blog',
                        'action'     => 'block-commenter',
                    ),
                ),
            ),
            'unblock-commenter' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/[:username]/unblock-commenter/[:permalink]/[:commenter]',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Blog',
                        'action'     => 'unblock-commenter',
                    ),
                ),
            ),
            'block-commenter-for-post' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/[:username]/block-commenter/[:permalink]/[:commenter]',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Blog',
                        'action'     => 'block-commenter',
                    ),
                ),
            ),
            'unblock-commenter-for-post' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/[:username]/unblock-commenter/[:permalink]/[:commenter]',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Blog',
                        'action'     => 'unblock-commenter',
                    ),
                ),
            ),
            'all-comments-in-my-posts' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/me/comments-in-my-posts[/:page]',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Comments',
                        'action'     => 'all-comments-in-my-posts',
                        'page'       => 1
                    ),
                ),
            ),
            'my-comments-in-posts' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/me/my-comments-in-posts[/:page]',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Comments',
                        'action'     => 'my-comments-in-posts',
                        'page'       => 1
                    ),
                ),
            ),
            'replies-of-my-comments' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/me/replies-of-my-comments[/:page]',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Comments',
                        'action'     => 'replies-of-my-comments',
                        'page'       => 1
                    ),
                ),
            ),
            'my-replies-of-comments' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/me/my-replies-of-comments[/:page]',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Comments',
                        'action'     => 'my-replies-of-comments',
                        'page'       => 1
                    ),
                ),
            ),
            'get-divisions' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/get-divisions',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Index',
                        'action'     => 'get-divisions',
                    ),
                ),
            ),
            'get-districts' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/get-districts',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Index',
                        'action'     => 'get-districts',
                    ),
                ),
            ),
            'change-profile' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/user/profile',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Index',
                        'action'     => 'changeProfileInfo'
                    ),
                ),
            ),
            'change-password' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/user/change-password',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Index',
                        'action'     => 'change-password',
                    ),
                ),
            ),
            'change-account' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/user/account[/:tab]',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Index',
                        'action'     => 'change-account',
                        'tab'=> ''
                    ),
                ),
            ),
            'check-username-unique' => array(
                'type' => 'literal',
                'options' => array(
                    'route'    => '/user/check-username-unique',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Index',
                        'action'     => 'checkUsernameUnique'
                    ),
                ),
            ),
            'make-favorite' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/make-favorite',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Subscribers',
                        'action'     => 'makeFavorite'
                    ),
                ),
            ),
            'cancel-favorite' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/cancel-favorite',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Subscribers',
                        'action'     => 'cancelFavorite'
                    ),
                ),
            ),
            'make-writer-favorite' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/me/subscribers/add/writer/[:id]',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Subscribers',
                        'action'     => 'make-favorite-writer',
                    ),
                ),
            ),
            'make-mood-favorite' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/me/subscribers/add/mood/[:permalink]',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Subscribers',
                        'action'     => 'make-favorite-mood',
                    ),
                ),
            ),
            'mood-delete' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/mood/delete/[:permalink]',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Moods',
                        'action'     => 'delete',
                    ),
                ),
            ),
            'cancel-mood-favorite' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/me/subscribers/cancel/mood/[:permalink]',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Subscribers',
                        'action'     => 'cancel-favorite-mood',
                    ),
                ),
            ),
            'cancel-writer-favorite' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/me/subscribers/cancel/writer/[:id]',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Subscribers',
                        'action'     => 'cancel-favorite-writer',
                    ),
                ),
            ),
            'make-post-favorite' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/me/subscribers/add/post/[:permalink][/next/:next]',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Subscribers',
                        'action'     => 'make-favorite-post',
                        'next'       => ''
                    ),
                ),
            ),
            'make-comment-favorite' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/me/subscribers/add/comment/[:comment_id]',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Subscribers',
                        'action'     => 'make-favorite-comment',
                        'comment_id' => 0
                    ),
                ),
            ),
            'cancel-comment-favorite' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/me/subscribers/cancel/comment/[:permalink]',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Subscribers',
                        'action'     => 'cancel-favorite-comment'
                    ),
                ),
            ),
            'make-discussion-favorite' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/me/subscribers/add/discussion/[:permalink][/next/:next]',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Subscribers',
                        'action'     => 'make-favorite-discussion',
                        'next'       => ''
                    ),
                ),
            ),
            'cancel-post-favorite' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/me/subscribers/cancel/post/[:permalink][/next/:next]',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Subscribers',
                        'action'     => 'cancel-favorite-post',
                        'next'       => ''
                    ),
                ),
            ),
            'cancel-discussion-favorite' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/me/subscribers/cancel/discussion/[:permalink][/next/:next]',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Subscribers',
                        'action'     => 'cancel-favorite-discussion',
                        'next'       => ''
                    ),
                ),
            ),
            'save-comment' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/me/save-comment/:permalink',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Comments',
                        'action'     => 'save',
                    ),
                ),
            ),'save-comment-for-userwall' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/me/userwall-save-comment/:permalink',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Comments',
                        'action'     => 'saveForUserWall',
                    ),
                ),
            ),
            'edit-comment' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/me/edit-comment/[:id]',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Comments',
                        'action'     => 'edit',
                        'id'         => ''
                    ),
                ),
            ),
            'edit-userwall-comment' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/me/edit-userwall-comment/[:id]',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Comments',
                        'action'     => 'user-wall-edit',
                        'id'         => ''
                    ),
                ),
            ),
            'ajax-delete-comment' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/delete/comment',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Comments',
                        'action'     => 'ajax-delete-comment',
                    ),
                ),
            ),
            'delete-comment' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/[:username]/delete-comment/[:id]',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Comments',
                        'action'     => 'delete-comment',
                    ),
                ),
            ),
            'delete-comment-from-post' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/[:username]/delete-comment/[:id]',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Comments',
                        'action'     => 'delete-comment',
                    ),
                ),
            ),
            'block-comment' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/[:username]/block-comment/[:id]',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Comments',
                        'action'     => 'block-comment',
                    ),
                ),
            ),
            'report' => array(
                'type' => 'literal',
                'options' => array(
                    'route'    => '/me/report',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Index',
                        'action'     => 'report',
                    ),
                ),
            ),
            'add-my-post' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/me/new[/isCalled/:isCalled]',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Blog',
                        'action'     => 'add-post',
                        'isCalled'   => 0
                    ),
                ),
            ),
            'edit-my-post' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/me/edit/post/:postId',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Blog',
                        'action'     => 'edit-post',
                        'postId' => null
                    ),
                ),
            ),
            'trash-my-post' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/me/trash/:permalink',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Blog',
                        'action'     => 'trash-post',
                    ),
                ),
            ),
            'trash-my-blog-post' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/posts/trash/:permalink',
                    'defaults' => array(
                        'controller' => 'Blog\Controller\Posts',
                        'action'     => 'trash-posts',
                    ),
                ),
            ),
            'hide-my-blog-content' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/hide/:content_type/:permalink',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Index',
                        'action'     => 'hide-content',
                    ),
                ),
            ),
            'unhide-my-blog-content' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/unhide/:content_type/:permalink',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Index',
                        'action'     => 'unhide-content',
                    ),
                ),
            ),
            'publish-my-post' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/me/publish/:permalink',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Blog',
                        'action'     => 'publish-post',
                    ),
                ),
            ),
            'delete-my-post' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/me/post/delete/',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Blog',
                        'action'     => 'delete-post',
                    ),
                ),
            ),
            'restore-my-post' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/me/restore/:permalink',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Blog',
                        'action'     => 'restore-post',
                    ),
                ),
            ),
            'my-favorite-posts' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/me/favorite-posts[/:page]',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Subscribers',
                        'action'     => 'index',
                        'page'       => 1
                    ),
                ),
            ),
            'get-episode-titles' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/me/get-episode-titles',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Blog',
                        'action'     => 'getEpisodeTitles'
                    ),
                ),
            ),
            'my-favorite-bloggers' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/me/favorite-bloggers[/:page]',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Subscribers',
                        'action'     => 'favorite-bloggers',
                    ),
                ),
            ),
            'me-being-favorite' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/me/being-favorite[/:page]',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Subscribers',
                        'action'     => 'BeingFavoriteBlogger',
                        'page'       => 1
                    ),
                ),
            ),
            'my-posts-being-favorite' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/me/posts-being-favorite[/:page]',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Subscribers',
                        'action'     => 'MyPostsBeingFavorite',
                        'page'       => 1
                    ),
                ),
            ),
            'cancel-favorite-bulky' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/me/cancel-favorite-selected',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Subscribers',
                        'action'     => 'cancel-favorite-bulky'
                    ),
                ),
            ),
            'all-published-notices' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/notices',
                    'defaults' => array(
                        'controller' => 'Blog\Controller\Notices',
                        'action'     => 'index',
                    ),
                ),
            ),
            'notice-specify' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/notices/:permalink',
                    'defaults' => array(
                        'controller' => 'Blog\Controller\Notices',
                        'action'     => 'show',
                    ),
                ),
            ),
//            /* User Groups */
//            'my-groups' => array(
//                'type' => 'segment',
//                'options' => array(
//                    'route'    => '/me/groups',
//                    'defaults' => array(
//                        'controller' => 'BlogUser\Controller\Groups',
//                        'action'     => 'index',
//                    ),
//                ),
//            ),
            /* User Episodes */
            'my-episodes' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/me/episodes',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Episodes',
                        'action'     => 'index',
                    ),
                ),
            ),
            'show-my-episode' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/me/episodes/show/[:permalink]',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Episodes',
                        'action'     => 'show',
                    ),
                ),
            ),
            'add-my-episode' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/me/episodes/add',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Episodes',
                        'action'     => 'add',
                    ),
                ),
            ),
            'edit-my-episode' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/me/episodes/edit[/:permalink]',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Episodes',
                        'action'     => 'edit',
                    ),
                ),
            ),
            'delete-my-episode' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/me/episodes/delete/:permalink',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Episodes',
                        'action'     => 'delete',
                    ),
                ),
            ),
            'show-my-episodic-post' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/me/episodes/:episodePermalink/show-post/:permalink',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Episodes',
                        'action'     => 'showPost',
                    ),
                ),
            ),
            'add-my-episodic-post' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/me/episodes/:episodePermalink/add-post',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Episodes',
                        'action'     => 'addPost',
                    ),
                ),
            ),
            'edit-my-episodic-post' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/me/episodes/:episodePermalink/edit-post[/:permalink]',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Episodes',
                        'action'     => 'editPost',
                    ),
                ),
            ),
            'delete-my-episodic-post' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/me/episodes/:episodePermalink/delete-post/:permalink',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Episodes',
                        'action'     => 'deletePost',
                    ),
                ),
            ),
            'trash-my-episodic-post' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/me/episodes/:episodePermalink/trash-post/:permalink',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Episodes',
                        'action'     => 'trashPost',
                    ),
                ),
            ),
            'restore-my-episodic-post' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/me/episodes/:episodePermalink/restore-post/:permalink',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Episodes',
                        'action'     => 'restorePost',
                    ),
                ),
            ),
            /* User Novels */
            'my-novels' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/me/novels',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Novels',
                        'action'     => 'index',
                    ),
                ),
            ),
            'add-my-novel' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/me/novels/add',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Novels',
                        'action'     => 'add',
                    ),
                ),
            ),
            'edit-my-novel' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/me/novels/edit[/:permalink]',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Novels',
                        'action'     => 'edit',
                    ),
                ),
            ),
            'delete-my-novel' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/me/novels/delete/:permalink',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Novels',
                        'action'     => 'delete',
                    ),
                ),
            ),
            /* User Discussions */
            'my-discussions' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/me/discussions',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Discussions',
                        'action'     => 'index',
                    ),
                ),
            ),
            'show-my-discussion' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/me/discussions/show/[:permalink]',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Discussions',
                        'action'     => 'show',
                    ),
                ),
            ),
            'add-my-discussion' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/me/discussions/add',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Discussions',
                        'action'     => 'add',
                    ),
                ),
            ),
            'edit-my-discussion' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/me/discussions/edit[/:permalink]',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Discussions',
                        'action'     => 'edit',
                    ),
                ),
            ),
            'delete-my-discussion' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/me/discussions/delete/:id',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Discussions',
                        'action'     => 'delete',
                    ),
                ),
            ),
            'block-commenter-for-discussion' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/[:username]/block-commenter-for-discussion/[:permalink]/[:commenter]',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Discussions',
                        'action'     => 'block-commenter',
                    ),
                ),
            ),
            'unblock-commenter-for-discussion' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/[:username]/unblock-commenter-for-discussion/[:permalink]/[:commenter]',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Discussions',
                        'action'     => 'unblock-commenter',
                    ),
                ),
            ),

            /* Friends and Chatting */
            'online-friends' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/online-friends',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\OnlineUsers',
                        'action'     => 'index'
                    ),
                ),
            ),
            'checkChatters' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/check-chatter-friends',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\OnlineUsers',
                        'action'     => 'check-chatters'
                    ),
                ),
            ),
            'loadChatBox' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/loadChatBox',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\OnlineUsers',
                        'action'     => 'load-chat-box'
                    ),
                ),
            ),
            'chat' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/chat',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\OnlineUsers',
                        'action'     => 'chat'
                    ),
                ),
            ),
            'send' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/send',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\OnlineUsers',
                        'action'     => 'new-message'
                    ),
                ),
            ),
            'chat-history' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/message',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\OnlineUsers',
                        'action'     => 'show-chat-history'
                    ),
                ),
            ),
            'get-userwall-chat-history' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/get-userwall-chat-history',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\OnlineUsers',
                        'action'     => 'get-userwall-chat-history'
                    ),
                ),
            ),'get-chat-history' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/get-chat-history/all',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\OnlineUsers',
                        'action'     => 'get-chat-history'
                    ),
                ),
            ),'get-unread-chat-person' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/get-unread-chat-person',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\OnlineUsers',
                        'action'     => 'get-unread-chat-person'
                    ),
                ),
            ),
            'view-all-messages' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/view-all-messages',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\OnlineUsers',
                        'action'     => 'show-chat-history'
                    ),
                ),
            ),
            'view-all-notifications' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/view-all-notifications',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Index',
                        'action'     => 'view-all-notifications'
                    ),
                ),
            ),
            'view-all-friend-requests' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/view-all-friend-requests',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Friends',
                        'action'     => 'view-all-requests'
                    ),
                ),
            ),
            'get-friend-suggestion-list' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/:username/get-friend-suggestion-list[/:isCalled]',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Friends',
                        'action'     => 'getFriendSuggestionList',
                        'username'   => 'me',
                        'isCalled'   => 0
                    ),
                ),
            ),
            'get-friend-request-list' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/me/get-friend-request-list[/:page[/:isCalled]]',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Friends',
                        'action'     => 'getFriendRequestList',
                        'page'       => 1,
                        'isCalled'   => 0
                    ),
                ),
            ),
            'page' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/pages[/:permalink]',
                    'defaults' => array(
                        'controller' => 'Blog\Controller\Pages',
                        'action'     => 'index'
                    ),
                ),
            ),
            'contact-us' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/pages/contact-us',
                    'defaults' => array(
                        'controller' => 'Blog\Controller\Pages',
                        'action'     => 'contact-us'
                    ),
                ),
            ),
            'show-fag-category-question' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/pages/faq',
                    'defaults' => array(
                        'controller' => 'Blog\Controller\Pages',
                        'action'     => 'show-faq-category'
                    ),
                ),
            ),
            'blog-competition' => array(
                'type' => 'literal',
                'options' => array(
                    'route'    => '/contest',
                    'defaults' => array(
                        'controller' => 'Blog\Controller\Competitions',
                        'action'     => 'index'
                    ),
                ),
                'may_terminate' => true,
                'child_routes' => array(
                    'show' => array(
                        'type'    => 'segment',
                        'options' => array(
                            'route'    => '/:contest[/:tab]',
                            'defaults' => array(
                                'action'     => 'show',
                                'contest'    => 'creative-blogging-2014',
                                'tab'        => 1
                            ),
                        )
                    ),
                    'get-posts' => array(
                        'type' => 'segment',
                        'options' => array(
                            'route' => '/get-posts[/:episode[/:category]]',
                            'defaults' => array(
                                'action' => 'getPostsEpisodeWise',
                                'category' => 1,
                            )
                        )
                    ),
                    'vote-for-post' => array(
                        'type' => 'segment',
                        'options' => array(
                            'route' => '/vote-for-post',
                            'defaults' => array(
                                'action' => 'voteForPost',
                            )
                        )
                    ),
                )
            ),
            'my-all-mood-statuses' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/me/moods',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Moods',
                        'action'     => 'index',
                    ),
                ),
            ),
            'specific-mood' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/me/moods/show/:permalink',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Moods',
                        'action'     => 'show',
                    ),
                ),
            ),
            'write-about-mood' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/me/write-about-mood[/isCalled/:isCalled]',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Moods',
                        'action'     => 'add',
                        'isCalled'   => 0
                    ),
                ),
            ),
            'edit-my-mood' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/me/moods/edit[/:mood_id]',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Moods',
                        'action'     => 'edit',
                    ),
                ),
            ),
            'trash-my-mood' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/me/moods/trash/:permalink',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Moods',
                        'action'     => 'trash',
                    ),
                ),
            ),
            'delete-my-mood' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/me/moods/delete',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Moods',
                        'action'     => 'delete',
                    ),
                ),
            ),
            'block-commenter-for-mood' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/[:username]/block-commenter-for-mood/[:permalink]/[:commenter]',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Moods',
                        'action'     => 'block-commenter',
                    ),
                ),
            ),
            'unblock-commenter-for-mood' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/[:username]/unblock-commenter-for-mood/[:permalink]/[:commenter]',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Moods',
                        'action'     => 'unblock-commenter',
                    ),
                ),
            ),
            'activate-email' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/activate-email/[:user_id]/[:activate_code]',
                    'constraints' => array(
                        'user_id' => '[a-zA-Z0-9]+',
                        'activationCode' => '[a-zA-Z0-9]+'
                    ),
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Index',
                        'action'     => 'activate-email',
                    ),
                ),
            ),
            'change-keyboardlayout' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/change-keyboardlayout',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Index',
                        'action' => 'changeKeyboardLayout'
                    )
                )
            ),
            'get-notifications' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/me/get-notifications[/:page[/:isCalled]]',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Index',
                        'action' => 'getNotifications',
                        'page' => 1,
                        'isCalled' => 0
                    )
                )
            ),
            'close-popup' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/closepopup',
                    'defaults' => array(
                        'controller' => 'Blog\Controller\Index',
                        'action'     => 'get-close-button',
                    ),
                ),
            ),
            'user-wall-ajax' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/me/getdata/ajax[/isCalled/:isCalled]',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Index',
                        'action'     => 'getUserWallData',
                        'isCalled'   => 0
                    ),
                ),
            ),
            'get-profile-wall-data' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/:username/profile-data[/isCalled/:isCalled]',
                    'defaults' => array(
                        'controller' => 'BlogUser\Controller\Index',
                        'action'     => 'getProfileWallData',
                        'username'   => 'me',
                        'isCalled'   => 0
                    ),
                ),
            ),
            'ajax-country' => array(
                'type'    => 'literal',
                'options' => array(
                    'route'    => '/geo/ajax/country',
                    'defaults' => array(
                        'controller' => 'Blog\Controller\Index',
                        'action'     => 'ajax-country',
                    ),
                ),
            ),
            'ajax-district' => array(
                'type'    => 'literal',
                'options' => array(
                    'route'    => '/geo/ajax/district',
                    'defaults' => array(
                        'controller' => 'Blog\Controller\Index',
                        'action'     => 'ajax-district',
                    ),
                ),
            ),
            'ajax-station' => array(
                'type'    => 'literal',
                'options' => array(
                    'route'    => '/geo/ajax/station',
                    'defaults' => array(
                        'controller' => 'Blog\Controller\Index',
                        'action'     => 'ajax-station',
                    ),
                ),
            ),
            'ajax-offices' => array(
                'type'    => 'literal',
                'options' => array(
                    'route'    => '/geo/ajax/offices',
                    'defaults' => array(
                        'controller' => 'Blog\Controller\Index',
                        'action'     => 'ajax-office',
                    ),
                ),
            ),
        ),
    ),
    'view_manager' => array(
        'template_path_stack' => array(
            __DIR__ . '/../view',
        ),
        'template_map' => array(
            'profile/layout'    => __DIR__ . '/../view/layout/new-user-profile.phtml',
        ),
    ),
    'service_manager' => array(
        'factories' => array(
            'Blog\Model\Admin' => function(\Zend\ServiceManager\ServiceManager $sm) {
                return new \Blog\Model\Admin($sm);
            },
            'Blog\Model\ContactReason' => function(\Zend\ServiceManager\ServiceManager $sm) {
                return new \Blog\Model\ContactReason($sm);
            },
            'Blog\Model\Blog' => function(\Zend\ServiceManager\ServiceManager $sm) {
                return new \Blog\Model\Blog($sm);
            },
            'Blog\Model\Comment' => function(\Zend\ServiceManager\ServiceManager $sm) {
                return new \Blog\Model\Comment($sm);
            },
            'Blog\Model\Notice' => function(\Zend\ServiceManager\ServiceManager $sm) {
                return new \Blog\Model\Notice($sm);
            },
            'Blog\Model\PostVoter' => function(\Zend\ServiceManager\ServiceManager $sm) {
                return new \Blog\Model\PostVoter($sm);
            },
            'Blog\Model\UserWall' => function(\Zend\ServiceManager\ServiceManager $sm) {
                return new \Blog\Model\UserWall($sm);
            },
            'BlogUser\Model\Album' => function(\Zend\ServiceManager\ServiceManager $sm) {
                return new \BlogUser\Model\Album($sm);
            },
            'BlogUser\Model\AlbumPicture' => function(\Zend\ServiceManager\ServiceManager $sm) {
                return new \BlogUser\Model\AlbumPicture($sm);
            },
            'BlogUser\Model\BlockedUser' => function(\Zend\ServiceManager\ServiceManager $sm) {
                return new \BlogUser\Model\BlockedUser($sm);
            },
            'BlogUser\Model\Chat' => function(\Zend\ServiceManager\ServiceManager $sm) {
                return new \BlogUser\Model\Chat($sm);
            },
            'BlogUser\Model\Discussion' => function(\Zend\ServiceManager\ServiceManager $sm) {
                return new \BlogUser\Model\Discussion($sm);
            },
            'BlogUser\Model\EducationalDegree' => function(\Zend\ServiceManager\ServiceManager $sm) {
                return new \BlogUser\Model\EducationalDegree($sm);
            },
            'BlogUser\Model\Email' => function(\Zend\ServiceManager\ServiceManager $sm) {
                return new \BlogUser\Model\Email($sm);
            },
            'BlogUser\Model\Episode' => function(\Zend\ServiceManager\ServiceManager $sm) {
                return new \BlogUser\Model\Episode($sm);
            },
            'BlogUser\Model\EpisodicPost' => function(\Zend\ServiceManager\ServiceManager $sm) {
                return new \BlogUser\Model\EpisodicPost($sm);
            },
            'BlogUser\Model\EpisodeSerial' => function(\Zend\ServiceManager\ServiceManager $sm) {
                return new \BlogUser\Model\EpisodeSerial($sm);
            },
            'BlogUser\Model\EpisodeStyle' => function(\Zend\ServiceManager\ServiceManager $sm) {
                return new \BlogUser\Model\EpisodeStyle($sm);
            },
            'BlogUser\Model\Friend' => function(\Zend\ServiceManager\ServiceManager $sm) {
                return new \BlogUser\Model\Friend($sm);
            },
            'BlogUser\Model\Group' => function(\Zend\ServiceManager\ServiceManager $sm) {
                return new \BlogUser\Model\Group($sm);
            },
            'BlogUser\Model\Hidden' => function(\Zend\ServiceManager\ServiceManager $sm) {
                return new \BlogUser\Model\Hidden($sm);
            },
            'BlogUser\Model\Mood' => function(\Zend\ServiceManager\ServiceManager $sm) {
                return new \BlogUser\Model\Mood($sm);
            },
            'BlogUser\Model\NoticeUser' => function(\Zend\ServiceManager\ServiceManager $sm) {
                return new \BlogUser\Model\NoticeUser($sm);
            },
            'BlogUser\Model\Notification' => function(\Zend\ServiceManager\ServiceManager $sm) {
                return new \BlogUser\Model\Notification($sm);
            },
            'BlogUser\Model\NovelName' => function(\Zend\ServiceManager\ServiceManager $sm) {
                return new \BlogUser\Model\NovelName($sm);
            },
            'BlogUser\Model\NotificationUser' => function(\Zend\ServiceManager\ServiceManager $sm) {
                return new \BlogUser\Model\NotificationUser($sm);
            },
            'BlogUser\Model\OtherSetting' => function(\Zend\ServiceManager\ServiceManager $sm) {
                return new \BlogUser\Model\OtherSetting($sm);
            },
            'BlogUser\Model\OtherSettings' => function(\Zend\ServiceManager\ServiceManager $sm) {
                return new \BlogUser\Model\OtherSettings($sm);
            },
            'BlogUser\Model\PreviousUserInfo' => function(\Zend\ServiceManager\ServiceManager $sm) {
                return new \BlogUser\Model\PreviousUserInfo($sm);
            },
            'BlogUser\Model\ProfilePicture' => function(\Zend\ServiceManager\ServiceManager $sm) {
                return new \BlogUser\Model\ProfilePicture($sm);
            },
            'BlogUser\Model\Report' => function(\Zend\ServiceManager\ServiceManager $sm) {
                return new \BlogUser\Model\Report($sm);
            },
            'BlogUser\Model\SocialMedia' => function(\Zend\ServiceManager\ServiceManager $sm) {
                return new \BlogUser\Model\SocialMedia($sm);
            },
            'BlogUser\Model\Subscribe' => function(\Zend\ServiceManager\ServiceManager $sm) {
                return new \BlogUser\Model\Subscribe($sm);
            },
            'BlogUser\Model\TempPicture' => function(\Zend\ServiceManager\ServiceManager $sm) {
                return new \BlogUser\Model\TempPicture($sm);
            },
            'BlogUser\Model\User' => function(\Zend\ServiceManager\ServiceManager $sm) {
                return new \BlogUser\Model\User($sm);
            },
            'BlogUser\Model\UserBanner' => function(\Zend\ServiceManager\ServiceManager $sm) {
                return new \BlogUser\Model\UserBanner($sm);
            },
            'BlogUser\Model\UserSocialMedia' => function(\Zend\ServiceManager\ServiceManager $sm) {
                return new \BlogUser\Model\UserSocialMedia($sm);
            },
        ),
    ),
);