require([
    'jquery',
    'jquery/ui',
    'jquery/validate',
    'mage/translate'],
    function($) {
    'use strict';
        $(function() {
            $.validator.addMethod(
                "accelasearch-googleshopping-notifications",
                function(value, element) {
                    if (value == '1') {
                        if ($( ".accelasearch-googleshopping-recipient" ).length) {
                            return true;
                        } else {
                            return false;
                        }
                    }

                    return true;
                },
                $.mage.__("If Notifier is enabled, it needs to add at least an e-mail recipient.")
            );

            $.validator.addMethod(
                "accelasearch-googleshopping-feed-generation-frequency",
                function(value, element) {
                    if ($("#googleshopping_sync_feed_generation_status").val() == '1') {
                        if (value == '') {
                            return false;
                        } else {
                            return true;
                        }
                    }
                },
                $.mage.__("If Feed Generation is enabled, it need to set a valid frequency.")
            );
        });
    }
);

