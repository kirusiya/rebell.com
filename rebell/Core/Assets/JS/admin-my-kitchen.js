/**
 * Admin My Kitchen Scripts.
 *
 * @author  Invbit <info@invbit.com>
 * @link    https://www.invbit.com
 */

jQuery(document).ready(function ($) {
  $('.toggler').click(function() {
    const icon = $(this).children('.dashicons')
    icon.toggleClass('dashicons-arrow-up')
    icon.toggleClass('dashicons-arrow-down')

    const target = $(this).data('toggle');
    $(this).toggleClass('hidden')
    $(`#${target}`).toggleClass('hidden')
  })
});