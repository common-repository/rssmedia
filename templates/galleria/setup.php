<?php

function _rssmedia_galleria_register_scripts ($template_url) {
  $url = $template_url . 'galleria-1.2.9.min.js';

  wp_register_script('rssmedia-galleria', $url, array('jquery'), false, true);
}

function _rssmedia_galleria_enqueue_scripts () {
  wp_enqueue_script('rssmedia-galleria');
}

if (function_exists('_rssmedia_register_template'))
  _rssmedia_register_template('galleria', array(
    'name' => 'Galleria',
    'register_scripts' => '_rssmedia_galleria_register_scripts',
    'enqueue_scripts' => '_rssmedia_galleria_enqueue_scripts'
  ));
