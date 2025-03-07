<?php

/**
 * @package   Astroid Framework
 * @author    Astroid Framework Team https://astroidframe.work
 * @copyright Copyright (C) 2023 AstroidFrame.work.
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or Later
 */
namespace Astroid;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\Registry\Registry;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseInterface;
use Joomla\Component\Content\Site\Helper\RouteHelper;
use Astroid\Element\Layout;

defined('_JEXEC') or die;

class Article
{

    public $type;
    public $article;
    public $params;
    public $attribs;
    public $template;
    public $other_params = null;
    public $category_params;
    public array $styles;

    function __construct($article, $categoryView = false, $other_params = null)
    {
        if (!$categoryView) {
            $this->other_params = $other_params;
        }
        $this->article = $article;
        $attribs = new Registry();
        $attribs->loadString($this->article->attribs, 'JSON');
        $this->attribs = $attribs;
        if (empty($this->article->params)) {
            $this->article->params = new Registry();
        }
        $this->article->params->merge($attribs);

        $this->getCategoryParams();

        $this->type = $this->article->params->get('astroid_article_type', 'regular');
        $this->template = Framework::getTemplate();

        $mainframe = Factory::getApplication();
        $this->params = new Registry();
        $itemId = $mainframe->input->get('Itemid', 0, 'INT');
        if ($itemId) {
            $menu = $mainframe->getMenu();
            $item = $menu->getItem($itemId);
            if (isset($item->query) && is_array($item->query) && $item->query['option'] == 'com_content' && ($item->query['view'] == 'category' || $item->query['view'] == 'article' || $item->query['view'] == 'featured')) {
                $this->params = $item->getParams();
            }
        }
        if (!$categoryView) {
            $this->addMeta();
            $this->renderRating();
        }
    }

    public function addMeta()
    {
        $app = Factory::getApplication();
        $itemid = $app->input->get('Itemid', '', 'INT');
        $menu = $app->getMenu();
        $item = $menu->getItem($itemid);

        if (!empty($item)) {
            $params = $item->getParams();

            $enabled = $params->get('astroid_opengraph_menuitem', 0);
            $enabled = (int) $enabled;
            if (!empty($enabled)) {
                return;
            }
        }

        if (!(Factory::getApplication()->input->get('option', '') == 'com_content' && Factory::getApplication()->input->get('view', '') == 'article')) {
            return;
        }

        $enabled = $this->template->params->get('article_opengraph', 1);
        $fb_id = $this->template->params->get('article_opengraph_facebook', '');
        $tw_id = $this->template->params->get('article_opengraph_twitter', '');

        if (empty($enabled)) {
            return;
        }

        $config = Factory::getApplication()->getConfig();

        $og_title = $this->article->title;
        if (!empty($this->article->params->get('astroid_og_title', ''))) {
            $og_title = $this->article->params->get('astroid_og_title', '');
        }
        $og_description = $this->article->metadesc;
        if (!empty($this->article->params->get('astroid_og_desc', ''))) {
            $og_description = $this->article->params->get('astroid_og_desc', '');
        }
        $images = json_decode($this->article->images);
        if (isset($images->image_fulltext) && !empty($images->image_fulltext)) {
            $img = Helper::cleanImageUrl($images->image_fulltext);
            $og_image = Uri::base() . htmlspecialchars($img->url, ENT_COMPAT, 'UTF-8');
        } elseif (isset($images->image_intro) && !empty($images->image_intro)) {
            $img = Helper::cleanImageUrl($images->image_intro);
            $og_image = Uri::base() . htmlspecialchars($img->url, ENT_COMPAT, 'UTF-8');
        } else {
            $og_image = '';
        }

        if (!empty($this->article->params->get('astroid_og_image', ''))) {
            $og_image = Uri::base() . $this->article->params->get('astroid_og_image', '');
        }

        $og_sitename = $config->get('sitename');
        $og_siteurl = Route::_(RouteHelper::getArticleRoute($this->article->slug, $this->article->catid, $this->article->language), true, 0, true) ;

        $meta = [];
        $meta[] = '<meta property="og:type" content="article">';
        $meta[] = '<meta name="twitter:card" content="' . $this->template->params->get('twittercardtype', 'summary_large_image') . '" />';
        if (!empty($og_title)) {
            $meta[] = '<meta property="og:title" content="' . htmlentities($og_title, ENT_QUOTES, "UTF-8", false) . '">';
        }
        if (!empty($og_sitename)) {
            $meta[] = '<meta property="og:site_name" content="' . $og_sitename . '">';
        }
        if (!empty($og_siteurl)) {
            $meta[] = '<meta property="og:url" content="' . $og_siteurl . '">';
        }
        if (!empty($og_description)) {
            $meta[] = '<meta property="og:description" content="' . substr($og_description, 0, 200) . '">';
        }
        if (!empty($og_image)) {
            $meta[] = '<meta property="og:image" content="' . $og_image . '">';
        }
        if (!empty($fb_id)) {
            $meta[] = '<meta property="fb:app_id" content="' . $fb_id . '" />';
        }
        if (!empty($tw_id)) {
            $meta[] = '<meta name="twitter:creator" content="@' . $tw_id . '" />';
        }
        $meta = implode('', $meta);
        if (!empty($meta)) {
            $document = Factory::getApplication()->getDocument();
            $document->addCustomTag($meta);
        }
    }

