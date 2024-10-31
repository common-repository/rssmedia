<?php

add_action('add_meta_boxes', '_rssmedia_postwidget_init');

function _rssmedia_postwidget_init () {
  foreach (array('post', 'page') as $screen)
    add_meta_box(
      'rssmedia_postwidget',
      'RSS Media',
      '_rssmedia_postwidget_content',
      $screen ,
      'side',
      'core'
    );
}

function _rssmedia_postwidget_content () {
  global $_rssmedia_templates;
?>
<p>
  <label for="rssmedia-template">Template
    <select id="rssmedia-template" name="rssmedia-template-id">
    <?php foreach ($_rssmedia_templates as $id => $settings): ?>
      <option value="<?php echo $id; ?>"><?php echo $settings['name'] ?></option>
    <?php endforeach; ?>
    </select>
  </label>
</p>

<p>
  <label for="rssmedia-limit">Limit
    <input class="widefat" id="rssmedia-limit" name="rssmedia-limit" type="text" value="<?php echo RSSMEDIA_LIMIT; ?>" />
  </label>
</p>

<p>
  <label for="rssmedia-url">URL
    <input class="widefat" id="rssmedia-url" name="rssmedia-url" type="text" value="<?php echo RSSMEDIA_URL; ?>" />
  </label>
</p>

<p><input id="rssmedia-insert" type="button" value="Insert" class="button rssmedia-insert"></p>

<script type="text/javascript">
jQuery(function ($) {

$('#rssmedia-insert').click(function () {
  var tag = '[rssmedia ' +
              'template="' + $('#rssmedia-template').val() + '" ' +
              'limit="' + $('#rssmedia-limit').val() + '" ' +
              'url="' + $('#rssmedia-url').val() + '"' +
            ']';

  var editor = tinyMCE.get('content');

  if (editor && !editor.isHidden())
    editor.execCommand('mceInsertContent', 0, tag);
  else
    QTags.insertContent(tag);
});

});
</script> 
<?php
}
