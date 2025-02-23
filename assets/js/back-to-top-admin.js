jQuery(document).ready(function($) {
    $('.color-field').wpColorPicker();

    function toggleCustomShapeField() {
        if ($('#button_shape_field').val() === 'custom') {
            $('.custom-button-radius').show();
        } else {
            $('.custom-button-radius').hide();
        }
    }
    toggleCustomShapeField();
    $('#button_shape_field').on('change', function() {
        toggleCustomShapeField();
    });
});