    public function render($position = 'above-title')
    {
        if ($position) {
            if ($this->type == 'regular') {
                return false;
            }

            $contenPosition = $this->attribs->get('astroid_article_content_position', 'above-title');

            if ($contenPosition != $position) {
                return false;
            }
        }

        Framework::getDocument()->include('blog.' . $this->type, ['article' => $this->article]);
    }

    public function renderLayout() {
        $article_layout = json_decode($this->category_params->get('astroid_article_layout', '{"template":"","layout":""}'));
        echo Layout::renderSublayout($article_layout->layout, $article_layout->template, 'article_layouts', ['article' => $this]);
    }

    // Read Time
    public function renderReadTime()
    {
        if ($this->showReadTime()) {
            $this->article->readtime = $this->calculateReadTime($this->article->introtext.$this->article->fulltext);
            Framework::getDocument()->include('blog.modules.readtime', ['article' => $this->article]);
        }
    }

    public function showReadTime()
    {
        if (Factory::getApplication()->input->get('tmpl', '') === 'component') {
            return FALSE;
        }

        $view  = Factory::getApplication()->input->get('view', '');
        if ($view != 'category' && $view != 'featured') {
            // for single
            $article_level = $this->attribs->get('astroid_readtime', ''); // from article
            $category_level = $this->category_params->get('astroid_readtime', ''); // from article
            $astroid_level = $this->template->params->get('astroid_article_readtime', 1);
        } else {
            // for listing
            $article_level = $this->params->get('astroid_readtime', ''); // from menu
            $category_level = '';
            $astroid_level = $this->template->params->get('astroid_readtime', 1);
        }
        return $this->checkPriority($article_level, $category_level, $astroid_level);
    }

    // Social Share
    public function renderSocialShare()
    {
        if ($this->showSocialShare()) {
            Framework::getDocument()->include('blog.modules.social', ['article' => $this->article]);
        }
    }

    public function showSocialShare()
    {

        if (Factory::getApplication()->input->get('tmpl', '') === 'component') {
            return FALSE;
        }

        $article_level = $this->attribs->get('astroid_socialshare', '');
        $article_level = $article_level == 1 ? '' : $article_level;
        $category_level = $this->category_params->get('astroid_socialshare', '');
        $category_level = $category_level == 1 ? '' : $category_level;

        $astroid_level = $this->template->params->get('article_socialshare_type', "none");
        $astroid_level = $astroid_level == 'none' ? 0 : 1;
        return $this->checkPriority($article_level, $category_level, $astroid_level);
    }

