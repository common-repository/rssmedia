<?php

function _rssmedia_jcarousel_register_scripts ($template_url) {
  $url = $template_url . 'js/jquery-jcarousel-core-min.js';

  wp_register_script('rssmedia-jcarousel', $url, array('jquery'), false, true);
}

function _rssmedia_jcarousel_register_styles ($template_url) {
  $url = $template_url . 'css/styles.css';

  wp_register_style('rssmedia-jcarousel', $url);
}

function _rssmedia_jcarousel_enqueue_scripts () {
  wp_enqueue_script('rssmedia-jcarousel');
}

function _rssmedia_jcarousel_enqueue_styles () {
  wp_enqueue_style('rssmedia-jcarousel');
}

if (function_exists('_rssmedia_register_template'))
  _rssmedia_register_template('jcarousel', array(
    'name' => 'Carousel (jCarousel)',
    'register_scripts' => '_rssmedia_jcarousel_register_scripts',
    'register_styles' => '_rssmedia_jcarousel_register_styles',
    'enqueue_scripts' => '_rssmedia_jcarousel_enqueue_scripts',
    'enqueue_styles' => '_rssmedia_jcarousel_enqueue_styles'
  ));
