(function ($) {

	"use strict";

	$(document).ready(function () {

		if (woogosend_params.show_settings) {

			setTimeout(function () {

                // Try show settings modal on settings page.
				var isMethodAdded = false;
				var methods = $(document).find('.wc-shipping-zone-method-type');
				for (var i = 0; i < methods.length; i++) {
					var method = methods[i];
                    if ($(method).text() == woogosend_params.method_title) {
						$(method).closest('tr').find('.row-actions .wc-shipping-zone-method-settings').trigger('click');
						isMethodAdded = true;
						return;
					}
                }

                // Show Add shipping method modal if the shipping is not added.
				if (!isMethodAdded) {
					$(".wc-shipping-zone-add-method").trigger('click');
                    $("select[name='add_method_id']").val(woogosend_params.method_id);
                }
                
            }, 300);
            
        }
        
	});

})(jQuery);
