</div>

<script>

jQuery(document).ready(function ($) {
  $('#rssimport-featured-slideshow-tabs')
    .tabs('#rssimport-featured-slideshow-slides li', {
      event: 'mouseover',
      rotate: true
    })
    .slideshow({
      clickable: false,
      autoplay: true,
      interval: 3000
    });
});

</script>
