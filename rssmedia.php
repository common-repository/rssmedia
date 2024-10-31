<?php

/**
 * Plugin Name: RSS Media
 * Description: Import and display media from feeds in various ways in your blog
 * Version: 0.2.1.1
 * License: GPLv3
 */

//avoid direct calls to this file, because now WP core and framework has been used
if ( ! function_exists('add_action') ) {
  header('Status: 403 Forbidden');
  header('HTTP/1.1 403 Forbidden');
  exit();
}

define('RSSMEDIA_LIMIT', 5);
define('RSSMEDIA_URL', 'http://example.com/feed/');

$_rssmedia_templates = array();

function _rssmedia_load_templates () {
  $path = plugin_dir_path(__FILE__) . 'templates/';

  $dirs = scandir($path);

  foreach ($dirs as $dir) {
    $template_path = $path . $dir;
    $setup_path = $template_path . '/' . 'setup.php';

    if (is_dir($template_path) && file_exists($setup_path))
      require_once $setup_path;
  }
}

function _rssmedia_register_template ($id, $settings) {
  global $_rssmedia_templates;

  if (isset($_rssmedia_templates[$id]))
    return;

  $path_prefix = 'templates/' . $id . '/';

  if (!isset($settings['path']))
    $settings['path'] = plugin_dir_path( __FILE__) . $path_prefix;

  if (!isset($settings['url']))
    $settings['url'] = plugins_url($path_prefix, __FILE__);

  $_rssmedia_templates[$id] = $settings;
}

_rssmedia_load_templates();

function _rssmedia_register_styles () {
  wp_register_style('mv-rss-style', plugins_url('css/styles.css' , __FILE__));

  global $_rssmedia_templates;

  foreach ($_rssmedia_templates as $code => $settings)
    if (isset($settings['register_styles'])
        && is_callable($settings['register_styles']))
      call_user_func($settings['register_styles'], $settings['url']);
}

function _rssmedia_register_scripts () {
  global $_rssmedia_templates;

  foreach ($_rssmedia_templates as $code => $settings)
    if (isset($settings['register_scripts'])
        && is_callable($settings['register_scripts']))
      call_user_func($settings['register_scripts'], $settings['url']);
}

add_action('wp_enqueue_scripts', '_rssmedia_register_styles');
add_action('wp_enqueue_scripts', '_rssmedia_register_scripts');

function _rssmedia_init() {

  //TODO: Load all scripts on demand
  wp_enqueue_script('jquery');
}

if ( ! function_exists('esc_attr') ) {
  function esc_attr( $text ) {
    return attribute_escape( $text );
  }
}

if ( ! function_exists('esc_url') ) {
  function esc_url($text ) {
    return clean_url($text);
  }
}

function _rssmedia_get_template_dir ($id) {
  global $_rssmedia_templates;

  if (!(isset($_rssmedia_templates[$id])
        && isset($_rssmedia_templates[$id]['path'])))
    return false;

  return $_rssmedia_templates[$id]['path'];
}

function _rssmedia_get_before_part ($template_id, $filename = 'before.php') {
  return file_get_contents(_rssmedia_get_template_dir($template_id)
                           . $filename);
}

function _rssmedia_get_after_part ($template_id, $filename = 'after.php') {
  return file_get_contents(_rssmedia_get_template_dir($template_id)
                           . $filename);
}

function _rssmedia_get_content_part ($template_id,
                                     $item,
                                     $filename = 'content.php') {

  $path = _rssmedia_get_template_dir($template_id) . $filename;

  $content = file_get_contents($path);

  $enclosure = $item->get_enclosure();

  $title = esc_attr($enclosure->get_title());

  utf8dec($title);
  all_convert($title);

  $media_price = $item->get_item_tags(SIMPLEPIE_NAMESPACE_MEDIARSS, 'price');
  $media_price = '$' . wp_filter_kses($media_price[0]['attribs']['']['price']);

  $content = str_replace("%link%", wp_filter_kses($item->get_link()), $content);
  $content = str_replace("%description%", wp_kses_post($item->get_description()), $content);
  $content = str_replace("%media:content%", wp_filter_kses($enclosure->get_link()), $content);
  $content = str_replace("%media:price%", $media_price, $content);
  $content = str_replace("%media:thumbnail%", wp_filter_kses($enclosure->get_thumbnail()), $content);
  $content = str_replace("%media:title%", $title, $content);

  return $content;
}

function _rssmedia_process_items ($items, $template_id) {
  $content = _rssmedia_get_before_part($template_id);

  foreach ($items as $item)
    $content .= _rssmedia_get_content_part($template_id, $item);

  return $content . _rssmedia_get_after_part($template_id);
}

function _rssmedia_get_func ($template, $code) {
  return isset($template[$code]) && is_callable($template[$code])
           ?  $template[$code]
             : false;
}

