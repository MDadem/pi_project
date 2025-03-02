(function ($) {
    "use strict";

    // Spinner
    var spinner = function () {
        setTimeout(function () {
            if ($('#spinner').length > 0) {
                $('#spinner').removeClass('show');
            }
        }, 1);
    };
    spinner();
    
    // Back to top button
    $(window).scroll(function () {
        if ($(this).scrollTop() > 300) {
            $('.back-to-top').fadeIn('slow');
        } else {
            $('.back-to-top').fadeOut('slow');
        }
    });
    $('.back-to-top').click(function () {
        $('html, body').animate({scrollTop: 0}, 1500, 'easeInOutExpo');
        return false;
    });

    // Sidebar Toggler
    $('.sidebar-toggler').click(function () {
        $('.sidebar, .content').toggleClass('open');
        return false;
    });

    // Dropdown Menu
    $('.navbar-nav .nav-link').on('click', function() {
        if($(this).hasClass('dropdown-toggle')) {
            $(this).siblings('.dropdown-menu').toggleClass('show');
            return false;
        }
    });

    // Close other dropdowns when one is opened
    $('.navbar-nav .nav-link.dropdown-toggle').on('click', function() {
        $('.navbar-nav .dropdown-menu').not($(this).siblings('.dropdown-menu')).removeClass('show');
    });

    // Close dropdown when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.navbar-nav').length) {
            $('.navbar-nav .dropdown-menu').removeClass('show');
        }
    });
})(jQuery);
