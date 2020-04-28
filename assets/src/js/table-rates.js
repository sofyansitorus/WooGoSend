/**
 * Table Rates
 */
var woogosendTableRates = {
  params: {},
  errorId: 'woogosend-errors-rate-fields',
  init: function (params) {
    woogosendTableRates.params = params;

    // Show advanced rate form
    $(document).off('click', '.woogosend-link--advanced-rate', woogosendTableRates.showAdvancedRateForm);
    $(document).on('click', '.woogosend-link--advanced-rate', woogosendTableRates.showAdvancedRateForm);

    // Close advanced rate form
    $(document).off('click', '#woogosend-btn--cancel-advanced', woogosendTableRates.closeAdvancedRateForm);
    $(document).on('click', '#woogosend-btn--cancel-advanced', woogosendTableRates.closeAdvancedRateForm);

    // Apply advanced rate
    $(document).off('click', '#woogosend-btn--apply-advanced', woogosendTableRates.applyAdvancedForm);
    $(document).on('click', '#woogosend-btn--apply-advanced', woogosendTableRates.applyAdvancedForm);

    // Add rate row
    $(document).off('click', '#woogosend-btn--add-rate', woogosendTableRates.handleAddRateButton);
    $(document).on('click', '#woogosend-btn--add-rate', woogosendTableRates.handleAddRateButton);

    // Delete rate row
    $(document).off('click', '#woogosend-btn--delete-rate-select', woogosendTableRates.showDeleteRateRowsForm);
    $(document).on('click', '#woogosend-btn--delete-rate-select', woogosendTableRates.showDeleteRateRowsForm);

    // Cancel delete rate row
    $(document).off('click', '#woogosend-btn--delete-rate-cancel', woogosendTableRates.closeDeleteRateRowsForm);
    $(document).on('click', '#woogosend-btn--delete-rate-cancel', woogosendTableRates.closeDeleteRateRowsForm);

    // Confirm delete rate row
    $(document).off('click', '#woogosend-btn--delete-rate-confirm', woogosendTableRates.deleteRateRows);
    $(document).on('click', '#woogosend-btn--delete-rate-confirm', woogosendTableRates.deleteRateRows);

    // Toggle selected rows
    $(document).off('change', '#woogosend-table--table_rates--dummy thead .select-item', woogosendTableRates.toggleRows);
    $(document).on('change', '#woogosend-table--table_rates--dummy thead .select-item', woogosendTableRates.toggleRows);

    // Toggle selected row
    $(document).off('change', '#woogosend-table--table_rates--dummy tbody .select-item', woogosendTableRates.toggleRow);
    $(document).on('change', '#woogosend-table--table_rates--dummy tbody .select-item', woogosendTableRates.toggleRow);

    // Handle change event dummy rate field
    $(document).off('input', '.woogosend-field--context--dummy:not(a)');
    $(document).on('input', '.woogosend-field--context--dummy:not(a)', woogosendDebounce(function (e) {
      woogosendTableRates.handleRateFieldDummy(e);
    }, 500));

    // Toggle selected row
    $(document).off('change', '#woocommerce_woogosend_distance_unit', woogosendTableRates.initForm);
    $(document).on('change', '#woocommerce_woogosend_distance_unit', woogosendTableRates.initForm);

    woogosendTableRates.initForm();

    if (!$('#woogosend-table--table_rates--dummy tbody tr').length) {
      woogosendTableRates.addRateRow();
    }

    woogosendTableRates.sortRateRows();
  },
  initForm: function () {
    var distanceUnitSelected = $('#woocommerce_woogosend_distance_unit').val();
    var $distanceUnitFields = $('#woocommerce_woogosend_distance_unit').data('fields');

    var label = $distanceUnitFields && _.has($distanceUnitFields.label, distanceUnitSelected) ? $distanceUnitFields.label[distanceUnitSelected] : '';

    if (label && label.length) {
      $.each($distanceUnitFields.targets, function (index, target) {
        $(target).data('index', index).text(label);
      });
    }
  },
  handleAddRateButton: function (e) {
    e.preventDefault();
    $(e.currentTarget).prop('disabled', true);

    woogosendTableRates.addRateRow();

    $(e.currentTarget).prop('disabled', false);
  },
  handleRateFieldDummy: function (e) {
    e.preventDefault();

    var $field = $(e.target);
    var $row = $field.closest('tr');
    $row.find('.woogosend-field--context--hidden[data-id=' + $field.data('id') + ']').val(e.target.value);

    if ($field.hasClass('woogosend-field--context--dummy--max_distance')) {
      $row.addClass('editing');

      $field.off('blur', woogosendTableRates.sortRateRows);
      $field.on('blur', woogosendTableRates.sortRateRows);
    }
  },
  showAdvancedRateForm: function (e) {
    e.preventDefault();

    var $row = $(e.currentTarget).closest('tr').addClass('editing');

    $row.find('.woogosend-field--context--hidden').each(function () {
      $('.woogosend-field--context--advanced[data-id=' + $(this).data('id') + ']').val($(this).val());
    });

    woogosendToggleButtons({
      left: {
        id: 'cancel-advanced',
        label: 'Cancel',
        icon: 'undo'
      },
      right: {
        id: 'apply-advanced',
        label: 'Apply Changes',
        icon: 'editor-spellcheck'
      }
    });

    $('.modal-close-link').hide();

    $('#woogosend-field-group-wrap--advanced_rate').fadeIn().siblings('.woogosend-field-group-wrap').hide();

    var $subTitle = $('#woogosend-field-group-wrap--advanced_rate').find('.wc-settings-sub-title').first().addClass('woogosend-hidden');

    $('.wc-backbone-modal-header').find('h1').append('<span>' + $subTitle.text() + '</span>');
  },
  applyAdvancedForm: function (e) {
    e.preventDefault();

    $('.woogosend-field--context--advanced').each(function () {
      var fieldId = $(this).data('id');
      var fieldValue = $(this).val();

      $('#woogosend-table--table_rates--dummy tbody tr.editing .woogosend-field--context--dummy[data-id=' + fieldId + ']:not(a)').val(fieldValue);
      $('#woogosend-table--table_rates--dummy tbody tr.editing .woogosend-field--context--hidden[data-id=' + fieldId + ']:not(a)').val(fieldValue);
    });

    woogosendTableRates.closeAdvancedRateForm(e);
  },
  closeAdvancedRateForm: function (e) {
    e.preventDefault();

    woogosendToggleButtons();

    $('#woogosend-field-group-wrap--advanced_rate').hide().siblings('.woogosend-field-group-wrap').not('.woogosend-hidden').fadeIn();

    $('#woogosend-field-group-wrap--advanced_rate').find('.wc-settings-sub-title').first().removeClass('woogosend-hidden');

    $('.wc-backbone-modal-header').find('h1 span').remove();

    $('.modal-close-link').show();

    $('#woogosend-table--table_rates--dummy tbody tr.selected').each(function () {
      $(this).find('.select-item').trigger('change');
    });

    woogosendTableRates.scrollToTableRate();
    woogosendTableRates.sortRateRows();
  },
  highlightRow: function () {
    var $row = $('#woogosend-table--table_rates--dummy tbody tr.editing').removeClass('editing');

    if ($row.length) {
      $row.addClass('highlighted');

      setTimeout(function () {
        $row.removeClass('highlighted');
      }, 1500);
    }
  },
  addRateRow: function () {
    var $lastRow = $('#woogosend-table--table_rates--dummy tbody tr:last-child');

    $('#woogosend-table--table_rates--dummy tbody').append(wp.template('woogosend-dummy-row'));

    if ($lastRow) {
      $lastRow.find('.woogosend-field--context--hidden:not(a)').each(function () {
        var $field = $(this);
        var fieldId = $field.data('id');
        var fieldValue = fieldId === 'woocommerce_woogosend_max_distance' ? Math.ceil((parseInt($field.val(), 10) * 1.8)) : $field.val();

        $('#woogosend-table--table_rates--dummy tbody tr:last-child .woogosend-field[data-id=' + fieldId + ']').val(fieldValue);
      });
    }

    woogosendTableRates.scrollToTableRate();

    woogosendTableRates.initForm();
  },
  showDeleteRateRowsForm: function (e) {
    e.preventDefault();

    $('#woogosend-table--table_rates--dummy tbody .select-item:not(:checked)').closest('tr').hide();
    $('#woogosend-table--table_rates--dummy').find('.woogosend-col--select-item, .woogosend-col--link_advanced').hide();
    $('#woogosend-table--table_rates--dummy').find('.woogosend-col--select-item, .woogosend-col--link_sort').hide();
    $('#woogosend-field-group-wrap--table_rates').siblings().hide();

    $('#woogosend-field-group-wrap--table_rates').find('p').first().addClass('woogosend-hidden');

    var $subTitle = $('#woogosend-field-group-wrap--table_rates').find('.wc-settings-sub-title').first().addClass('woogosend-hidden');

    $('.wc-backbone-modal-header').find('h1').append('<span>' + $subTitle.text() + '</span>');

    woogosendToggleButtons({
      left: {
        id: 'delete-rate-cancel',
        label: 'Cancel',
        icon: 'undo'
      },
      right: {
        id: 'delete-rate-confirm',
        label: 'Confirm Delete',
        icon: 'trash'
      }
    });
  },
  closeDeleteRateRowsForm: function (e) {
    e.preventDefault();

    $('#woogosend-table--table_rates--dummy tbody tr').show();
    $('#woogosend-table--table_rates--dummy').find('.woogosend-col--select-item, .woogosend-col--link_advanced').show();
    $('#woogosend-table--table_rates--dummy').find('.woogosend-col--select-item, .woogosend-col--link_sort').show();
    $('#woogosend-field-group-wrap--table_rates').siblings().not('.woogosend-hidden').fadeIn();

    $('#woogosend-field-group-wrap--table_rates').find('p').first().removeClass('woogosend-hidden');
    $('#woogosend-field-group-wrap--table_rates').find('.wc-settings-sub-title').first().removeClass('woogosend-hidden');

    $('.wc-backbone-modal-header').find('h1 span').remove();

    $('#woogosend-table--table_rates--dummy tbody tr.selected').each(function () {
      $(this).find('.select-item').trigger('change');
    });

    woogosendTableRates.scrollToTableRate();
  },
  deleteRateRows: function (e) {
    e.preventDefault();

    $('#woogosend-table--table_rates--dummy tbody .select-item:checked').closest('tr').remove();

    if (!$('#woogosend-table--table_rates--dummy tbody tr').length) {
      if ($('#woogosend-table--table_rates--dummy thead .select-item').is(':checked')) {
        $('#woogosend-table--table_rates--dummy thead .select-item').prop('checked', false).trigger('change');
      }

      woogosendTableRates.addRateRow();
    } else {
      woogosendToggleButtons();
    }

    woogosendTableRates.closeDeleteRateRowsForm(e);
  },
  toggleRows: function (e) {
    e.preventDefault();

    var isChecked = $(e.target).is(':checked');

    $('#woogosend-table--table_rates--dummy tbody tr').each(function () {
      woogosendTableRates.toggleRowSelected($(this), isChecked);
    });

    if (isChecked) {
      woogosendToggleButtons({
        left: {
          id: 'delete-rate-select',
          label: 'Delete Selected Rates',
          icon: 'trash'
        }
      });
    } else {
      woogosendToggleButtons();
    }
  },
  toggleRow: function (e) {
    e.preventDefault();

    var $field = $(e.target);
    var $row = $(e.target).closest('tr');

    woogosendTableRates.toggleRowSelected($row, $field.is(':checked'));

    if ($('#woogosend-table--table_rates--dummy tbody .select-item:checked').length) {
      woogosendToggleButtons({
        left: {
          id: 'delete-rate-select',
          label: 'Delete Selected Rates',
          icon: 'trash'
        }
      });
    } else {
      woogosendToggleButtons();
    }

    var isBulkChecked = $('#woogosend-table--table_rates--dummy tbody .select-item').length === $('#woogosend-table--table_rates--dummy tbody .select-item:checked').length;

    $('#woogosend-table--table_rates--dummy thead .select-item').prop('checked', isBulkChecked);
  },
  toggleRowSelected: function ($row, isChecked) {
    $row.find('.woogosend-field--context--dummy').prop('disabled', isChecked);

    if (isChecked) {
      $row.addClass('selected').find('.select-item').prop('checked', isChecked);
    } else {
      $row.removeClass('selected').find('.select-item').prop('checked', isChecked);
    }
  },
  sortRateRows: function () {

    var rows = $('#woogosend-table--table_rates--dummy > tbody > tr').get().sort(function (a, b) {

      var valueADistance = $(a).find('.woogosend-field--context--dummy--max_distance').val();
      var valueBDistance = $(b).find('.woogosend-field--context--dummy--max_distance').val();

      var valueAIndex = $(a).find('.woogosend-field--context--dummy--max_distance').index();
      var valueBIndex = $(b).find('.woogosend-field--context--dummy--max_distance').index();

      if (isNaN(valueADistance) || !valueADistance.length) {
        return 2;
      }

      valueADistance = parseInt(valueADistance, 10);
      valueBDistance = parseInt(valueBDistance, 10);

      if (valueADistance < valueBDistance) {
        return -1;
      }

      if (valueADistance > valueBDistance) {
        return 1;
      }

      if (valueAIndex < valueBIndex) {
        return -1;
      }

      if (valueAIndex > valueBIndex) {
        return 1;
      }

      return 0;
    });

    var maxDistances = {};

    $.each(rows, function (index, row) {
      var maxDistance = $(row).find('.woogosend-field--context--dummy--max_distance').val();

      if (!maxDistances[maxDistance]) {
        maxDistances[maxDistance] = [];
      }

      maxDistances[maxDistance].push($(row));

      $(row).addClass('woogosend-rate-row-index--' + index).appendTo($('#woogosend-table--table_rates--dummy').children('tbody')).fadeIn('slow');
    });

    _.each(maxDistances, function (rows) {
      _.each(rows, function (row) {
        if (rows.length > 1) {
          $(row).addClass('woogosend-sort-enabled').find('a.woogosend-col--link_sort').prop('enable', true);
        } else {
          $(row).removeClass('woogosend-sort-enabled').find('a.woogosend-col--link_sort').prop('enable', false);
        }
      });
    });

    setTimeout(function () {
      woogosendTableRates.highlightRow();

      if (!$('#woogosend-table--table_rates--dummy > tbody').sortable('instance')) {
        $(function () {
          var oldIndex = null;
          var maxDistance = null;

          $('#woogosend-table--table_rates--dummy tbody').sortable({
            items: 'tr.woogosend-sort-enabled:not(.selected)',
            cursor: 'move',
            classes: {
              "ui-sortable": "highlight"
            },
            placeholder: "ui-state-highlight",
            axis: "y",
            start: function (event, ui) {
              if ($(event.target).closest('tr').hasClass('selected')) {
                $(event.target).sortable('cancel');
              } else {
                oldIndex = ui.item.index();

                maxDistance = $('#woogosend-table--table_rates--dummy tbody tr')
                  .eq(oldIndex)
                  .find('[data-id="woocommerce_woogosend_max_distance"]')
                  .val();
              }
            },
            change: function (event, ui) {
              if (!maxDistance) {
                $(event.target).sortable('cancel');
              } else {
                var newIndex = ui.placeholder.index();
                var rowIndex = newIndex > oldIndex ? (newIndex - 1) : (newIndex + 1);

                var newMaxDistance = $('#woogosend-table--table_rates--dummy tbody tr')
                  .eq(rowIndex)
                  .find('[data-id="woocommerce_woogosend_max_distance"]')
                  .val();

                if (maxDistance !== newMaxDistance) {
                  $(event.target).sortable('cancel');
                }
              }
            },
          });
          $('#woogosend-table--table_rates--dummy tbody').disableSelection();
        });
      }
    }, 100);
  },
  scrollToTableRate: function () {
    $('.wc-modal-shipping-method-settings').scrollTop($('.wc-modal-shipping-method-settings').find('form').outerHeight());
  },
  hasError: function () {
    $('#woocommerce_woogosend_field_group_table_rates').next('p').next('.woogosend-error-box').remove();

    var uniqueKeys = {};
    var ratesData = [];

    $('#woogosend-table--table_rates--dummy > tbody > tr').each(function () {
      var $row = $(this);
      var rowIndex = $row.index();
      var rowData = {
        index: rowIndex,
        error: false,
        fields: {},
      };

      var uniqueKey = [];

      $row.find('input.woogosend-field--context--hidden').each(function () {
        var $field = $(this);
        var fieldTitle = $field.data('title');
        var fieldKey = $field.data('key');
        var fieldId = $field.data('id');
        var fieldValue = $field.val().trim();

        var fieldData = {
          title: fieldTitle,
          value: fieldValue,
          key: fieldKey,
          id: fieldId,
        };

        if ($field.hasClass('woogosend-field--is-required') && fieldValue.length < 1) {
          fieldData.error = woogosendTableRates.rateRowError(rowIndex, woogosendSprintf(woogosendError('field_required'), fieldTitle));
        }

        if (!fieldData.error && fieldValue.length) {
          if ($field.data('validate') === 'number' && isNaN(fieldValue)) {
            fieldData.error = woogosendTableRates.rateRowError(rowIndex, woogosendSprintf(woogosendError('field_numeric'), fieldTitle));
          }

          var fieldValueInt = parseInt(fieldValue, 10);

          if (typeof $field.attr('min') !== 'undefined' && fieldValueInt < parseInt($field.attr('min'), 10)) {
            fieldData.error = woogosendTableRates.rateRowError(rowIndex, woogosendSprintf(woogosendError('field_min_value'), fieldTitle, $field.attr('min')));
          }

          if (typeof $field.attr('max') !== 'undefined' && fieldValueInt > parseInt($field.attr('max'), 10)) {
            fieldData.error = woogosendTableRates.rateRowError(rowIndex, woogosendSprintf(woogosendError('field_max_value'), fieldTitle, $field.attr('max')));
          }
        }

        if ($field.data('is_rule') && fieldValue.length) {
          uniqueKey.push(sprintf('%s__%s', fieldKey, fieldValue));
        }

        rowData.fields[fieldKey] = fieldData;
      });

      if (uniqueKey.length) {
        var uniqueKeyString = uniqueKey.join('___');

        if (_.has(uniqueKeys, uniqueKeyString)) {
          var duplicateKeys = [];

          for (var i = 0; i < uniqueKey.length; i++) {
            if (uniqueKey[i].indexOf('max_distance') === -1) {
              var keySplit = uniqueKey[i].split('__');
              var title = $row.find('input.woogosend-field--context--hidden[data-key="' + keySplit[0] + '"]').data('title');

              duplicateKeys.push(woogosendSprintf('%s: %s', title, keySplit[1]));
            }
          }

          rowData.error = woogosendTableRates.rateRowError(rowIndex, woogosendSprintf(woogosendError('duplicate_rate_row'), woogosendTableRates.indexToNumber(uniqueKeys[uniqueKeyString]), duplicateKeys.join(', ')));
        } else {
          uniqueKeys[uniqueKeyString] = rowIndex;
        }
      }

      ratesData.push(rowData);
    });

    var errorText = '';

    _.each(ratesData, function (rowData) {
      if (rowData.error) {
        errorText += woogosendSprintf('<p>%s</p>', rowData.error.toString());
      }

      _.each(rowData.fields, function (field) {
        if (field.error) {
          errorText += woogosendSprintf('<p>%s</p>', field.error.toString());
        }
      });
    });

    if (errorText) {
      return $('#woocommerce_woogosend_field_group_table_rates').next('p').after('<div class="error notice woogosend-error-box has-margin">' + errorText + '</div>');
    }

    return false;
  },
  rateRowError: function (rowIndex, errorMessage) {
    return new Error(woogosendSprintf(woogosendError('table_rate_row'), woogosendTableRates.indexToNumber(rowIndex), errorMessage));
  },
  indexToNumber: function (rowIndex) {
    return (rowIndex + 1);
  },
};
