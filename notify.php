<?php

add_action('publish_post', '_rssmedia_notify');
add_action('admin_init', '_rssmedia_notify_settings');

function _rssmedia_notify ($post_id) {
  if (!(($permalink = get_permalink($post_id))
        && ($url = get_option('rssmedia_notify_url', ''))))
    return;

  $url = esc_url_raw($url . 'url/' . urlencode($permalink));

  wp_remote_head($url, array('blocking' => false));
}

function _rssmedia_notify_settings () {
  add_settings_section(
    'rssmedia_notify_settings',
    'Notify service',
    '_rssmedia_notify_settings_content',
    'writing'
  );

  add_settings_field(
    'rssmedia_notify_url',
    'Service URL',
    '_rssmedia_notify_url_content',
    'writing',
    'rssmedia_notify_settings'
  );

  register_setting(
    'writing',
    'rssmedia_notify_url',
    '_rssmedia_notify_url_sanitize'
  );
}

function _rssmedia_notify_settings_content () {
  echo '<p>Notify following service when post is updated</p>';
}

function _rssmedia_notify_url_content () {
  $value = get_option('rssmedia_notify_url', '');

  echo '<input type="text"',
               'id="rssmedia_notify_url"',
               'class="regular-text"',
               'name="rssmedia_notify_url"',
               'value="', esc_url($value), '">';
}

function _rssmedia_notify_url_sanitize ($url) {
  $url = esc_url_raw($url);

  if (substr($url, -1) != '/')
    $url .= '/';

  return $url;
}

?>
