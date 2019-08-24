
var woogosendFrontendForm = {
    setFields: function () {
        woogosendFrontendForm.fields = $.parseJSON(wc_address_i18n_params.locale.replace(/&quot;/g, '"'));
    },
    bindEvents: function () {
        $(document.body).on('updated_wc_div updated_shipping_method', woogosendFrontendForm.loadForm);
    },
    loadForm: function () {
        _.each(woogosendFrontendForm.getForms(), function (form) {
            var $wrapper = $(form.wrapper);

            if ($wrapper && $wrapper.length) {
                // Add address_1 & address_2 fields to calc_shipping form
                if (form.prefix === 'calc_shipping' &&
                    (woogosend_frontend.shipping_calculator_postcode || woogosend_frontend.shipping_calculator_city)) {
                    var $cloneFieldWrap = $wrapper.find('#calc_shipping_postcode_field');

                    if (!$cloneFieldWrap || !$cloneFieldWrap.length) {
                        $cloneFieldWrap = $wrapper.find('#calc_shipping_city_field');
                    }

                    if ($cloneFieldWrap && $cloneFieldWrap.length) {
                        var addressFields = [];

                        if (woogosend_frontend.shipping_calculator_address_1) {
                            addressFields.push('address_1');
                        }

                        if (woogosend_frontend.shipping_calculator_address_2) {
                            addressFields.push('address_2');
                        }

                        _.each(addressFields, function (addressField) {
                            var $addresFieldWrap = $cloneFieldWrap.clone().attr({
                                id: 'calc_shipping_' + addressField + '_field'
                            });

                            $addresFieldWrap.find('input').attr({
                                id: 'calc_shipping_' + addressField,
                                name: 'calc_shipping_' + addressField,
                                placeholder: woogosendFrontendForm.fields['default'][addressField].placeholder,
                                value: $('#woogosend-calc-shipping-field-value-' + addressField).val()
                            }).trigger('change');

                            $cloneFieldWrap.before($addresFieldWrap);

                            $('#woogosend-calc-shipping-field-value-' + addressField).remove();
                        });
                    }
                }

                $(document.body).trigger('woogosend_form_loaded_' + form.prefix, form);
                $(document.body).trigger('woogosend_form_loaded', form);
            }
        });
    },
    getForms: function () {
        return [{
            wrapper: '.woocommerce-billing-fields__field-wrapper',
            prefix: 'billing'
        }, {
            wrapper: '.woocommerce-shipping-fields__field-wrapper',
            prefix: 'shipping'
        }, {
            wrapper: '.shipping-calculator-form',
            prefix: 'calc_shipping'
        }];
    },
    init: function () {
        // wc_address_i18n_params is required to continue, ensure the object exists
        if (typeof wc_address_i18n_params === 'undefined') {
            return false;
        }

        // wc_country_select_params is required to continue, ensure the object exists
        if (typeof wc_country_select_params === 'undefined') {
            return false;
        }

        woogosendFrontendForm.setFields();
        woogosendFrontendForm.bindEvents();
        woogosendFrontendForm.loadForm();
    }
};

$(document).ready(woogosendFrontendForm.init);