  </ul>
</div>

<script>

jQuery(document).ready(function ($) {
  $('#rssimport-sidebarlist .rssimport-sidebarlist-image > img').tooltip({
    items: 'img',
    content: function () {
      return $(this)
               .next('.rssimport-sidebarlist-description')
               .html();
    },
    position: { my: 'left top+15', at: 'left bottom', collision: 'flipfit' },
    show: false,
    hide: false
  })
});

</script>