function RSSMedia(
    $template_id,
    $limit = RSSMEDIA_LIMIT, $feedurl = RSSMEDIA_URL
  ) {

  // replace for yahoo pipes urls
  $feedurl = str_replace('&#038;', '&', $feedurl);

  $limit = (int) $limit;

  $rss = fetch_feed($feedurl);

  if (!$rss || is_wp_error($rss)) {
    if (empty($rss->ERROR))
      $rss->ERROR = NULL;

    echo wptexturize('Error: Feed has a error or is not valid. ' . $rss->ERROR);

    return;
  }

  global $_rssmedia_templates;

  if (!isset($_rssmedia_templates[$template_id]))
    return wptexturize('RSSMedia error: no template (' . $template_id . ')');

  $template = $_rssmedia_templates[$template_id];

  wp_enqueue_style('mv-rss-style');

  if ($enqueue_scripts = _rssmedia_get_func($template, 'enqueue_scripts'))
    $enqueue_scripts();

  if ($enqueue_styles = _rssmedia_get_func($template, 'enqueue_styles'))
    $enqueue_styles();

  if (!$rss->get_item_quantity()) {
    echo wptexturize('No items, feed is empty.');

    return;
  }

  if (!$process_items = _rssmedia_get_func($template, 'process_items'))
    $process_items = '_rssmedia_process_items';

  $content = $process_items($rss->get_items(0, $limit), $template_id);

  if (strip_tags($content))
    $content = wptexturize($content);
  else
    $content = wptexturize('No items, feed is empty.');

  return $content;
}

function utf8dec($s_String) {
  if ( version_compare(phpversion(), '5.0.0', '>=') )
    $s_String = html_entity_decode(htmlentities( $s_String." ", ENT_COMPAT, 'UTF-8') );
  else
    $s_String = RSSMedia_html_entity_decode_php4( htmlentities($s_String." ") );
  return substr($s_String, 0, strlen($s_String)-1);
}

