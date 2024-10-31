<?php

function _rssmedia_sidebar_list_register_styles ($template_url) {
  $url = $template_url . 'css/styles.css';

  wp_register_style('rssmedia-sidebar-list', $url);
}

function _rssmedia_sidebar_list_enqueue_scripts () {
  wp_enqueue_script('jquery-ui-tooltip');
}

function _rssmedia_sidebar_list_enqueue_styles () {
  wp_enqueue_style('rssmedia-sidebar-list');
}

if (function_exists('_rssmedia_register_template'))
  _rssmedia_register_template('sidebar-list', array(
    'name' => 'Sidebar (Image and text)',
    'register_styles' => '_rssmedia_sidebar_list_register_styles',
    'enqueue_scripts' => '_rssmedia_sidebar_list_enqueue_scripts',
    'enqueue_styles' => '_rssmedia_sidebar_list_enqueue_styles'
  ));