    // Comments
    public function renderComments()
    {
        if ($this->showComments()) {
            Framework::getDocument()->include('blog.modules.comments', ['article' => $this->article]);
        }
    }

    public function showComments()
    {
        if (Factory::getApplication()->input->get('tmpl', '') === 'component') {
            return FALSE;
        }
        $category_level = $this->category_params->get('astroid_comments', '');
        $category_level = $category_level == 1 ? '' : $category_level;
        $article_level = $this->article->params->get('astroid_comments', '');
        $article_level = $article_level == 1 ? '' : $article_level;
        $astroid_level = $this->template->params->get('article_comments', "none");
        $astroid_level = $astroid_level == 'none' ? 0 : 1;
        return $this->checkPriority($article_level, $category_level, $astroid_level);
    }

    // Related Posts
    public function renderRelatedPosts()
    {
        if ($this->showRelatedPosts()) {
            $article_relatedposts_count = $this->attribs->get('article_relatedposts_count', '');
            $category_relatedposts_count = $this->category_params->get('article_relatedposts_count', '');

            if ($this->attribs->get('astroid_relatedposts', '') === '' && $this->category_params->get('astroid_relatedposts', '') === '') {
                $count = $this->template->params->get('article_relatedposts_count', 4);
            } else if ($this->attribs->get('astroid_relatedposts', '') === '' && $this->category_params->get('astroid_relatedposts', '') !== '') {
                if ($category_relatedposts_count === '') {
                    $count = $this->template->params->get('article_relatedposts_count', 4);
                } else {
                    $count = $this->category_params->get('article_relatedposts_count_custom', 4);
                }
            } else if ($this->attribs->get('astroid_relatedposts', '') !== '') {
                if ($article_relatedposts_count === '' && $category_relatedposts_count === '') {
                    $count = $this->template->params->get('article_relatedposts_count', 4);
                } else if ($article_relatedposts_count === '' && $category_relatedposts_count !== '') {
                    $count = $this->category_params->get('article_relatedposts_count_custom', 4);
                } else if ($article_relatedposts_count !== '') {
                    $count = $this->attribs->get('article_relatedposts_count_custom', 4);
                } else {
                    $count = $this->template->params->get('article_relatedposts_count', 4);
                }
            }

            $params = new Registry();
            $params->loadArray(['maximum' => $count]);

            $items = Factory::getApplication()->bootModule('mod_related_items', 'site')->getHelper('RelatedItemsHelper')->getRelatedArticles($params, Factory::getApplication());

            Framework::getDocument()->include('blog.modules.related', ['items' => $items, 'display_posttypeicon' => $this->showRelatedPostTypeIcon(), 'display_badge' => $this->showRelatedArticleBadge()]);
        }
    }

    public function showRelatedPosts()
    {
        if (Factory::getApplication()->input->get('tmpl', '') === 'component') {
            return FALSE;
        }
        $article_level = $this->attribs->get('astroid_relatedposts', '');
        $category_level = $this->category_params->get('astroid_relatedposts', '');
        $astroid_level = $this->template->params->get('article_relatedposts', 1);
        return $this->checkPriority($article_level, $category_level, $astroid_level);
    }

    // Author Info
    public function renderAuthorInfo()
    {
        if ($this->showAuthorInfo()) {
            Framework::getDocument()->include('blog.modules.author_info', ['article' => $this->article]);
        }
    }

    public function showAuthorInfo()
    {
        if (Factory::getApplication()->input->get('tmpl', '') === 'component') {
            return FALSE;
        }
        $article_level = $this->attribs->get('astroid_authorinfo', '');
        $category_level = $this->category_params->get('astroid_authorinfo', '');
        $astroid_level = $this->template->params->get('article_authorinfo', 1);
        return $this->checkPriority($article_level, $category_level, $astroid_level);
    }

