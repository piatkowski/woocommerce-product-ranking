function doubleSlider($container, initialValues) {
    this.value = initialValues;
    const inputFields = $container.querySelectorAll('input[type=range]');

    this.input = {
        from: inputFields[0],
        to: inputFields[1]
    };

    var _this = this;

    function getInputValues() {
        const from = parseInt(_this.input.from.value, 10);
        const to = parseInt(_this.input.to.value, 10);
        return [from, to];
    }

    function fillSlider(controlSlider, sliderColor = '#DDD', rangeColor = '#009900') {
        const rangeDistance = _this.input.to.max - _this.input.to.min;
        const fromPosition = _this.input.from.value - _this.input.to.min;
        const toPosition = _this.input.to.value - _this.input.to.min;
        controlSlider.style.background = `linear-gradient(
      to right,
      ${sliderColor} 0%,
      ${sliderColor} ${(fromPosition) / (rangeDistance) * 100}%,
      ${rangeColor} ${((fromPosition) / (rangeDistance)) * 100}%,
      ${rangeColor} ${(toPosition) / (rangeDistance) * 100}%, 
      ${sliderColor} ${(toPosition) / (rangeDistance) * 100}%, 
      ${sliderColor} 100%)`;
    }

    function controlFromSlider() {
        const [from, to] = getInputValues();
        fillSlider(_this.input.to);
        if (from > to) {
            _this.input.from.value = to;
            _this.value.from = to;
        } else {
            _this.value.from.value = from;
            _this.value.from = from;
        }
        $container.dispatchEvent(new CustomEvent(
            'slider:change',
            {
                detail: {
                    name: _this.input.to.dataset.name,
                    from: _this.value.from,
                    to: _this.value.to
                }
            }
        ));
    }

    function controlToSlider() {
        const [from, to] = getInputValues();
        fillSlider(_this.input.to);
        setToggleAccessible(_this.input.to);
        if (from <= to) {
            _this.input.to.value = to;
            _this.value.to = to;
        } else {
            _this.value.to = from;
            _this.input.to.value = from;
        }
        $container.dispatchEvent(new CustomEvent(
            'slider:change',
            {
                detail: {
                    name: _this.input.to.dataset.name,
                    from: _this.value.from,
                    to: _this.value.to
                }
            }
        ));
    }

    function setToggleAccessible(currentTarget) {
        if (Number(currentTarget.value) <= 0) {
            _this.input.to.style.zIndex = 2;
        } else {
            _this.input.to.style.zIndex = 0;
        }
    }

    fillSlider(_this.input.to);
    setToggleAccessible(_this.input.to);
    _this.input.from.oninput = () => controlFromSlider();
    _this.input.to.oninput = () => controlToSlider();
}

jQuery(document).ready(function ($) {
    const $ipFilter = $("#ipFilter");
    const $ipTable = $("#idTable");
    const $ipLoadMoreSection = $('.ip-load-more');
    const $ipLoadMoreButton = $('.ip-load-more button');

    var $page = 1;
    var $max_pages = $ipFilter.find('input[name=max_pages]').val();
    var loadingMore = false;
    var isFilterChanging = false;
    var filterChangeDelay;

    function updateLoadMoreButton() {
        var isVisible = $page < $max_pages;
        $ipLoadMoreSection.toggle(isVisible).removeClass('active');
        loadingMore = false;
    }

    function updateRowNumbers() {
        $('.ip-row-nr').each(function (i) {
            $(this).text(i + 1);
        });
        $('.ip-row-mobile-nr').each(function (i) {
            $(this).text(i + 1);
        });
    }

    updateLoadMoreButton();
    updateRowNumbers();

    $("#ipToggleFilter").on('click', function (e) {
        e.preventDefault();
        $(this).toggleClass('active');
        $("#ipFilter").delay(100).slideToggle(800);
    });

    $ipLoadMoreButton.on('click', function (e) {
        $ipLoadMoreSection.addClass('active');
        if ($page < $max_pages) {
            $page++;
            $ipFilter.find('input[name=page]').val($page - 1);
            $("#ipFilter form").trigger('submit', ['load-more']);
        }
    });

    $("#ipFilter form").on('submit', function (e, mode) {
        e.preventDefault();
        $ipFilter.addClass('loading');
        $ipTable.addClass('loading');

        if (!mode) {
            $ipFilter.find('input[name=page]').val(0);
        }
        var data = $(this).serialize();
        $.post(woocommerce_params.ajax_url, data, function (response) {
            $ipFilter.removeClass('loading');
            $ipTable.removeClass('loading');
            var $tableContent = $("#ipTableContent");
            if (!mode) {
                $tableContent.children('.ajax').remove();
                $page = 1;
                $max_pages = 1;
            }
            if (response && response.success === true) {
                if (!mode) {
                    $([document.documentElement, document.body]).animate({
                        scrollTop: $tableContent.offset().top
                    }, 1000);
                    $max_pages = response.data.max_pages;
                }
                $tableContent.append(response.data.html);
            }
            updateLoadMoreButton();
            updateRowNumbers();
        });
    });

    $("#ipFilter form input, #ipFilter form select").change(function (e) {
        $('#ipFilter .reset').fadeIn('fast');
        filterChangeDelay = setTimeout(function () {
            $("#ipFilter form").trigger('submit');
        }, 2000);
    }).focus(function (e) {
        clearTimeout(filterChangeDelay);
    });

    $('.ip-double-slider').each(function () {
        $(this)[0].doubleSlider = new doubleSlider($(this)[0], {
            from:   Number($(this).find('input').eq(0).attr('min')),
            to:     Number($(this).find('input').eq(0).attr('max'))
        });
        $(this)[0].addEventListener("slider:change", function (e) {
            const id = '#ip-slider-' + e.detail.name + '-';
            $(id + 'from').text(e.detail.from + ' zł');
            $(id + 'to').text(e.detail.to + ' zł');
        });
    });

    $('body').on('change', '.ip-toggle-button input', function (e) {
        var $target = $(this).parent().parent().next().next();
        $target.slideToggle(800);
    });

    $('#ipTableSort a').click(function (e) {
        e.preventDefault();
        var orderBy = $(this).attr('data-order-by');
        var order = $(this).attr('data-order');

        if ($(this).hasClass('active')) {
            var newOrder = order === 'ASC' ? 'DESC' : 'ASC';
            $(this).attr('data-order', newOrder);
        } else {
            $('#ipTableSort a').removeClass('active');
            $(this).addClass('active');
            $(this).attr('data-order', 'DESC');
        }
        order = $(this).attr('data-order');

        $ipFilter.find('input[name=order_by]').val(orderBy);
        $ipFilter.find('input[name=order]').val(order);
        $("#ipFilter form").trigger('submit');
    });

    $('#ipFilter button[type=reset]').click(function (e) {
        $('#ipFilter .reset').fadeOut('fast');
        setTimeout(function () {
            $("#ipFilter form").trigger('submit');
        }, 300);
    });

    $(window).scroll(function (e) {
        if (!loadingMore && $(this).scrollTop() + $(window).height() > $ipLoadMoreSection.offset().top + 200) {
            loadingMore = true;
            $ipLoadMoreButton.trigger('click');
        }
    });

    $(document).on('mouseenter', '.ip-icon-tooltip', function () {
        const tooltip = $(this).next();
        tooltip.css({
            left: $(this).position().left  - tooltip.width() / 2
        });
        tooltip.stop(true, true).fadeIn('fast');
    }).on('mouseleave', '.ip-icon-tooltip', function () {
        $(this).next().stop(true, true).fadeOut('fast');
    });


});