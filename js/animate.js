(function ($) {
    // Functions

    const elementInViewport = function (element) {
        const _this_top = element.offset().top;
        return (_this_top <= window.scrollY + parseInt(window.innerHeight)) && (_this_top >= window.scrollY);
    };
    const initAnimations = function () {
        const bindAnimation = function () {
            $('[data-animation]').each(function () {
                const _animation = $(this).data('animation');
                let _delay = $(this).data('animation-delay');
                let _duration = $(this).data('animation-duration');
                const _prefix = 'animate__';
                if (_animation !== '' && elementInViewport($(this)) && !$(this).hasClass('animation-done')) {
                    if (_delay !== '' && _delay !== 0 && _delay !== '0' && _delay !== undefined) {
                        _delay = parseInt(_delay);
                    } else {
                        _delay = 0;
                    }

                    if (_duration !== '' && _duration !== 0 && _duration !== '0' && _duration !== undefined) {
                        _duration = parseInt(_duration) + 10;
                    } else {
                        _duration = 1010;
                    }

                    const _this = this;
                    $(_this).css('animation-duration', _duration + 'ms');
                    setTimeout(function () {
                        $(_this).css('visibility', 'visible');
                        $(_this).addClass('animate');
                        $(_this).addClass(_prefix + _animation);
                        $(_this).addClass('animation-done');
                        setTimeout(function () {
                            $(_this).removeClass('animate');
                            $(_this).addClass(_prefix + 'animated');
                            $(_this).removeClass(_prefix + _animation);
                        }, (_duration + _delay));
                    }, _delay);
                }
            });
        };

        $(window).on("scroll", function () {
            bindAnimation();
        });
        bindAnimation();
    };

    const winLoad = function () {
        initAnimations();
    };

    $(window).on('load', winLoad);
})(jQuery);