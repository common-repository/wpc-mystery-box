'use strict';

(function($) {
  $(function() {
    wpcmb_active_settings();
    wpcmb_type_init();
    wpcmb_terms_init();
    wpcmb_products_init();
    wpcmb_arrange();
  });

  $(document).on('change', '#product-type', function() {
    wpcmb_active_settings();
  });

  $(document).on('click touch', '.wpcmb_expand_all', function(e) {
    e.preventDefault();

    $('.wpcmb_assortment_inner').addClass('active');
  });

  $(document).on('click touch', '.wpcmb_collapse_all', function(e) {
    e.preventDefault();

    $('.wpcmb_assortment_inner').removeClass('active');
  });

  $(document).on('click touch', '.wpcmb_add_assortment', function(e) {
    e.preventDefault();
    $('.wpcmb_assortments').addClass('wpcmb_assortments_loading');

    var data = {
      action: 'wpcmb_add_assortment', assortment: {}, nonce: wpcmb_vars.nonce,
    };

    $.post(ajaxurl, data, function(response) {
      $('.wpcmb_assortments tbody').append(response);
      wpcmb_type_init();
      wpcmb_terms_init();
      wpcmb_products_init();
      wpcmb_arrange();
      $('.wpcmb_assortments').removeClass('wpcmb_assortments_loading');
    });
  });

  $(document).on('click touch', '.wpcmb_duplicate_assortment', function(e) {
    e.preventDefault();
    $('.wpcmb_assortments').addClass('wpcmb_assortments_loading');

    var $assortment = $(this).closest('.wpcmb_assortment');
    var form_data = $assortment.
        find('input, select, button, textarea').
        serialize() || 0;
    var data = {
      action: 'wpcmb_add_assortment',
      form_data: form_data,
      nonce: wpcmb_vars.nonce,
    };

    $.post(ajaxurl, data, function(response) {
      $(response).insertAfter($assortment);
      wpcmb_type_init();
      wpcmb_terms_init();
      wpcmb_products_init();
      wpcmb_arrange();
      $('.wpcmb_assortments').removeClass('wpcmb_assortments_loading');
    });
  });

  $(document).on('click touch', '.wpcmb_save_assortments', function(e) {
    e.preventDefault();

    var $this = $(this);

    $this.addClass('wpcmb_disabled');
    $('.wpcmb_assortments').addClass('wpcmb_assortments_loading');

    var form_data = $('#wpcmb_settings').
        find('input, select, button, textarea').
        serialize() || 0;
    var data = {
      action: 'wpcmb_save_assortments',
      pid: $('#post_ID').val(),
      form_data: form_data,
      nonce: wpcmb_vars.nonce,
    };

    $.post(ajaxurl, data, function(response) {
      $('.wpcmb_assortments').removeClass('wpcmb_assortments_loading');
      $this.removeClass('wpcmb_disabled');
    });
  });

  $(document).on('click touch', '.wpcmb_export_assortments', function(e) {
    e.preventDefault();

    if (!$('#wpcmb_export_dialog').length) {
      $('body').append('<div id=\'wpcmb_export_dialog\'></div>');
    }

    $('#wpcmb_export_dialog').html('Loading...');

    $('#wpcmb_export_dialog').dialog({
      minWidth: 460,
      title: 'Export',
      modal: true,
      dialogClass: 'wpc-dialog',
      open: function() {
        $('.ui-widget-overlay').bind('click', function() {
          $('#wpcmb_export_dialog').dialog('close');
        });
      },
    });

    var data = {
      action: 'wpcmb_export_assortments',
      pid: $('#post_ID').val(),
      nonce: wpcmb_vars.nonce,
    };

    $.post(ajaxurl, data, function(response) {
      $('#wpcmb_export_dialog').html(response);
    });
  });

  $(document).on('click touch', '.wpcmb_remove_assortment', function(e) {
    e.preventDefault();

    if (confirm('Are you sure?')) {
      $(this).closest('.wpcmb_assortment').remove();
    }
  });

  $(document).on('click touch', '.wpcmb_assortment_heading', function(e) {
    if (($(e.target).closest('.wpcmb_duplicate_assortment').length === 0) &&
        ($(e.target).closest('.wpcmb_remove_assortment').length === 0)) {
      $(this).closest('.wpcmb_assortment_inner').toggleClass('active');
    }
  });

  $(document).on('change, keyup', '.wpcmb_assortment_name_val', function() {
    var _val = $(this).val();

    $(this).
        closest('.wpcmb_assortment_inner').
        find('.wpcmb_assortment_name').
        html(_val.replace(/(<([^>]+)>)/ig, ''));
  });

  $(document).on('change', '.wpcmb_assortment_type', function() {
    wpcmb_type_init_assortment($(this));
    wpcmb_terms_init_assortment($(this));
    wpcmb_products_init_assortment($(this));
  });

  function wpcmb_type_init() {
    $('.wpcmb_assortment_type').each(function() {
      wpcmb_type_init_assortment($(this));
    });
  }

  // search terms
  $(document).on('change', '.wpcmb_terms', function() {
    var $this = $(this);
    var val = $this.val();
    var type = $this.closest('.wpcmb_assortment').
        find('.wpcmb_assortment_type').
        val();

    $this.data(type, val.join());
  });

  function wpcmb_active_settings() {
    if ($('#product-type').val() === 'wpcmb') {
      $('li.general_tab').addClass('show_if_wpcmb');
      $('#general_product_data .pricing').addClass('show_if_wpcmb');
      $('.wpcmb_tab').addClass('active');
      $('#_downloadable').
          closest('label').
          addClass('show_if_wpcmb').
          removeClass('show_if_simple');
      $('#_virtual').
          closest('label').
          addClass('show_if_wpcmb').
          removeClass('show_if_simple');
      $('.show_if_external').hide();
      $('.show_if_simple').show();
      $('.show_if_wpcmb').show();
      $('.product_data_tabs li').removeClass('active');
      $('.panel-wrap .panel').hide();
      $('#wpcmb_settings').show();
    } else {
      $('li.general_tab').removeClass('show_if_wpcmb');
      $('#general_product_data .pricing').removeClass('show_if_wpcmb');
      $('#_downloadable').
          closest('label').
          removeClass('show_if_wpcmb').
          addClass('show_if_simple');
      $('#_virtual').
          closest('label').
          removeClass('show_if_wpcmb').
          addClass('show_if_simple');
      $('.show_if_wpcmb').hide();
      $('.show_if_' + $('#product-type').val()).show();
    }
  }

  function wpcmb_type_init_assortment($this) {
    var $assortment = $this.closest('.wpcmb_assortment');
    var $type = $assortment.find('.wpcmb_assortment_type');
    var type = $type.val();
    var label = $type.find(':selected').text().trim();

    $assortment.find('.wpcmb_hide').hide();
    $assortment.find('.wpcmb_assortment_type_label').text(label);

    if (type !== '') {
      if (type === 'products') {
        $assortment.find('.wpcmb_show_if_products').
            show().
            css('display', 'flex');
      } else {
        $assortment.find('.wpcmb_show_if_terms').show().css('display', 'flex');
      }
    }

    $assortment.find('.wpcmb_show').show();
    $assortment.find('.wpcmb_hide_if_' + type).hide();
  }

  function wpcmb_terms_init() {
    $('.wpcmb_terms').each(function() {
      wpcmb_terms_init_assortment($(this));
    });
  }

  function wpcmb_products_init() {
    $('.wpcmb_products').each(function() {
      wpcmb_products_init_assortment($(this));
    });
  }

  function wpcmb_terms_init_assortment($this) {
    var $assortment = $this.closest('.wpcmb_assortment');
    var $terms = $assortment.find('.wpcmb_terms');
    var type = $assortment.find('.wpcmb_assortment_type').val();

    if (type === 'types') {
      type = 'product_type';
    }

    $terms.selectWoo({
      ajax: {
        url: ajaxurl, dataType: 'json', delay: 250, data: function(params) {
          return {
            term: params.term,
            action: 'wpcmb_search_term',
            taxonomy: type,
            nonce: wpcmb_vars.nonce,
          };
        }, processResults: function(data) {
          var options = [];

          if (data) {
            $.each(data, function(index, text) {
              options.push({id: text[0], text: text[1]});
            });
          }

          return {
            results: options,
          };
        }, cache: true,
      }, minimumInputLength: 1,
    });

    if ((typeof $terms.data(type) === 'string' || $terms.data(type) instanceof
        String) && $terms.data(type) !== '') {
      $terms.val($terms.data(type).split(',')).change();
    } else {
      $terms.val([]).change();
    }
  }

  function wpcmb_products_init_assortment($this) {
    var $assortment = $this.closest('.wpcmb_assortment');
    var $products = $assortment.find('.wpcmb_products');

    $products.selectWoo({
      allowClear: true, ajax: {
        url: ajaxurl, dataType: 'json', delay: 250, data: function(params) {
          return {
            term: params.term,
            action: 'wpcmb_search_product',
            nonce: wpcmb_vars.nonce,
          };
        }, processResults: function(data) {
          var options = [];

          if (data) {
            $.each(data, function(index, text) {
              options.push({id: text[0], text: text[1]});
            });
          }

          return {
            results: options,
          };
        }, cache: true,
      }, minimumInputLength: 1,
    });
  }

  function wpcmb_arrange() {
    $('.wpcmb_assortments tbody').sortable({
      handle: '.wpcmb_move_assortment',
    });
  }
})(jQuery);