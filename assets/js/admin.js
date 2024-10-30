jQuery(document).ready(function () {
	if(jQuery('.camoo-sms-et').length > 0) {
		CamooSms.initialize();
	}

	jQuery(".chosen-select").chosen({width: "25em"});
	// Check about page
	if (jQuery('.wp-sms-welcome').length) {
		jQuery('.nav-tab-wrapper a').click(function () {
			var tab_id = jQuery(this).attr('data-tab');

			if (tab_id == 'link') {
				return true;
			}

			jQuery('.nav-tab-wrapper a').removeClass('nav-tab-active');
			jQuery('.tab-content').removeClass('current');

			jQuery("[data-tab=" + tab_id + "]").addClass('nav-tab-active');
			jQuery("[data-content=" + tab_id + "]").addClass('current');

			return false;
		});
	}
});

var CamooSms=(function($){
	"use strict";
	var me = {
		initialized: false,

		/**
		 * @return {void}
		 */
		initialize: function (){

			if (me.initialized === true) {
				return;
			}

			me.registerEvents();
			me.initialized = true;
		},

		/**
		 * @return {void}
		 */
		registerEvents: function() {

			$('.chosen-select').on('change', function(){

				var valueRaw = $(this).val();
				if (Array.isArray(valueRaw)) {
					return;
				}
				var value = valueRaw.replace(/^\s*|\s*$/g, '');
				if ( value !== '' ) {
					var isCamoo = value.includes('camoo');
					if (isCamoo === false) {
						$('#wp_camoo_sms_settings\\[encrypt_sms\\]').prop('checked', false).prop('disabled', true);
					}
				}
			});
		}
	};
	return {
		'initialize' : me.initialize,
	};
})(jQuery);