    // menu level article badge
    public function renderArticleBadge()
    {
        if ($this->showArticleBadge()) {
            Framework::getDocument()->include('blog.modules.badge', ['article' => $this->article]);
        }
    }

    public function showArticleBadge()
    {
        if (Factory::getApplication()->input->get('tmpl', '') === 'component') {
            return FALSE;
        }
        if (Factory::getApplication()->input->get('option', '') === 'com_content' && Factory::getApplication()->input->get('view', '') === 'article') {
            return FALSE;
        }
        $article_level = $this->article->params->get('astroid_article_badge', 0);
        if (!$article_level) {
            return false;
        }
        $menu_level = $this->params->get('astroid_badge', '');
        $astroid_level = $this->template->params->get('astroid_badge', 1);
        $return =  $this->checkPriority('', $menu_level, $astroid_level);
        return $return;
    }

    public function showRelatedArticleBadge()
    {
        if ($this->attribs->get('astroid_relatedposts', '') === '') {
            $article_level = '';
        } else {
            $article_level = $this->attribs->get('article_relatedposts_badge', '');
        }
        if ($this->category_params->get('astroid_relatedposts', '') === '') {
            $category_level = '';
        } else {
            $category_level = $this->category_params->get('article_relatedposts_badge', '');
        }
        if ($this->template->params->get('article_relatedposts', 1)) {
            $astroid_level = $this->template->params->get('article_relatedposts_badge', 1);
        } else {
            $astroid_level = 0;
        }
        $return =  $this->checkPriority($article_level, $category_level, $astroid_level);
        return $return;
    }


    // Post Type Icon
    public function renderPostTypeIcon()
    {
        if ($this->showPostTypeIcon()) {
            Framework::getDocument()->include('blog.modules.posttype', ['article' => $this->article]);
        }
    }

    public function showPostTypeIcon()
    {
        if (Factory::getApplication()->input->get('tmpl', '') === 'component') {
            return FALSE;
        }
        if (Factory::getApplication()->input->get('option', '') === 'com_content' && Factory::getApplication()->input->get('view', '') === 'article') {
            return FALSE;
        }
        $menu_level = $this->params->get('astroid_posttype', '');
        $article_level = $this->article->params->get('astroid_posttype', '');
        $astroid_level = $this->template->params->get('article_posttype', 1);
        $view  = Factory::getApplication()->input->get('view', '');
        switch ($astroid_level) {
            case 2:
                if ($view === 'article') {
                    $astroid_level = 1;
                    echo "enterd to article view only";
                }
                break;
            case 3:
                if ($view === 'category' || $view === 'featured') {
                    $astroid_level = 1;
                }
                break;
        }
        return $this->checkPriority($menu_level, $article_level, $astroid_level);
    }

    public function showRelatedPostTypeIcon()
    {
        if ($this->attribs->get('astroid_relatedposts', '') === '') {
            $article_level = '';
        } else {
            $article_level = $this->attribs->get('article_relatedposts_posttype', '');
        }
        if ($this->category_params->get('astroid_relatedposts', '') === '') {
            $category_level = '';
        } else {
            $category_level = $this->category_params->get('article_relatedposts_posttype', '');
        }
        if ($this->template->params->get('article_relatedposts', 1)) {
            $astroid_level = $this->template->params->get('article_relatedposts_posttype', 1);
        } else {
            $astroid_level = 0;
        }
        return $this->checkPriority($article_level, $category_level, $astroid_level);
    }

    public function renderRating()
    {
        if ($this->showRating()) {
            $document = Framework::getDocument();
            $document->addScript('//cdn.jsdelivr.net/npm/semantic-ui@2.4.0/dist/components/rating.min.js', 'body');
            $document->addStyleSheet('//cdn.jsdelivr.net/npm/semantic-ui@2.4.0/dist/components/rating.min.css');
        }
    }