function all_convert($s_String) {

  // Array for entities
  $umlaute  = array('„','“','–',' \&#34;','&#8211;','&#8212;','&#8216;','&#8217;','&#8220;','&#8221;','&#8222;','&#8226;','&#8230;' ,'�'     ,'�'      ,'�'     ,'�'      ,'�'       ,'�'       ,'�'       ,'�'     ,'�'       ,'�'       ,'�'       ,'�'      ,'�'       ,'�'      ,'�'      ,'�'      ,'�'      ,'�'     ,'�'      ,'�'      ,'�'      ,'�'      ,'�'       ,'�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�',utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),utf8_encode('�'),chr(128),chr(129),chr(130),chr(131),chr(132),chr(133),chr(134),chr(135),chr(136),chr(137),chr(138),chr(139),chr(140),chr(141),chr(142),chr(143),chr(144),chr(145),chr(146),chr(147),chr(148),chr(149),chr(150),chr(151),chr(152),chr(153),chr(154),chr(155),chr(156),chr(157),chr(158),chr(159),chr(160),chr(161),chr(162),chr(163),chr(164),chr(165),chr(166),chr(167),chr(168),chr(169),chr(170),chr(171),chr(172),chr(173),chr(174),chr(175),chr(176),chr(177),chr(178),chr(179),chr(180),chr(181),chr(182),chr(183),chr(184),chr(185),chr(186),chr(187),chr(188),chr(189),chr(190),chr(191),chr(192),chr(193),chr(194),chr(195),chr(196),chr(197),chr(198),chr(199),chr(200),chr(201),chr(202),chr(203),chr(204),chr(205),chr(206),chr(207),chr(208),chr(209),chr(210),chr(211),chr(212),chr(213),chr(214),chr(215),chr(216),chr(217),chr(218),chr(219),chr(220),chr(221),chr(222),chr(223),chr(224),chr(225),chr(226),chr(227),chr(228),chr(229),chr(230),chr(231),chr(232),chr(233),chr(234),chr(235),chr(236),chr(237),chr(238),chr(239),chr(240),chr(241),chr(242),chr(243),chr(244),chr(245),chr(246),chr(247),chr(248),chr(249),chr(250),chr(251),chr(252),chr(253),chr(254),chr(255),chr(256));
  $htmlcode = array('&bdquo;','&ldquo;','&ndash;',' &#34;','&ndash;','&mdash;','&lsquo;','&rsquo;','&ldquo;','&rdquo;','&bdquo;','&bull;' ,'&hellip;','&euro;','&sbquo;','&fnof;','&bdquo;','&hellip;','&dagger;','&Dagger;','&circ;','&permil;','&Scaron;','&lsaquo;','&OElig;','&#x017D;','&lsquo;','&rsquo;','&ldquo;','&rdquo;','&bull;','&ndash;','&mdash;','&tilde;','&trade;','&scaron;','&rsaquo;','&oelig;','&#x017E;','&Yuml;','&iexcl;','&cent;','&pound;','&curren;','&yen;','&brvbar;','&sect;','&uml;','&copy;','&ordf;','&laquo;','&not;','&reg;','&macr;','&deg;','&plusmn;','&sup2;','&sup3;','&acute;','&micro;','&para;','&middot;','&cedil;','&supl;','&ordm;','&raquo;','&frac14;','&frac12;','&frac34;','&iquest;','&Agrave;','&Aacute;','&Acirc;','&Atilde;','&Auml;','&Aring;','&AElig;','&Ccedil;','&Egrave;','&Eacute;','&Ecirc;','&Euml;','&Igrave;','&Iacute;','&Icirc;','&Iuml;','&ETH;','&Ntilde;','&Ograve;','&Oacute;','&Ocirc;','&Otilde;','&Ouml;','&times;','&Oslash;','&Ugrave;','&Uacute;','&Ucirc;','&Uuml;','&Yacute;','&THORN;','&szlig;','&agrave;','&aacute;','&acirc;','&atilde;','&auml;','&aring;','&aelig;','&ccedil;','&egrave;','&eacute;','&ecirc;','&euml;','&igrave;','&iacute;','&icirc;','&iuml;','&eth;','&ntilde;','&ograve;','&oacute;','&ocirc;','&otilde;','&ouml;','&divide;','&oslash;','&ugrave;','&uacute;','&ucirc;','&uuml;','&yacute;','&thorn;','&yuml;','&euro;','&sbquo;','&fnof;','&bdquo;','&hellip;','&dagger;','&Dagger;','&circ;','&permil;','&Scaron;','&lsaquo;','&OElig;','&#x017D;','&lsquo;','&rsquo;','&ldquo;','&rdquo;','&bull;','&ndash;','&mdash;','&tilde;','&trade;','&scaron;','&rsaquo;','&oelig;','&#x017E;','&Yuml;','&iexcl;','&cent;','&pound;','&curren;','&yen;','&brvbar;','&sect;','&uml;','&copy;','&ordf;','&laquo;','&not;','&reg;','&macr;','&deg;','&plusmn;','&sup2;','&sup3;','&acute;','&micro;','&para;','&middot;','&cedil;','&supl;','&ordm;','&raquo;','&frac14;','&frac12;','&frac34;','&iquest;','&Agrave;','&Aacute;','&Acirc;','&Atilde;','&Auml;','&Aring;','&AElig;','&Ccedil;','&Egrave;','&Eacute;','&Ecirc;','&Euml;','&Igrave;','&Iacute;','&Icirc;','&Iuml;','&ETH;','&Ntilde;','&Ograve;','&Oacute;','&Ocirc;','&Otilde;','&Ouml;','&times;','&Oslash;','&Ugrave;','&Uacute;','&Ucirc;','&Uuml;','&Yacute;','&THORN;','&szlig;','&agrave;','&aacute;','&acirc;','&atilde;','&auml;','&aring;','&aelig;','&ccedil;','&egrave;','&eacute;','&ecirc;','&euml;','&igrave;','&iacute;','&icirc;','&iuml;','&eth;','&ntilde;','&ograve;','&oacute;','&ocirc;','&otilde;','&ouml;','&divide;','&oslash;','&ugrave;','&uacute;','&ucirc;','&uuml;','&yacute;','&thorn;','&yuml;','&euro;','','&sbquo;','&fnof;','&bdquo;','&hellip;','&dagger;','&Dagger;','&circ;','&permil;','&Scaron;','&lsaquo;','&OElig;','','&#x017D;','','','&lsquo;','&rsquo;','&ldquo;','&rdquo;','&bull;','&ndash;','&mdash;','&tilde;','&trade;','&scaron;','&rsaquo;','&oelig;','','&#x017E;','&Yuml;','&nbsp;','&iexcl;','&iexcl;','&iexcl;','&iexcl;','&yen;','&brvbar;','&sect;','&uml;','&copy;','&ordf;','&laquo;','&not;','�&shy;','&reg;','&macr;','&deg;','&plusmn;','&sup2;','&sup3;','&acute;','&micro;','&para;','&middot;','&cedil;','&supl;','&ordm;','&raquo;','&frac14;','&frac12;','&frac34;','&iquest;','&Agrave;','&Aacute;','&Acirc;','&Atilde;','&Auml;','&Aring;','&AElig;','&Ccedil;','&Egrave;','&Eacute;','&Ecirc;','&Euml;','&Igrave;','&Iacute;','&Icirc;','&Iuml;','&ETH;','&Ntilde;','&Ograve;','&Oacute;','&Ocirc;','&Otilde;','&Ouml;','&times;','&Oslash;','&Ugrave;','&Uacute;','&Ucirc;','&Uuml;','&Yacute;','&THORN;','&szlig;','&agrave;','&aacute;','&acirc;','&atilde;','&auml;','&aring;','&aelig;','&ccedil;','&egrave;','&eacute;','&ecirc;','&euml;','&igrave;','&iacute;','&icirc;','&iuml;','&eth;','&ntilde;','&ograve;','&oacute;','&ocirc;','&otilde;','&ouml;','&divide;','&oslash;','&ugrave;','&uacute;','&ucirc;','&uuml;','&yacute;','&thorn;','&yuml;');
  //$s_String = str_replace($umlaute, $htmlcode, $s_String);
  if ( version_compare(phpversion(), '5.0.0', '>=') )
    $s_String = utf8_encode( html_entity_decode( str_replace($umlaute, $htmlcode, $s_String) ) );
  else
    $s_String = utf8_encode( RSSMedia_html_entity_decode_php4( str_replace($umlaute, $htmlcode, $s_String) ) );

  // &hellip; , &#8230;
  $s_String = preg_replace('~\xC3\xA2\xE2\x82\xAC\xC2\xA6~', '&hellip;', $s_String);
  $s_String = preg_replace('~\xC3\x83\xC2\xA2\xC3\xA2\xE2\x80\x9A\xC2\xAC\xC3\x82\xC2\xA6~', '&hellip;', $s_String);
  $s_String = preg_replace('~\xD0\xB2\xD0\x82\xC2\xA6~', '&hellip;', $s_String);

  // &mdash; , &#8212;
  $s_String = preg_replace('~\xC3\xA2\xE2\x82\xAC\xE2\x80\x9D~', '&mdash;', $s_String);
  $s_String = preg_replace('~\xC3\x83\xC2\xA2\xC3\xA2\xE2\x80\x9A\xC2\xAC\xC3\xA2\xE2\x82\xAC\xC2\x9D~', '&mdash;', $s_String);
  $s_String = preg_replace('~\xD0\xB2\xD0\x82\xE2\x80\x9D~', '&mdash;', $s_String);

  // &ndash; , &#8211;
  $s_String = preg_replace('~\xC3\xA2\xE2\x82\xAC\xE2\x80\x9C~', '&ndash;', $s_String);
  $s_String = preg_replace('~\xC3\x83\xC2\xA2\xC3\xA2\xE2\x80\x9A\xC2\xAC\xC3\xA2\xE2\x82\xAC\xC5\x93~', '&ndash;', $s_String);
  $s_String = preg_replace('~\xD0\xB2\xD0\x82\xE2\x80\x9C~', '&ndash;', $s_String);

  // &rsquo; , &#8217;
  $s_String = preg_replace('~\xC3\xA2\xE2\x82\xAC\xE2\x84\xA2~', '&rsquo;', $s_String);
  $s_String = preg_replace('~\xC3\x83\xC2\xA2\xC3\xA2\xE2\x80\x9A\xC2\xAC\xC3\xA2\xE2\x80\x9E\xC2\xA2~', '&rsquo;', $s_String);
  $s_String = preg_replace('~\xD0\xB2\xD0\x82\xE2\x84\xA2~', '&rsquo;', $s_String);
  $s_String = preg_replace('~\xD0\xBF\xD1\x97\xD0\x85~', '&rsquo;', $s_String);

  // &lsquo; , &#8216;
  $s_String = preg_replace('~\xC3\xA2\xE2\x82\xAC\xCB\x9C~', '&lsquo;', $s_String);
  $s_String = preg_replace('~\xC3\x83\xC2\xA2\xC3\xA2\xE2\x80\x9A\xC2\xAC\xC3\x8B\xC5\x93~', '&lsquo;', $s_String);

  // &rdquo; , &#8221;
  $s_String = preg_replace('~\xC3\xA2\xE2\x82\xAC\xC2\x9D~', '&rdquo;', $s_String);
  $s_String = preg_replace('~\xC3\x83\xC2\xA2\xC3\xA2\xE2\x80\x9A\xC2\xAC\xC3\x82\xC2\x9D~', '&rdquo;', $s_String);
  $s_String = preg_replace('~\xD0\xB2\xD0\x82\xD1\x9C~', '&rdquo;', $s_String);

  // &ldquo; , &#8220;
  $s_String = preg_replace('~\xC3\xA2\xE2\x82\xAC\xC5\x93~', '&ldquo;', $s_String);
  $s_String = preg_replace('~\xC3\x83\xC2\xA2\xC3\xA2\xE2\x80\x9A\xC2\xAC\xC3\x85\xE2\x80\x9C~', '&ldquo;', $s_String);
  $s_String = preg_replace('~\xD0\xB2\xD0\x82\xD1\x9A~', '&ldquo;', $s_String);

  // &trade; , &#8482;
  $s_String = preg_replace('~\xC3\xA2\xE2\x80\x9E\xC2\xA2~', '&trade;', $s_String);
  $s_String = preg_replace('~\xC3\x83\xC2\xA2\xC3\xA2\xE2\x82\xAC\xC5\xBE\xC3\x82\xC2\xA2~', '&trade;', $s_String);

  // th
  $s_String = preg_replace('~t\xC3\x82\xC2\xADh~', 'th', $s_String);

  // .
  $s_String = preg_replace('~.\xD0\x92+~', '.', $s_String);
  $s_String = preg_replace('~.\xD0\x92~', '.', $s_String);

  // ,
  $s_String = preg_replace('~\x2C\xD0\x92~', ',', $s_String);

  return $s_String;
}

