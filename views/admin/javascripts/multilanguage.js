jQuery(document).ready(function() {

    jQuery('#edit-form').on('click', '.multilanguage-code', function() {
        var dialog, data;
        var target = jQuery(this);
        var text = target.parents('.input-block').find('textarea').val();
        dialog = jQuery('#multilanguage-modal').dialog({
            autoOpen: true,
            position: { 'my' : 'right-50', 'at' : 'left', 'of' : target.parents('.input-block') },
            title: "Translate to " + jQuery(this).data('code'),
            height: 250,
            width: 350,
            modal: true,
            open: function(event, ui) {
                data = {
                        'record_id'   : target.data('record-id'),
                        'record_type' : target.data('record-type'),
                        'element_id'  : target.data('element-id'),
                        'locale_code' : target.data('code'),
                        'text'        : text
                    };
                jQuery.get(baseUrl + '/admin/multilanguage/translations/translation', 
                        data, 
                        function(translationData) {
                            jQuery('#multilanguage-translation').val(translationData.translation);
                            dialog.translationId = translationData.id;
                        }
                );
            },
            buttons: {
                "Submit translation" : function() {
                    data = {
                        'record_id'   : target.data('record-id'),
                        'record_type' : target.data('record-type'),
                        'translation' : jQuery('#multilanguage-translation').val(),
                        'element_id'  : target.data('element-id'),
                        'locale_code' : target.data('code'),
                        'text'        : text,
                        'translation_id' : dialog.translationId
                    };
                    jQuery.post(baseUrl + '/admin/multilanguage/translations/translate', data, function() {jQuery('#multilanguage-modal').dialog("close");});
                }
            }
        });
    });
});


