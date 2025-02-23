jQuery(document).ready(function($) {
    // Toggle button visibility based on scroll position.
    $(window).scroll(function() {
        if ($(this).scrollTop() > 100) {
            $('#backmeta-back-to-top').fadeIn();
        } else {
            $('#backmeta-back-to-top').fadeOut();
        }
    });

    // Smooth scroll to top when the button is clicked.
    $('#backmeta-back-to-top').click(function(e) {
        e.preventDefault();
        $('html, body').animate({ scrollTop: 0 }, 800);
        return false;
    });
});