<?php

function _rssmedia_galleriffic_register_scripts ($template_url) {
  $url = $template_url . 'js/jquery-galleriffic.js';

  wp_register_script('rssmedia-galleriffic',
                     $url,
                     array('jquery'),
                     false,
                     true);
}

function _rssmedia_galleriffic_register_styles ($template_url) {
  $url = $template_url . 'css/styles.css';

  wp_register_style('rssmedia-galleriffic', $url);
}

function _rssmedia_galleriffic_enqueue_scripts () {
  wp_enqueue_script('rssmedia-galleriffic');
}

function _rssmedia_galleriffic_enqueue_styles () {
  wp_enqueue_style('rssmedia-galleriffic');
}

if (function_exists('_rssmedia_register_template'))
  _rssmedia_register_template('galleriffic', array(
    'name' => 'Galleriffic',
    'register_scripts' => '_rssmedia_galleriffic_register_scripts',
    'register_styles' => '_rssmedia_galleriffic_register_styles',
    'enqueue_scripts' => '_rssmedia_galleriffic_enqueue_scripts',
    'enqueue_styles' => '_rssmedia_galleriffic_enqueue_styles'
  ));
