jQuery(document).ready(function($) {

  $('#widgets-right').on('click', '.upw-tab-item', function(event) {
    event.preventDefault();
    var widget = $(this).parents('.widget');
    console.log(widget);
    widget.find('.upw-tab-item').removeClass('active');
    $(this).addClass('active');
    widget.find('.upw-tab').addClass('upw-hide');
    widget.find('.' + $(this).data('toggle')).removeClass('upw-hide');
  });

});