    public function showRating()
    {
        if (Factory::getApplication()->input->get('tmpl', '') === 'component') {
            return FALSE;
        }

        $option = Factory::getApplication()->input->get('option', '');
        $view = Factory::getApplication()->input->get('view', '');
        if ($option == 'com_content' && ($view == 'featured' || $view == 'category')) {
            return FALSE;
        }

        if (!$this->article->params->get('show_vote', 0)) {
            return FALSE;
        }

        $astroid_level = $this->template->params->get('article_rating', 1);
        return $astroid_level ? true : false;
    }

    /**
     * Add Classes for a position defined by User
     * @param string $position
     * @param array $classes
     */
    public function addStyle(string $position = '', array $classes = [])
    {
        if ($position && count($classes)) {
            if (!isset($this->styles[$position])) {
                $this->styles[$position] = $classes;
            } else {
                $this->styles[$position] = array_merge($this->styles[$position], $classes);
            }
        }
    }

    /**
     * Get classes from a position defined by user
     * @param string $position
     * @return string
     */
    public function getStyle(string $position = '') : string
    {
        if ($position && isset($this->styles[$position]) && count($this->styles[$position])) {
            return implode(' ', $this->styles[$position]);
        } else {
            return '';
        }
    }

    // Utility Functions
    public function checkPriority($firstPriority, $secondPriority, $thirdPriority)
    {
        $firstPriority = $firstPriority === '' ? -1 : (int) $firstPriority;
        $secondPriority = $secondPriority === '' ? -1 : (int) $secondPriority;
        $thirdPriority = $thirdPriority === '' ? -1 : (int) $thirdPriority;

        $enabled = false;
        switch ($firstPriority) {
            case -1:
                switch ($secondPriority) {
                    case -1:
                        switch ($thirdPriority) {
                            case 1:
                                $enabled = true;
                                break;
                            case 0:
                                $enabled = false;
                                break;
                        }
                        break;
                    case 1:
                        $enabled = true;
                        break;
                    case 0:
                        $enabled = false;
                        break;
                }
                break;
            case 1:
                $enabled = true;
                break;
            case 0:
                $enabled = false;
                break;
        }
        return $enabled;
    }

    public function calculateReadTime($string)
    {
        $speed = 170;
        $word = str_word_count(strip_tags($string));
        $m = floor($word / $speed);
        $s = floor($word % $speed / ($speed / 60));

        if ($m < 1) {
            $m = 1;
        } else if ($s > 30) {
            $m = $m;
        } else {
            $m++;
        }
        if ($m == 1) {
            return Text::sprintf('ASTROID_ARTICLE_READTIME_MINUTE', $m);
        } else {
            return Text::sprintf('ASTROID_ARTICLE_READTIME_MINUTES', $m);
        }
    }

    public function getTemplateParams()
    {
        return $this->template->params;
    }

    public function getImage()
    {
        $type = $this->article->params->get('astroid_article_type', 'regular');
        $thumbnail = '';
        switch ($type) {
            case 'video':
                $thumbnail = $this->getVideoThumbnail();
                break;
            case 'gallery':
                $thumbnail = $this->getGalleryThumbnail();
                break;
        }
        $images = json_decode($this->article->images);
        if (isset($images->image_intro) && !empty($images->image_intro) && empty($thumbnail)) {
            $thumbnail = true;
        }
        return $thumbnail;
    }

    public function getGalleryThumbnail()
    {
        $enabled = $this->article->params->get('astroid_article_thumbnail', 1);
        if (!$enabled) {
            return FALSE;
        }
        $items = $this->article->params->get('astroid_article_gallery_items', []);
        if (empty($items)) {
            return '';
        }
        $first_element = NULL;
        foreach ($items as $item) {
            $first_element = $item;
            break;
        }
        return Uri::root() . $first_element['image'];
    }