/**
 * Entfernt unvollstaendige Worte am Ende eines Strings.
 * @author Thomas Scholz <http://toscho.de>
 * @param $str Zeichenkette
 * @return string
 */
function RSSMedia_end_on_word($str) {

  $arr = explode( ' ', trim($str) );
  array_pop($arr);

  return rtrim( implode(' ', $arr), ',;');
}

function _rssmedia_shortcode ($atts) {

  $defaults = array(
    'template' => 0,
    'limit' => RSSMEDIA_LIMIT,
    'url' => ''
  );

  extract(shortcode_atts($defaults, $atts));

  $limit = intval($limit);
  $url = html_entity_decode($url);

  return RSSMedia($template, $limit, $url);
}

add_shortcode('rssmedia', '_rssmedia_shortcode');

function RSSMedia_shortcode_quot($pee) {
  global $shortcode_tags;

  if ( !empty($shortcode_tags) && is_array($shortcode_tags) ) {
    $tagnames = array_keys($shortcode_tags);
    $tagregexp = join( '|', array_map('preg_quote', $tagnames) );
    $pee = preg_replace('/\\s*?(\\[(' . $tagregexp . ')\\b.*?\\/?\\](?:.+?\\[\\/\\2\\])?)\\s*/s', '$1', $pee);
  }

  return $pee;
}

