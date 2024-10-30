jQuery(document).ready(function () {
    var input = document.querySelectorAll(".wp-sms-input-mobile, #wp-sms-input-mobile, .user-mobile-wrap #mobile");

    for (var i = 0; i < input.length; i++) {
        if (input[i]) {
            window.intlTelInput(input[i], {
                onlyCountries: wp_camoo_sms_intel_tel_input.only_countries,
                preferredCountries: wp_camoo_sms_intel_tel_input.preferred_countries,
                autoHideDialCode: wp_camoo_sms_intel_tel_input.auto_hide,
                nationalMode: wp_camoo_sms_intel_tel_input.national_mode,
                separateDialCode: wp_camoo_sms_intel_tel_input.separate_dial,
                utilsScript: wp_camoo_sms_intel_tel_input.util_js
            });
        }
    }

    var input = document.querySelector("#job_mobile, #_job_mobile");
    if (input && !input.getAttribute('placeholder')) {
        window.intlTelInput(input, {
            onlyCountries: wp_camoo_sms_intel_tel_input.only_countries,
            preferredCountries: wp_camoo_sms_intel_tel_input.preferred_countries,
            autoHideDialCode: wp_camoo_sms_intel_tel_input.auto_hide,
            nationalMode: wp_camoo_sms_intel_tel_input.national_mode,
            separateDialCode: wp_camoo_sms_intel_tel_input.separate_dial,
            utilsScript: wp_camoo_sms_intel_tel_input.util_js
        });
    }

});