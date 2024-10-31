<?php

function _rssmedia_featured_slideshow_register_scripts ($template_url) {
  $url = $template_url . 'js/jquery-tools-min.js';

  wp_register_script('rssmedia-featured-slideshow',
                     $url,
                     array('jquery'),
                     false,
                     true);
}

function _rssmedia_featured_slideshow_register_styles ($template_url) {
  $url = $template_url . 'css/styles.css';

  wp_register_style('rssmedia-featured-slideshow', $url);
}

function _rssmedia_featured_slideshow_enqueue_scripts () {
  wp_enqueue_script('rssmedia-featured-slideshow');
}

function _rssmedia_featured_slideshow_enqueue_styles () {
  wp_enqueue_style('rssmedia-featured-slideshow');
}

function _rssmedia_featured_slideshow_process_items ($items, $template_id) {
  $before = _rssmedia_get_before_part($template_id);

  $tabs = _rssmedia_get_before_part($template_id, 'before-tabs.php');
  $images = _rssmedia_get_before_part($template_id, 'before-images.php');

  foreach ($items as $item) {
    $tabs
      .= _rssmedia_get_content_part($template_id, $item, 'content-tabs.php');

    $images
      .= _rssmedia_get_content_part($template_id, $item, 'content-images.php');
  }

  $tabs .= _rssmedia_get_after_part($template_id, 'after-tabs.php');
  $images .= _rssmedia_get_after_part($template_id, 'after-images.php');

  return $before . $tabs . $images . _rssmedia_get_after_part($template_id);
}

if (function_exists('_rssmedia_register_template'))
  _rssmedia_register_template('featured-slideshow', array(
    'name' => 'Featured products (slideshow)',
    'register_scripts' => '_rssmedia_featured_slideshow_register_scripts',
    'register_styles' => '_rssmedia_featured_slideshow_register_styles',
    'enqueue_scripts' => '_rssmedia_featured_slideshow_enqueue_scripts',
    'enqueue_styles' => '_rssmedia_featured_slideshow_enqueue_styles',
    'process_items' => '_rssmedia_featured_slideshow_process_items'
  ));