add_action( 'init', '_rssmedia_init' );


/**
 * code to utf-8 in PHP 4
 *
 * @package RSSMedia
 */
function RSSMedia_code_to_utf8($num) {

  if ($num <= 0x7F) {
    return chr($num);
  } elseif ($num <= 0x7FF) {
    return chr(($num >> 0x06) + 0xC0) . chr(($num & 0x3F) + 128);
  } elseif ($num <= 0xFFFF) {
    return chr(($num >> 0x0C) + 0xE0) . chr((($num >> 0x06) & 0x3F) + 0x80) . chr(($num & 0x3F) + 0x80);
  } elseif ($num <= 0x1FFFFF) {
    return chr(($num >> 0x12) + 0xF0) . chr((($num >> 0x0C) & 0x3F) + 0x80) . chr((($num >> 0x06) & 0x3F) + 0x80) . chr(($num & 0x3F) + 0x80);
  }

  return '';
}


/**
 * html_entity_decode for PHP 4
 *
 * @package RSSMedia
 */
function RSSMedia_html_entity_decode_php4($str) {
  $htmlentities = array (
    "&Aacute;" => chr(195).chr(129),
    "&aacute;" => chr(195).chr(161),
    "&Acirc;" => chr(195).chr(130),
    "&acirc;" => chr(195).chr(162),
    "&acute;" => chr(194).chr(180),
    "&AElig;" => chr(195).chr(134),
    "&aelig;" => chr(195).chr(166),
    "&Agrave;" => chr(195).chr(128),
    "&agrave;" => chr(195).chr(160),
    "&alefsym;" => chr(226).chr(132).chr(181),
    "&Alpha;" => chr(206).chr(145),
    "&alpha;" => chr(206).chr(177),
    "&amp;" => chr(38),
    "&and;" => chr(226).chr(136).chr(167),
    "&ang;" => chr(226).chr(136).chr(160),
    "&Aring;" => chr(195).chr(133),
    "&aring;" => chr(195).chr(165),
    "&asymp;" => chr(226).chr(137).chr(136),
    "&Atilde;" => chr(195).chr(131),
    "&atilde;" => chr(195).chr(163),
    "&Auml;" => chr(195).chr(132),
    "&auml;" => chr(195).chr(164),
    "&bdquo;" => chr(226).chr(128).chr(158),
    "&Beta;" => chr(206).chr(146),
    "&beta;" => chr(206).chr(178),
    "&brvbar;" => chr(194).chr(166),
    "&bull;" => chr(226).chr(128).chr(162),
    "&cap;" => chr(226).chr(136).chr(169),
    "&Ccedil;" => chr(195).chr(135),
    "&ccedil;" => chr(195).chr(167),
    "&cedil;" => chr(194).chr(184),
    "&cent;" => chr(194).chr(162),
    "&Chi;" => chr(206).chr(167),
    "&chi;" => chr(207).chr(135),
    "&circ;" => chr(203).chr(134),
    "&clubs;" => chr(226).chr(153).chr(163),
    "&cong;" => chr(226).chr(137).chr(133),
    "&copy;" => chr(194).chr(169),
    "&crarr;" => chr(226).chr(134).chr(181),
    "&cup;" => chr(226).chr(136).chr(170),
    "&curren;" => chr(194).chr(164),
    "&dagger;" => chr(226).chr(128).chr(160),
    "&Dagger;" => chr(226).chr(128).chr(161),
    "&darr;" => chr(226).chr(134).chr(147),
    "&dArr;" => chr(226).chr(135).chr(147),
    "&deg;" => chr(194).chr(176),
    "&Delta;" => chr(206).chr(148),
    "&delta;" => chr(206).chr(180),
    "&diams;" => chr(226).chr(153).chr(166),
    "&divide;" => chr(195).chr(183),
    "&Eacute;" => chr(195).chr(137),
    "&eacute;" => chr(195).chr(169),
    "&Ecirc;" => chr(195).chr(138),
    "&ecirc;" => chr(195).chr(170),
    "&Egrave;" => chr(195).chr(136),
    "&egrave;" => chr(195).chr(168),
    "&empty;" => chr(226).chr(136).chr(133),
    "&emsp;" => chr(226).chr(128).chr(131),
    "&ensp;" => chr(226).chr(128).chr(130),
    "&Epsilon;" => chr(206).chr(149),
    "&epsilon;" => chr(206).chr(181),
    "&equiv;" => chr(226).chr(137).chr(161),
    "&Eta;" => chr(206).chr(151),
    "&eta;" => chr(206).chr(183),
    "&ETH;" => chr(195).chr(144),
    "&eth;" => chr(195).chr(176),
    "&Euml;" => chr(195).chr(139),
    "&euml;" => chr(195).chr(171),
    "&euro;" => chr(226).chr(130).chr(172),
    "&exist;" => chr(226).chr(136).chr(131),
    "&fnof;" => chr(198).chr(146),
    "&forall;" => chr(226).chr(136).chr(128),
    "&frac12;" => chr(194).chr(189),
    "&frac14;" => chr(194).chr(188),
    "&frac34;" => chr(194).chr(190),
    "&frasl;" => chr(226).chr(129).chr(132),
    "&Gamma;" => chr(206).chr(147),
    "&gamma;" => chr(206).chr(179),
    "&ge;" => chr(226).chr(137).chr(165),
    "&harr;" => chr(226).chr(134).chr(148),
    "&hArr;" => chr(226).chr(135).chr(148),
    "&hearts;" => chr(226).chr(153).chr(165),
    "&hellip;" => chr(226).chr(128).chr(166),
    "&Iacute;" => chr(195).chr(141),
    "&iacute;" => chr(195).chr(173),
    "&Icirc;" => chr(195).chr(142),
    "&icirc;" => chr(195).chr(174),
    "&iexcl;" => chr(194).chr(161),
    "&Igrave;" => chr(195).chr(140),
    "&igrave;" => chr(195).chr(172),
    "&image;" => chr(226).chr(132).chr(145),
    "&infin;" => chr(226).chr(136).chr(158),
    "&int;" => chr(226).chr(136).chr(171),
    "&Iota;" => chr(206).chr(153),
    "&iota;" => chr(206).chr(185),
    "&iquest;" => chr(194).chr(191),
    "&isin;" => chr(226).chr(136).chr(136),
    "&Iuml;" => chr(195).chr(143),
    "&iuml;" => chr(195).chr(175),
    "&Kappa;" => chr(206).chr(154),
    "&kappa;" => chr(206).chr(186),
    "&Lambda;" => chr(206).chr(155),
    "&lambda;" => chr(206).chr(187),
    "&lang;" => chr(226).chr(140).chr(169),
    "&laquo;" => chr(194).chr(171),
    "&larr;" => chr(226).chr(134).chr(144),
    "&lArr;" => chr(226).chr(135).chr(144),
    "&lceil;" => chr(226).chr(140).chr(136),
    "&ldquo;" => chr(226).chr(128).chr(156),
    "&le;" => chr(226).chr(137).chr(164),
    "&lfloor;" => chr(226).chr(140).chr(138),
    "&lowast;" => chr(226).chr(136).chr(151),
    "&loz;" => chr(226).chr(151).chr(138),
    "&lrm;" => chr(226).chr(128).chr(142),
    "&lsaquo;" => chr(226).chr(128).chr(185),
    "&lsquo;" => chr(226).chr(128).chr(152),
    "&macr;" => chr(194).chr(175),
    "&mdash;" => chr(226).chr(128).chr(148),
    "&micro;" => chr(194).chr(181),
    "&middot;" => chr(194).chr(183),
    "&minus;" => chr(226).chr(136).chr(146),
    "&Mu;" => chr(206).chr(156),
    "&mu;" => chr(206).chr(188),
    "&nabla;" => chr(226).chr(136).chr(135),
    "&nbsp;" => chr(194).chr(160),
    "&ndash;" => chr(226).chr(128).chr(147),
    "&ne;" => chr(226).chr(137).chr(160),
    "&ni;" => chr(226).chr(136).chr(139),
    "&not;" => chr(194).chr(172),
    "&notin;" => chr(226).chr(136).chr(137),
    "&nsub;" => chr(226).chr(138).chr(132),
    "&Ntilde;" => chr(195).chr(145),
    "&ntilde;" => chr(195).chr(177),
    "&Nu;" => chr(206).chr(157),
    "&nu;" => chr(206).chr(189),
    "&Oacute;" => chr(195).chr(147),
    "&oacute;" => chr(195).chr(179),
    "&Ocirc;" => chr(195).chr(148),
    "&ocirc;" => chr(195).chr(180),
    "&OElig;" => chr(197).chr(146),
    "&oelig;" => chr(197).chr(147),
    "&Ograve;" => chr(195).chr(146),
    "&ograve;" => chr(195).chr(178),
    "&oline;" => chr(226).chr(128).chr(190),
    "&Omega;" => chr(206).chr(169),
    "&omega;" => chr(207).chr(137),
    "&Omicron;" => chr(206).chr(159),
    "&omicron;" => chr(206).chr(191),
    "&oplus;" => chr(226).chr(138).chr(149),
    "&or;" => chr(226).chr(136).chr(168),
    "&ordf;" => chr(194).chr(170),
    "&ordm;" => chr(194).chr(186),
    "&Oslash;" => chr(195).chr(152),
    "&oslash;" => chr(195).chr(184),
    "&Otilde;" => chr(195).chr(149),
    "&otilde;" => chr(195).chr(181),
    "&otimes;" => chr(226).chr(138).chr(151),
    "&Ouml;" => chr(195).chr(150),
    "&ouml;" => chr(195).chr(182),
    "&para;" => chr(194).chr(182),
    "&part;" => chr(226).chr(136).chr(130),
    "&permil;" => chr(226).chr(128).chr(176),
    "&perp;" => chr(226).chr(138).chr(165),
    "&Phi;" => chr(206).chr(166),
    "&phi;" => chr(207).chr(134),
    "&Pi;" => chr(206).chr(160),
    "&pi;" => chr(207).chr(128),
    "&piv;" => chr(207).chr(150),
    "&plusmn;" => chr(194).chr(177),
    "&pound;" => chr(194).chr(163),
    "&prime;" => chr(226).chr(128).chr(178),
    "&Prime;" => chr(226).chr(128).chr(179),
    "&prod;" => chr(226).chr(136).chr(143),
    "&prop;" => chr(226).chr(136).chr(157),
    "&Psi;" => chr(206).chr(168),
    "&psi;" => chr(207).chr(136),
    "&radic;" => chr(226).chr(136).chr(154),
    "&rang;" => chr(226).chr(140).chr(170),
    "&raquo;" => chr(194).chr(187),
    "&rarr;" => chr(226).chr(134).chr(146),
    "&rArr;" => chr(226).chr(135).chr(146),
    "&rceil;" => chr(226).chr(140).chr(137),
    "&rdquo;" => chr(226).chr(128).chr(157),
    "&real;" => chr(226).chr(132).chr(156),
    "&reg;" => chr(194).chr(174),
    "&rfloor;" => chr(226).chr(140).chr(139),
    "&Rho;" => chr(206).chr(161),
    "&rho;" => chr(207).chr(129),
    "&rlm;" => chr(226).chr(128).chr(143),
    "&rsaquo;" => chr(226).chr(128).chr(186),
    "&rsquo;" => chr(226).chr(128).chr(153),
    "&sbquo;" => chr(226).chr(128).chr(154),
    "&Scaron;" => chr(197).chr(160),
    "&scaron;" => chr(197).chr(161),
    "&sdot;" => chr(226).chr(139).chr(133),
    "&sect;" => chr(194).chr(167),
    "&shy;" => chr(194).chr(173),
    "&Sigma;" => chr(206).chr(163),
    "&sigma;" => chr(207).chr(131),
    "&sigmaf;" => chr(207).chr(130),
    "&sim;" => chr(226).chr(136).chr(188),
    "&spades;" => chr(226).chr(153).chr(160),
    "&sub;" => chr(226).chr(138).chr(130),
    "&sube;" => chr(226).chr(138).chr(134),
    "&sum;" => chr(226).chr(136).chr(145),
    "&sup1;" => chr(194).chr(185),
    "&sup2;" => chr(194).chr(178),
    "&sup3;" => chr(194).chr(179),
    "&sup;" => chr(226).chr(138).chr(131),
    "&supe;" => chr(226).chr(138).chr(135),
    "&szlig;" => chr(195).chr(159),
    "&Tau;" => chr(206).chr(164),
    "&tau;" => chr(207).chr(132),
    "&there4;" => chr(226).chr(136).chr(180),
    "&Theta;" => chr(206).chr(152),
    "&theta;" => chr(206).chr(184),
    "&thetasym;" => chr(207).chr(145),
    "&thinsp;" => chr(226).chr(128).chr(137),
    "&THORN;" => chr(195).chr(158),
    "&thorn;" => chr(195).chr(190),
    "&tilde;" => chr(203).chr(156),
    "&times;" => chr(195).chr(151),
    "&trade;" => chr(226).chr(132).chr(162),
    "&Uacute;" => chr(195).chr(154),
    "&uacute;" => chr(195).chr(186),
    "&uarr;" => chr(226).chr(134).chr(145),
    "&uArr;" => chr(226).chr(135).chr(145),
    "&Ucirc;" => chr(195).chr(155),
    "&ucirc;" => chr(195).chr(187),
    "&Ugrave;" => chr(195).chr(153),
    "&ugrave;" => chr(195).chr(185),
    "&uml;" => chr(194).chr(168),
    "&upsih;" => chr(207).chr(146),
    "&Upsilon;" => chr(206).chr(165),
    "&upsilon;" => chr(207).chr(133),
    "&Uuml;" => chr(195).chr(156),
    "&uuml;" => chr(195).chr(188),
    "&weierp;" => chr(226).chr(132).chr(152),
    "&Xi;" => chr(206).chr(158),
    "&xi;" => chr(206).chr(190),
    "&Yacute;" => chr(195).chr(157),
    "&yacute;" => chr(195).chr(189),
    "&yen;" => chr(194).chr(165),
    "&yuml;" => chr(195).chr(191),
    "&Yuml;" => chr(197).chr(184),
    "&Zeta;" => chr(206).chr(150),
    "&zeta;" => chr(206).chr(182),
    "&zwj;" => chr(226).chr(128).chr(141),
    "&zwnj;" => chr(226).chr(128).chr(140),
    "&gt;" => ">",
    "&lt;" => "<"
  );

  $return = strtr($str, $htmlentities);
  $return = preg_replace('~&#x([0-9a-f]+);~ei', 'RSSMedia_code_to_utf8(hexdec("\\1"))', $return);
  $return = preg_replace('~&#([0-9]+);~e', 'RSSMedia_code_to_utf8(\\1)', $return);

  return $return;
}


