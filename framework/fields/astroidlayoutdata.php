<?php

/**
 * @package   Astroid Framework
 * @author    Astroid Framework Team https://astroidframe.work
 * @copyright Copyright (C) 2023 AstroidFrame.work.
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or Later
 */
defined('JPATH_PLATFORM') or die;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Astroid\Element\Layout;
use Joomla\Registry\Registry;
use Astroid\Helper;

/**
 * Form Field class for the Joomla Platform.
 * Supports a generic list of options.
 *
 * @since  11.1
 */
class JFormFieldAstroidLayoutData extends FormField {

    /**
     * The form field type.
     *
     * @var    string
     * @since  11.1
     */
    protected $type = 'AstroidLayoutData';
    protected $ordering;

    protected function getInput() {
        $params = new Registry();
        $app = Factory::getApplication();
        if ($app->input->get('view', '') == 'article') {
            $id = $app->input->get('id');
            if (empty($id)) {
                return Text::_('ASTROID_ARTICLE_NOTICE_EMPTY_ID');
            }
            $db = Factory::getContainer()->get(DatabaseInterface::class);
            $query = $db->getQuery(true);
            $query->select($db->quoteName(array('catid')));
            $query->from($db->quoteName('#__content'));
            $query->where($db->quoteName('id') . ' = ' . $id);
            $db->setQuery($query);
            $catid = $db->loadResult();

            $query = $db->getQuery(true);
            $query->select($db->quoteName(array('params')));
            $query->from($db->quoteName('#__categories'));
            $query->where($db->quoteName('id') . ' = ' . $catid);
            $db->setQuery($query);
            $result = $db->loadObject();
            if (!empty($result)) {
                $params->loadString($result->params, 'JSON');
                $article_layout = json_decode($params->get('astroid_article_layout', '{"template":"","layout":""}'));
                if (!$article_layout->template) {
                    $query = $db->getQuery(true);
                    $query->select($db->quoteName(array('template')));
                    $query->from($db->quoteName('#__template_styles'));
                    $query->where($db->quoteName('home') . ' = 1');
                    $query->where($db->quoteName('client_id') . ' = 0');
                    $db->setQuery($query);
                    $article_layout->template = $db->loadResult();
                }
                $sublayout      = Layout::getDataLayout($article_layout->layout, $article_layout->template, 'article_layouts');
                if (!isset($sublayout['data']) || !$sublayout['data']) {
                    return Text::_('ASTROID_ARTICLE_WARNING_LAYOUT_EMPTY');
                }
                define('ASTROID_TEMPLATE_NAME', $article_layout->template);
                $widgets    =   array();
                $form_template  =   Helper::getFormTemplate('article_data');
                $layout     =   json_decode($sublayout['data'], true);
                foreach ($layout['sections'] as $section) {
                    foreach ($section['rows'] as $row) {
                        foreach ($row['cols'] as $col) {
                            foreach ($col['elements'] as $element) {
                                if ($form_template[$element['type']]['info']['element_type'] == 'widget') {
                                    $widgets[]  =   $element;
                                }
                            }
                        }
                    }
                }
                $json = [
                    'article_id' => $id,
                    'widgets' => $widgets,
                    'constant'   => Helper\Constants::manager_configs(),
                    'form_template' => $form_template,
                    'template' => $article_layout->template
                ];
                $html   =   '<script type="application/json" id="'.$this->id.'_json">'.json_encode($json).'</script>';
                $html   .=  '<div class="as-article-widget-data" id="'.$this->id.'"></div>';
                $wa = Factory::getApplication()->getDocument()->getWebAssetManager();
                $wa->registerAndUseStyle('astroid.article.widget.data', "media/astroid/assets/vendor/articlelayout/dist/index.css");
                $wa->useScript('bootstrap.tab');
                $wa->registerAndUseScript('astroid.article.widget.data', 'media/astroid/assets/vendor/articlelayout/dist/index.js', ['relative' => true, 'version' => 'auto'], ['type' => 'module']);
                return $html;
            }
        }
        return Text::_('ASTROID_ARTICLE_NOT_ARTICLE');
    }
}