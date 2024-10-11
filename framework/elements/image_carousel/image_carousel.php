<?php

/**
 * @package   Astroid Framework
 * @author    Astroid Framework Team https://astroidframe.work
 * @copyright Copyright (C) 2023 AstroidFrame.work.
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or Later
 * DO NOT MODIFY THIS FILE DIRECTLY AS IT WILL BE OVERWRITTEN IN THE NEXT UPDATE
 * You can easily override all files under /astroid/ folder.
 * Just copy the file to JROOT/templates/YOUR_ASTROID_TEMPLATE/astroid/elements/module_position/module_position.php folder to create and override
 */

// No direct access.
defined('_JEXEC') or die;

use Astroid\Framework;
use Astroid\Helper\Style;
use Joomla\CMS\Uri\Uri;

extract($displayData);
$images       = $params->get('images', '');
if (empty($images)) {
    return false;
}
$images       = json_decode($images);
if (!count($images)) {
    return false;
}

$enable_slider      =   $params->get('enable_slider', 0);
$use_masonry        =   $params->get('use_masonry', 0);
$use_lightbox       =   $params->get('use_lightbox', 0);
$enable_image_cover =   $params->get('enable_image_cover', 0);
$min_height         =   $params->get('min_height', 500);
$height             =   $params->get('height', '');
$slider_autoplay    =   $params->get('slider_autoplay', 0);
$slider_nav         =   $params->get('slider_nav', 1);
$slider_dotnav      =   $params->get('slider_dotnav', 0);
$slider_scrollbar   =   $params->get('slider_scrollbar', 0);
$interval           =   $params->get('interval', 3);
$slide_settings     =   array();
$slide_responsive   =   array();

$enable_column_alignment    =   $params->get('enable_column_alignment', 0);
$column_alignment           =   $params->get('column_alignment', '');
$text_alignment             =   $params->get('text_alignment', '');

$row_column_cls     =   'row';

if ($enable_column_alignment) {
    $row_column_cls .=  ' ' . $column_alignment;
}

if ($text_alignment) {
    $row_column_cls .=  ' justify-content-' . $text_alignment;
}

$responsive_key     =   [
    'xs'    => '',
    'sm'    => '576',
    'md'    => '768',
    'lg'    => '992',
    'xl'    => '1200',
    'xxl'   => '1400',
];
foreach ($responsive_key as $key => $min_width) {
    $column         =   $params->get($key . '_column', '');
    $slidesPerGroup =   $params->get($key . '_slidesPerGroup', '');
    $gutter         =   $params->get('gutter_' . $key, '10');
    if (!empty($column)) {
        if (!count($slide_settings)) {
            $slide_settings[]       =   'slidesPerView: ' . $column;
            $slide_settings[]       =   'slidesPerGroup: ' . $slidesPerGroup;
            $slide_settings[]       =   'spaceBetween: ' . $gutter;
        } elseif (!empty($min_width)) {
            $slide_responsive[]     =   $min_width . ': {slidesPerView: '.$column.',slidesPerGroup: '.$slidesPerGroup.',spaceBetween: '.$gutter.'}';
        }
    }
}

if ($slider_autoplay) {
    $slide_settings[]       =   'autoplay: {delay: '.($interval * 1000).'}';
}

if ($slider_dotnav) {
    $slide_settings[]       =   'pagination: {el: ".swiper-pagination",clickable: true,}';
}

if ($slider_nav) {
    $slide_settings[]       =   'navigation: {nextEl: ".swiper-button-next",prevEl: ".swiper-button-prev",}';
}
$speed              =   $params->get('speed', 0);
if (!empty($speed)) {
    $slide_settings[]   =   'speed:' . ($speed * 1000);
}
$loop               =   $params->get('loop', 0);
if (!empty($loop)) {
    $slide_settings[]   =   'loop:true';
}
$freemode           =   $params->get('freemode', 0);
if (!empty($freemode)) {
    $slide_settings[]   =   'freeMode: true';
}
$dir                =   $params->get('direction', '');
//$slide_settings[]   =   'autoHeight: true';
if (count($slide_responsive)) {
    $slide_settings[]       =   'breakpoints: {'.implode(',', $slide_responsive).'}';
}

$rounded_size       =   $params->get('rounded_size', '3');
$border_radius      =   $params->get('border_radius', '');
if ($border_radius == 'rounded') {
    $border_radius  = ' ' . $border_radius . '-' . $rounded_size;
} else {
    $border_radius  = $border_radius !== '' ? ' ' . $border_radius : '';
}
$box_shadow     = $params->get('box_shadow', '');
$box_shadow     = $box_shadow !== '' ? ' ' . $box_shadow : '';
$hover_effect   = $params->get('hover_effect', '');
$hover_effect   = $hover_effect !== '' ? ' as-effect-' . $hover_effect : '';

echo '<div class="swiper"'.(!empty($dir) ? ' dir="'.$dir.'"' : '').'>';
echo '<div class="swiper-wrapper">';
foreach ($images as $image) {
    $image_params   =   Style::getSubFormParams($image->params);
    if (!empty($image_params['image'])) {
        echo '<div class="swiper-slide">';
        if ($image_params['use_link']) {
            echo '<a href="'.$image_params['link'].'" title="'.$image_params['title'].'">';
        }
        echo '<div class="position-relative overflow-hidden' . ($enable_image_cover ? ' as-image-cover' : '') . $border_radius . $box_shadow . $hover_effect . '">';
        echo '<img src="'. Astroid\Helper\Media::getMediaPath($image_params['image']).'" alt="'.$image_params['title'].'" class="d-inline-block'.($enable_image_cover ? ' object-fit-cover w-100 h-100' : '').'">';
        echo '</div>';
        if ($image_params['use_link'] || $use_lightbox) {
            echo '</a>';
        }
        echo '</div>';
    }
}
echo '</div>';
if ($slider_dotnav) {
    echo '<div class="swiper-pagination"></div>';
}
if ($slider_nav) {
    echo '<div class="swiper-button-prev"></div><div class="swiper-button-next"></div>';
}
if ($slider_scrollbar) {
    echo '<div class="swiper-scrollbar"></div>';
}
echo '</div>';
$document = Framework::getDocument();

if ($use_lightbox) {
    $document->loadFancyBox();
    $document->addScriptDeclaration('Fancybox.bind(\'[data-fancybox="astroid-'.$element->id.'"]\');', 'body');
}
$document->loadSwiper('#'.$element->id.' .swiper', implode(',', $slide_settings));
if ($enable_image_cover) {
    if (!empty($height)) {
        $element->style->child('.as-image-cover')->addCss('min-height', $min_height . 'px');
        $element->style->child('.as-image-cover')->addCss('height', $height);
    } else {
        $element->style->child('.as-image-cover')->addCss('height', $min_height . 'px');
    }
}