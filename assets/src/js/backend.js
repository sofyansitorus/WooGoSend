/**
 * Backend Scripts
 */

var woogosendBackend = {
  renderForm: function () {
    if (!$('#woocommerce_woogosend_origin_type') || !$('#woocommerce_woogosend_origin_type').length) {
      return;
    }

    // Submit form
    $(document).off('click', '#woogosend-btn--save-settings', woogosendBackend.submitForm);
    $(document).on('click', '#woogosend-btn--save-settings', woogosendBackend.submitForm);

    // Toggle Store Origin Fields
    $(document).off('change', '#woocommerce_woogosend_origin_type', woogosendBackend.toggleStoreOriginFields);
    $(document).on('change', '#woocommerce_woogosend_origin_type', woogosendBackend.toggleStoreOriginFields);

    $('#woocommerce_woogosend_origin_type').trigger('change');

    $('.wc-modal-shipping-method-settings table.form-table').each(function () {
      var $table = $(this);
      var $rows = $table.find('tr');

      if (!$rows.length) {
        $table.remove();
      }
    });

    $('.woogosend-field-group').each(function () {
      var $fieldGroup = $(this);

      var fieldGroupId = $fieldGroup
        .attr('id')
        .replace('woocommerce_woogosend_field_group_', '');

      var $fieldGroupDescription = $fieldGroup
        .next('p')
        .detach();

      var $fieldGroupTable = $fieldGroup
        .nextAll('table.form-table')
        .first()
        .attr('id', 'woogosend-table--' + fieldGroupId)
        .addClass('woogosend-table woogosend-table--' + fieldGroupId)
        .detach();

      $fieldGroup
        .wrap('<div id="woogosend-field-group-wrap--' + fieldGroupId + '" class="woogosend-field-group-wrap stuffbox woogosend-field-group-wrap--' + fieldGroupId + '"></div>');

      $fieldGroupDescription
        .appendTo('#woogosend-field-group-wrap--' + fieldGroupId);

      $fieldGroupTable
        .appendTo('#woogosend-field-group-wrap--' + fieldGroupId);

      if ($fieldGroupTable && $fieldGroupTable.length) {
        if ($fieldGroup.hasClass('woogosend-field-group-hidden')) {
          $('#woogosend-field-group-wrap--' + fieldGroupId)
            .addClass('woogosend-hidden');
        }
      } else {
        $('#woogosend-field-group-wrap--' + fieldGroupId).remove();
      }
    });

    var params = _.mapObject(woogosend_backend, function (val, key) {
      switch (key) {
        case 'default_lat':
        case 'default_lng':
        case 'test_destination_lat':
        case 'test_destination_lng':
          return parseFloat(val);

        default:
          return val;
      }
    });

    woogosendTableRates.init(params);
    woogosendMapPicker.init(params);

    woogosendToggleButtons();
  },
  maybeOpenModal: function () {
    // Try show settings modal on settings page.
    if (woogosend_backend.showSettings) {
      setTimeout(function () {
        var isMethodAdded = false;
        var methods = $(document).find('.wc-shipping-zone-method-type');
        for (var i = 0; i < methods.length; i++) {
          var method = methods[i];
          if ($(method).text() === woogosend_backend.methodTitle) {
            $(method).closest('tr').find('.row-actions .wc-shipping-zone-method-settings').trigger('click');
            isMethodAdded = true;
            return;
          }
        }

        // Show Add shipping method modal if the shipping is not added.
        if (!isMethodAdded) {
          $('.wc-shipping-zone-add-method').trigger('click');
          $('select[name="add_method_id"]').val(woogosend_backend.methodId).trigger('change');
        }
      }, 500);
    }
  },
  submitForm: function (e) {
    e.preventDefault();

    if (woogosendMapPicker.editingAPIKey || woogosendMapPicker.editingAPIKeyPicker) {
      window.alert(woogosendError('finish_editing_api'));
    } else {
      if (!woogosendTableRates.hasError()) {
        $('#btn-ok').trigger('click');
      } else {
        window.alert(woogosendError('table_rates_invalid'));
      }
    }
  },
  toggleStoreOriginFields: function (e) {
    e.preventDefault();
    var selected = $(this).val();
    var fields = $(this).data('fields');
    _.each(fields, function (fieldIds, fieldValue) {
      _.each(fieldIds, function (fieldId) {
        if (fieldValue !== selected) {
          $('#' + fieldId).closest('tr').hide();
        } else {
          $('#' + fieldId).closest('tr').show();
        }
      });
    });
  },
  initForm: function () {
    // Init form
    $(document.body).off('wc_backbone_modal_loaded', woogosendBackend.renderForm);
    $(document.body).on('wc_backbone_modal_loaded', woogosendBackend.renderForm);
  },
  init: function () {
    woogosendBackend.initForm();
    woogosendBackend.maybeOpenModal();
  }
};

$(document).ready(woogosendBackend.init);
