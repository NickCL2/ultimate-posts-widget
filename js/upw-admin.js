jQuery(document).ready(function($) {

  $('.widget').on('click', '.upw-tab-item', function(event) {
    event.preventDefault();
    $(this).parents('.widget-inside').find('.upw-tab-item').removeClass('active');
    $(this).addClass('active');
    $(this).parents('.widget-inside').find('.upw-tab').addClass('upw-hide');
    $(this).parents('.widget-inside').find('.' + $(this).data('toggle')).removeClass('upw-hide');
  });

});