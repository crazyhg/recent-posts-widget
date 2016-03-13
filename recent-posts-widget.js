jQuery(document).ready(function($) {
    $('.rpw-list').each(function() {
        var widgetData = getWidgetData(this);
        initWidget($(this), widgetData);
    });

    function getWidgetData(widget) {
        var widgetNumber = widget.dataset.widgetNumber;
        return window['rpwData' + widgetNumber];
    }

    function initWidget($widget, widgetData) {
        var requestData = {
            'action': 'load_recent_posts',
            'widgetNumber': $widget[0].dataset.widgetNumber
        }
        $.post(widgetData.ajaxUrl, requestData, function(response) {
            $widget.html(response);
            ellipsis($widget);
            if (widgetData.carouselEnabled) {
                enableCarousel($widget, widgetData);
                setupCarouselToggler($widget, widgetData)
            }
        });
    }

    function ellipsis($container) {
        $container.find(".rpw-post-title").dotdotdot({
            watch: "window"
        });
    }

    function enableCarousel($container, widgetData) {
        var $controls = $container.siblings('.rpw-controls');
        var $pageIndicator = $controls.children('.rpw-index').children('.rpw-index-content');
        $container.on('init reInit beforeChange', function(event, slick, currentSlide, nextSlide) {
            var i = (nextSlide ? nextSlide : 0) + 1;
            $pageIndicator.text(i + ' / ' + slick.slideCount);
        });
        $container.slick({
            autoplay: widgetData.carouselTimeout > 0,
            autoplaySpeed: widgetData.carouselTimeout * 1000,
            fade: true,
            arrows: true,
            appendArrows: $controls,
            prevArrow: widgetData.carouselPrevArrow,
            nextArrow: widgetData.carouselNextArrow,
            adaptiveHeight: true,
        });
    }

    function disableCarousel($container) {
        $container.slick('unslick');
    }

    function setupCarouselToggler($container, widgetData) {
        $toggler = $container.siblings('.rpw-controls').children('.rpw-index');
        $toggler.click(function() {
            $this = $(this);
            $indicatorContent = $this.children('.rpw-index-content');
            if ($container.hasClass('slick-slider')) {
                disableCarousel($container);
                $indicatorContent.empty();
                $indicatorContent.addClass('dashicons dashicons-arrow-up-alt2');
            } else {
                $indicatorContent.removeClass('dashicons dashicons-arrow-up-alt2');
                enableCarousel($container, widgetData);
            }
        });
    }

            
});