// check class wp_widget exists
if ( class_exists('WP_Widget') ) {

  class RSSMedia_Widget extends WP_Widget {

    function RSSMedia_Widget() {
      $widget_ops = array('classname' => 'rssmedia', 'description' => __('Display media from feeds') );
      $this->WP_Widget('rssmedia', __( 'RSS Media' ), $widget_ops);
    }

    function widget($args, $instance) {
      extract($args, EXTR_SKIP);

      $template_id = empty($instance['template_id'])
                       ? '0'
                         : $instance['template_id'];

      $title = empty($instance['title'])
                 ? '&nbsp;'
                   : apply_filters('widget_title', $instance['title']);

      $display = empty($instance['display'])
                   ? RSSMEDIA_LIMIT
                     : $instance['display'];

      $feedurl = empty($instance['feedurl'])
                   ? RSSMEDIA_URL
                     : $instance['feedurl'];

      echo $before_widget,
             $before_title,
               $title,
             $after_title,
             RSSMedia($template_id, $display, $feedurl),
           $after_widget;
    }

    function update($new_instance, $old_instance) {
      $instance['instance'] = $old_instance;
      $instance['template_id'] = $new_instance['template_id'];
      $instance['title'] = strip_tags( $new_instance['title'] );
      $instance['display'] = (int) $new_instance['display'];
      $instance['feedurl'] = $new_instance['feedurl'];

      if ( current_user_can('unfiltered_html') )
        return $instance;
      else
        return stripslashes( strip_tags ( $instance ) );
    }

    function form($instance) {
      $instance = wp_parse_args(
          (array) $instance, array(
                           'template_id' => '0',
                           'title' => '',
                           'display' => RSSMEDIA_LIMIT,
                           'feedurl' => RSSMEDIA_URL
                      )
      );

      $template_id = $instance['template_id'];
      $title   = strip_tags($instance['title']);
      $display = (int) $instance['display'];
      $feedurl = $instance['feedurl'];

      global $_rssmedia_templates;

      ?>
        <p>
          <label for="<?php echo $this->get_field_id('template_id'); ?>">Template name
            <select id="<?php echo $this->get_field_id('template_id'); ?>" name="<?php echo $this->get_field_name('template_id'); ?>">
              <?php foreach ($_rssmedia_templates as $id => $settings): ?>
              <option value="<?php echo $id; ?>" <?php if ($template_id == $id) { echo 'selected="selected"'; } ?>><?php echo $settings['name'] ?></option>
              <?php endforeach; ?>
            </select>
          </label>
        </p>

        <p>
          <label for="<?php echo $this->get_field_id('title'); ?>">Title <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" /></label>
        </p>

        <p>
          <label for="<?php echo $this->get_field_id('display'); ?>">Limit <input class="widefat" id="<?php echo $this->get_field_id('display'); ?>" name="<?php echo $this->get_field_name('display'); ?>" type="text" value="<?php echo esc_attr($display); ?>" /></label>
        </p>
        <p>
          <label for="<?php echo $this->get_field_id('feedurl'); ?>">URL <input class="widefat" id="<?php echo $this->get_field_id('feedurl'); ?>" name="<?php echo $this->get_field_name('feedurl'); ?>" type="text" value="<?php echo $feedurl; ?>" /></label>
        </p>
      <?php

    }
  }

  add_action( 'widgets_init', create_function('', 'return register_widget("RSSMedia_Widget");') );

} // end if class wp_widget exists

require_once 'postwidget.php';
require_once 'notify.php';

?>