    public function getVideoThumbnail()
    {
        $enabled = $this->article->params->get('astroid_article_thumbnail', 1);
        if (!$enabled) {
            return FALSE;
        }
        $type = $this->article->params->get('astroid_article_video_type', 'youtube');
        $return = '';
        $id = $this->article->params->get('astroid_article_video_url', '');
        if (empty($id)) {
            return $return;
        }
        $id = self::getVideoId($id, $type);
        switch ($type) {
            case 'youtube':
                $return = '//img.youtube.com/vi/' . $id . '/maxresdefault.jpg';
                break;
            case 'vimeo':
                $return = self::getVimeoThumbnailByID($id);
                break;
        }
        return $return;
    }

    public static function getVimeoThumbnailByID($vid)
    {
        $hash = unserialize(file_get_contents("https://vimeo.com/api/v2/video/" . $vid . ".php"));
        $thumbnail = $hash[0]['thumbnail_large'];
        return $thumbnail;
    }

    public static function getVideoId($url, $type)
    {
        $parts = parse_url($url);
        if ($type == "youtube") {
            if (isset($parts['query'])) {
                parse_str($parts['query'], $query);
            }
            return (isset($query['v']) ? $query['v'] : '');
        } else {
            return (isset($parts['path']) ? str_replace('/', '', $parts['path']) : '');
        }
    }

    public static function getArticleRating($id)
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = "SELECT * FROM `#__content_rating` WHERE `content_id`='$id'";
        $db->setQuery($query);
        $result = $db->loadObject();
        if (empty($result)) {
            return 0;
        } else {
            return ceil($result->rating_sum / $result->rating_count);
        }
    }

    public static function getImageWidth ($params = null, $type = 'lead', $idx = 0) {
        $image                  =   array();
        $image['position']      =   $params->get('image_'.$type.'_position','top');
        if ($image['position'] == 'zigzag') {
            if ($idx % 2 == 0) {
                $image['position'] = 'left';
            } else {
                $image['position'] = 'right';
            }
        }
        if ($image['position'] == 'left' || $image['position'] == 'right') {
            $image['default']   =   ' col-'. $params->get('image_'.$type.'_width','12');
            $image['xl']        =   ' col-xl-'. $params->get('image_'.$type.'_width_xl','4');
            $image['lg']        =   ' col-lg-'. $params->get('image_'.$type.'_width_l','4');
            $image['md']        =   ' col-md-'. $params->get('image_'.$type.'_width_m','6');
            $image['sm']        =   ' col-sm-'. $params->get('image_'.$type.'_width_s','12');
            $image['expand']    =   '';
            if ($image['xl'] == ' col-xl-12') {
                $image['expand']    =   ' margin-xl-0';
            } elseif ($image['position'] == 'right') {
                $image['xl']    .=  ' order-xl-1';
            }
            if ($image['lg'] == ' col-lg-12') {
                $image['expand']    .=  ' margin-lg-0';
            } elseif ($image['position'] == 'right') {
                $image['lg']    .=  ' order-lg-1';
            }
            if ($image['md'] == ' col-md-12') {
                $image['expand']    .=  ' margin-md-0';
            } elseif ($image['position'] == 'right') {
                $image['md']    .=  ' order-md-1';
            }
            if ($image['sm'] == ' col-sm-12') {
                $image['expand']    .=  ' margin-sm-0';
            } elseif ($image['position'] == 'right') {
                $image['sm']    .=  ' order-sm-1';
            }
            if ($image['default'] == ' col-12') {
                $image['expand']    .=  ' margin-0';
            } elseif ($image['position'] == 'right') {
                $image['default']    .=  ' order-1';
            }
        }
        return $image;
    }

    public function getCategoryParams()
    {
        $params = new Registry();
        if (Factory::getApplication()->input->get('view', '') == 'article' && !empty($this->article->catid)) {
            $db = Factory::getContainer()->get(DatabaseInterface::class);
            $query = "SELECT `params` FROM `#__categories` WHERE `id`=" . $this->article->catid;
            $db->setQuery($query);
            $result = $db->loadObject();
            if (!empty($result) && !empty($result->params)) {
                $params->loadString($result->params, 'JSON');
            }
        }
        $this->category_params = $params;
    }
}
