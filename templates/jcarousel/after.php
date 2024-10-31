    </ul>
  </div>

  <div class="clear"></div>

  <div class="rssimport-jcarousel-prev">&laquo; Prev</div>
  <div class="rssimport-jcarousel-next">Next &raquo;</div>

  <div class="clear"></div>
</div>

<script>

jQuery(document).ready(function ($) {
  var $inner = $('.rssimport-jcarousel-inner')
                 .jcarousel();

  $('.rssimport-jcarousel-prev').click(function () {
    $inner.jcarousel('scroll', '-=1');
  });

  $('.rssimport-jcarousel-next').click(function () {
    $inner.jcarousel('scroll', '+=1');
  });
});

</script>
