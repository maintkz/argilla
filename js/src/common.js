(function() { 'use strict';

  // Const
  // -----

  var media_queries = [
    //=require media-queries-config.json
  ][0];

  window.SMALL_MOBILE_WIDTH = media_queries.mobile.small;
  window.MOBILE_WIDTH = media_queries.mobile.portrait;
  window.LANDSCAPE_MOBILE_WIDTH = media_queries.mobile.landscape;
  window.PORTRAIT_TABLET_WIDTH = media_queries.tablet.portrait;
  window.TABLET_WIDTH = media_queries.tablet.landscape;
  window.SMALL_NOTEBOOK_WIDTH = media_queries.notebook.small;
  window.NOTEBOOK_WIDTH = media_queries.notebook.normal;

  window.HEADER_HEIGHT = $('.header').height();

  // selectors
  window.$WINDOW = $(window);
  window.$DOCUMENT = $(document);
  window.$HTML = $(document.documentElement);
  window.$BODY = $(document.body);

  // tosrus default settings
  window.TOSRUS_DEFAULTS = {
    buttons: {
      next: true,
      prev: true
    },

    keys: {
      prev: 37,
      next: 39,
      close: 27
    },

    wrapper: {
      onClick: 'close'
    }
  };


  // Helpers
  // -------

  window.WINDOW_WIDTH = window.innerWidth || $WINDOW.width();
  window.WINDOW_HEIGHT = $WINDOW.height();
  $WINDOW.resize(() => {
    WINDOW_WIDTH = window.innerWidth || $WINDOW.width();
    WINDOW_HEIGHT = $WINDOW.height();
  });

  window.IS_DESKTOP_WIDTH = () => {
    return WINDOW_WIDTH > NOTEBOOK_WIDTH;
  };
  window.IS_NOTEBOOK_WIDTH = () => {
    return ( WINDOW_WIDTH > SMALL_NOTEBOOK_WIDTH && WINDOW_WIDTH <= NOTEBOOK_WIDTH );
  };
  window.IS_SMALL_NOTEBOOK_WIDTH = () => {
    return ( WINDOW_WIDTH > TABLET_WIDTH && WINDOW_WIDTH <= SMALL_NOTEBOOK_WIDTH );
  };
  window.IS_TABLET_WIDTH = () => {
    return ( WINDOW_WIDTH >= PORTRAIT_TABLET_WIDTH && WINDOW_WIDTH <= TABLET_WIDTH );
  };
  window.IS_MOBILE_WIDTH = () => {
    return WINDOW_WIDTH <= MOBILE_WIDTH;
  };
  window.IS_LANDSCAPE_MOBILE_WIDTH = () => {
    return WINDOW_WIDTH <= LANDSCAPE_MOBILE_WIDTH;
  };
  window.IS_SMALL_MOBILE_WIDTH = () => {
    return WINDOW_WIDTH <= SMALL_MOBILE_WIDTH;
  };
  window.IS_TOUCH_DEVICE = 'ontouchstart' in document;


  // Masked input
  // ------------

  // Phone
  $('input[type="tel"]').mask('+7 (999) 999-99-99', {
    autoclear: false
  });

  if (IS_DESKTOP) {
    $('input[type="date"]').attr('type', 'text');

    // Date
    $('.js-date-mask').mask('99/99/9999', {
      placeholder: 'дд.мм.гггг',
      autoclear: false
    });

    // Time
    $('.js-time-mask').mask('99:99', {
      placeholder: 'чч:мм',
      autoclear: false
    });
  }


  // Spinner
  // -------

  $DOCUMENT.on('mousedown.js-spinner', '.js-spinner-down, .js-spinner-up', function() {
    var $spinner_control = $(this),
        $input = $spinner_control.siblings('.inp'),
        value = parseInt( $input.val(), 10 ),
        step = $input.data('step') ? $input.data('step') : 1,
        may_be_zero = $input.data('zero') ? $input.data('zero') : false,
        inc_timeout, inc_interval, dec_timeout, dec_interval;

    $spinner_control
      .on('mouseup.js-spinner', clearAll)
      .on('mouseleave.js-spinner', $spinner_control, clearAll);

    if ( $spinner_control.hasClass('js-spinner-down') ) {
      decVal(); dec_timeout = setTimeout(() => {
        dec_interval = setInterval(decVal, 70);
      }, 300);
    }

    if ( $spinner_control.hasClass('js-spinner-up') ) {
      incVal(); inc_timeout = setTimeout(() => {
        inc_interval = setInterval(incVal, 70);
      }, 300);
    }

    function incVal() {
      if ( $.isMouseLoaderActive() ) return;

      $input.val(value + step).change();
    }

    function decVal() {
      if ( $.isMouseLoaderActive() ) return;

      if ( may_be_zero ) {
        if ( value >= step ) {
          $input.val(value - step).change();
        }
      } else {
        if ( value > step ) {
          $input.val(value - step).change();
        }
      }
    }

    function clearAll() {
      clearTimeout(dec_timeout); clearInterval(dec_interval);
      clearTimeout(inc_timeout); clearInterval(inc_interval);
    }
  });

  $DOCUMENT.on('keydown', '.js-spinner .inp', function(e) {
    var $input = $(this);

    if ( e.keyCode == 46 || e.keyCode == 8 || e.keyCode == 9 || e.keyCode == 27 ||
      (e.keyCode == 65 && e.ctrlKey === true) ||
      (e.keyCode >= 35 && e.keyCode <= 39)) {
      return;
    } else {
      if ( (e.keyCode < 48 || e.keyCode > 57) && (e.keyCode < 96 || e.keyCode > 105 ) ) {
        e.preventDefault();
      }
    }
  });

  $DOCUMENT.on('keyup paste', '.js-spinner .inp', function(e) {
    var $input = $(this),
        may_be_zero = $input.data('zero') ? $input.data('zero') : false;

    if ( !may_be_zero && $input.val() == 0 ) {
      $input.val(1);
    }
  });


  // Overlay loader
  // --------------

  // open popup
  $DOCUMENT.on('click.overlay-open', '.js-overlay', function(e) {
    e.preventDefault();

    var $popup = $(this).attr('href');

    $.overlayLoader(true, {
      node: $popup,
      hideSelector: '.js-popup-close'
    });
  });

  // autofocus
  $DOCUMENT.on('overlayLoaderShow', (e, $node) => {
    $node.find('.js-autofocus-inp').focus();
  });


  // Selectric
  // ---------

  // init selectric
  $DOCUMENT.on('initSelectric yiiListViewUpdated', () => {
    $('select').selectric({
      disableOnMobile: false,
      nativeOnMobile: true
    });
  }).trigger('initSelectric');


  // Checkboxes
  // ----------

  $('.checkbox input').on('change initCheckboxes', function() {
    var $inp = $(this),
        $label = $inp.closest('.checkbox');

    if ( $inp.prop('checked') ) {
      $label.addClass('checked');
    } else {
      $label.removeClass('checked');
    }
  }).trigger('initCheckboxes');


  // Radio buttons
  // ----------

  $('.radio input').on('change initRadio', function() {
    var $inp = $(this),
        $group = $('[name="' + $inp.attr('name') + '"]'),
        $labels = $group.closest('.radio'),
        $selected_item = $labels.find('input').filter(':checked').closest('.radio');

    $labels.removeClass('checked');
    $selected_item.addClass('checked');
  }).trigger('initRadio');


  // Scroll to
  // ---------

  $DOCUMENT.on('click.scroll-to', '.js-scroll-to', function(e) {
    e.preventDefault();

    var $lnk = $(this);
    var $elem_to_scroll = $($lnk.attr('href'));
    var speed = $lnk.data('speed') || 150;
    var offset = $lnk.data('offset') || 0;

    $WINDOW.scrollTo($elem_to_scroll, {duration: speed, offset: offset});
  });


  // Menus
  // -----

  (function() {
    var $menus = $('.js-menu');

    if (IS_DESKTOP) {
      $menus.on('mouseenter.js-menu', 'li', function() {
        var $this = $(this);
        clearTimeout($this.data('hoverTimeout'));
        $this.addClass('is-hovered');
      });

      $menus.on('mouseleave.js-menu', 'li', function() {
        var $this = $(this);
        $this.data('hoverTimeout', setTimeout(function() {
          $this.removeClass('is-hovered');
        }, 200));
      });
    }

    if (IS_MOBILE) {
      $menus.on('click.js-m-menu', 'a', function(e) {
        e.preventDefault();

        var $anchor = $(this);
        var $parent = $anchor.parent();

        var has_dropdown = $parent.hasClass('has-dropdown');
        var is_hovered = $parent.hasClass('is-hovered');

        $parent.siblings().removeClass('is-hovered');

        if (!has_dropdown) {
          location.href = $anchor.attr('href');
        } else {
          if (is_hovered) {
            location.href = $anchor.attr('href');
          } else {
            $parent.addClass('is-hovered');
          }
        }
      });
    }
  }());


  // Tabs
  // ----

  $('.js-tabs .tabs-nav li a').click(function(e) {
    e.preventDefault();

    var $this = $(this);
    var $panel = $( $this.attr('href') );

    $this.closest('li').addClass('active').siblings().removeClass('active');
    $panel.closest('.tabs').find('.tabs-panel').hide();
    $panel.fadeIn();
  });


  // Galleries
  // ---------

  // init tosrus static gallery
  $('.js-gallery').each(function() {
    $(this).find('.js-gallery-item').tosrus(TOSRUS_DEFAULTS);
  });


  // Rotators
  // --------

  $('.js-slideshow').each(function() {
    var $this = $(this);

    var tos = $this.tosrus({
      effect: 'slide',
      slides: {
        visible: 1
      },
      autoplay: {
        play: true,
        timeout: 7500
      },
      infinite: true,
      pagination: {
        add: true
      }
    });
  });


  // Scrolling to top
  // ----------------

  if ( !IS_MOBILE_WIDTH() ) {
    var $go_top_btn = $('<div class="go-top-btn"></div>');
    $go_top_btn.click(() => {
      $WINDOW.scrollTo(0, 200);
    });
    $WINDOW.scroll(() => {
      var scroll_top = $WINDOW.scrollTop();
      if ( scroll_top > 0 ) {
        $go_top_btn.addClass('visible');
      } else {
        $go_top_btn.removeClass('visible');
      }
    });
    $BODY.append( $go_top_btn );
  }

})();
