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

        // Show API Key instruction
        $(document).off('click', '.woogosend-show-instructions', woogosendBackend.showApiKeyInstructions);
        $(document).on('click', '.woogosend-show-instructions', woogosendBackend.showApiKeyInstructions);

        // Close API Key instruction
        $(document).off('click', '#woogosend-btn--close-instructions', woogosendBackend.closeApiKeyInstructions);
        $(document).on('click', '#woogosend-btn--close-instructions', woogosendBackend.closeApiKeyInstructions);

        // Toggle Store Origin Fields
        $(document).off('change', '#woocommerce_woogosend_origin_type', woogosendBackend.toggleStoreOriginFields);
        $(document).on('change', '#woocommerce_woogosend_origin_type', woogosendBackend.toggleStoreOriginFields);

        // Toggle Store Origin Fields
        $(document).off('change', '#woocommerce_woogosend_api_key_split', woogosendBackend.toggleServerSideAPIKey);
        $(document).on('change', '#woocommerce_woogosend_api_key_split', woogosendBackend.toggleServerSideAPIKey);

        $('#woocommerce_woogosend_origin_type').trigger('change');
        $('#woocommerce_woogosend_api_key_split').trigger('change');

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
                .wrap('<div id="woogosend-field-group-wrap--' + fieldGroupId + '" class="woogosend-field-group-wrap woogosend-field-group-wrap--' + fieldGroupId + '"></div>');

            $fieldGroupDescription
                .appendTo('#woogosend-field-group-wrap--' + fieldGroupId);

            $fieldGroupTable
                .appendTo('#woogosend-field-group-wrap--' + fieldGroupId);

            if ($fieldGroup.hasClass('woogosend-field-group-hidden')) {
                $('#woogosend-field-group-wrap--' + fieldGroupId)
                    .addClass('woogosend-hidden');
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

        woogosendMapPicker.init(params);
        // woogosendTableRates.init(params);
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
        'use strict';
        e.preventDefault();

        $('#btn-ok').trigger('click');
    },
    showApiKeyInstructions: function (e) {
        'use strict';

        e.preventDefault();

        toggleBottons({
            left: {
                id: 'close-instructions',
                label: 'Back',
                icon: 'undo'
            },
            right: {
                id: 'get-api-key',
                label: 'Get API Key',
                icon: 'admin-links'
            }
        });

        $('#woogosend-field-group-wrap--api_key_instruction').fadeIn().siblings().hide();

        $('.modal-close-link').hide();
    },
    closeApiKeyInstructions: function (e) {
        'use strict';

        e.preventDefault();

        $('#woogosend-field-group-wrap--api_key_instruction').hide().siblings().not('.woogosend-hidden').fadeIn();

        $('.modal-close-link').show();

        toggleBottons();
    },
    toggleServerSideAPIKey: function (e) {
        if ($(e.target).is(':checked')) {
            $('#woocommerce_woogosend_api_key_server').closest('tr').removeClass('woogosend-hidden');
        } else {
            $('#woocommerce_woogosend_api_key_server').closest('tr').addClass('woogosend-hidden');
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