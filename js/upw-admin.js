jQuery(document).ready(function($) {

  $('.upw-tabs').on('click', '.upw-tab-item:not(.active)', function(event) {
    event.preventDefault();
    $('.upw-tab-item').removeClass('active');
    $(this).addClass('active');
    $('.upw-tab').addClass('upw-hide');
    $('.' + $(this).data('toggle')).removeClass('upw-hide');
  });

});