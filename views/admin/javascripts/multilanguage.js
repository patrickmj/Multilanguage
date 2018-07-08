jQuery(document).ready(function() {

    /**
     * Add a form to translate record metadata.
     */
    jQuery('#edit-form').on('click', '.multilanguage-code', function() {
        var dialog, data;
        var target = jQuery(this);

        if (!target.data('record-id')) {
            alert('To translate the value of a record, this record must be already saved itself.');
            return;
        }

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
                        'record_id' : target.data('record-id'),
                        'record_type' : target.data('record-type'),
                        'element_id' : target.data('element-id'),
                        'locale_code' : target.data('code'),
                        'text' : text
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
                        'record_id' : target.data('record-id'),
                        'record_type' : target.data('record-type'),
                        'translation' : jQuery('#multilanguage-translation').val(),
                        'element_id' : target.data('element-id'),
                        'locale_code' : target.data('code'),
                        'text' : text,
                        'translation_id' : dialog.translationId
                    };
                    jQuery.post(baseUrl + '/admin/multilanguage/translations/translate',
                        data,
                        function() {
                            jQuery('#multilanguage-modal').dialog("close");
                        }
                    );
                }
            }
        });
    });

});

/**
 * Add some js for translation after an admin page is loaded.
 *
 * The use of a js is required, since the plugins doesn't throw events.
 */
(function($, window, document) {
    $(function() {

        /**
         * Display the language of each record (simple page, exhibit…).
         */

        // It's quicker to get all codes in one query, since there are a few.
        $('.simple-pages #content tbody').filter(':first').each(function(index) {
            var data = {'record_type' : 'SimplePagesPage'};
            var list = $(this);
            $.get(baseUrl + '/admin/multilanguage/translations/list-locale-codes-record',
                data,
                function(localeCodes) {
                    list.find('span.title').each(function(index) {
                        var record_id = $(this).parent('td').find('.action-links li a.edit').attr('href').split('/').pop();
                        $(this).append(
                            $(' <span class="locale-code"></span>').text('[' + localeCodes[record_id] + ']')
                        );
                    });
                }
            );
        });
        $('.simple-pages #page-hierarchy').filter(':first').each(function(index) {
            var data = {'record_type' : 'SimplePagesPage'};
            var list = $(this);
            $.get(baseUrl + '/admin/multilanguage/translations/list-locale-codes-record',
                data,
                function(localeCodes) {
                    list.find('li p a:first-child').each(function(index) {
                        var record_id = $(this).parent('p').find('a.edit').attr('href').split('/').pop();
                        $(this).after(
                            $(' <span class="locale-code"></span>').text('[' + localeCodes[record_id] + ']')
                        );
                    });
                }
            );
        });
        $('.exhibits #content tbody').filter(':first').each(function(index) {
            var data = {'record_type' : 'Exhibit'};
            var list = $(this);
            $.get(baseUrl + '/admin/multilanguage/translations/list-locale-codes-record',
                data,
                function(localeCodes) {
                    list.find('.exhibit .exhibit-info span').each(function(index) {
                        var record_id = $(this).parent('.exhibit-info').find('.action-links li a.edit').attr('href').split('/').pop();
                        $(this).after(
                            $(' <span class="locale-code"></span>').text('[' + localeCodes[record_id] + ']')
                        );
                    });
                }
            );
        });

        /**
         * Add a select input to set the language of a record (simple page, exhibit…).
         */

        $('.simple-pages #content form section').filter(':first').each(function(index) {
            var metadata = $(this);
            var record_type;
            var record_id;
            var insertNthChild;
            var recordExists;
            record_type = 'SimplePagesPage';
            insertNthChild = '2';
            recordExists = metadata.parent('form').find('#save a.delete-confirm');

            // TODO How to get the id of the new record? Use slug?
            var record_id = recordExists.attr('href').split('/').pop();

            if (!recordExists.length) {
                var html = '<div class="field">'
                    + '<div id="locale_code-label" class="two columns alpha">'
                    + '<label for="locale_code" class="optional">Locale</label>'
                    + '</div>'
                    + '<div class="inputs five columns omega">'
                    + '<p class="explanation">The locale can be set only after a first save of this record.</p>'
                    + '<select name="locale_code" id="locale_code" disabled="disabled">'
                    + '<option value="" selected="selected">Select below…</option>'
                    + '</select>'
                    + '</div>'
                    + '</div>';
                metadata.find('.field:nth-child(' + insertNthChild + ')').after(html);
                return;
            }

            $.get(baseUrl + '/admin/multilanguage/translations/list-locale-codes',
                null,
                function(availableCodes) {
                    if (!availableCodes) {
                        return;
                    }

                    $.get(baseUrl + '/admin/multilanguage/translations/locale-code-record',
                        {record_type: record_type, record_id: record_id},
                        function(localeCode) {
                            var html = '<div class="field">'
                                + '<div id="locale_code-label" class="two columns alpha">'
                                + '<label for="locale_code" class="optional">Locale</label>'
                                + '</div>'
                                + '<div class="inputs five columns omega">'
                                + '<p class="explanation">The locale of this record.</p>'
                                + '<select name="locale_code" id="locale_code">'
                                + '<option value="" selected="selected">Select below…</option>'
                                + '</select>'
                                + '</div>'
                                + '</div>';
                            metadata.find('.field:nth-child(' + insertNthChild + ')').after(html);
                            $.each(availableCodes, function(value, text) {
                                $('#locale_code').append(
                                    $('<option></option>').val(value).html(text)
                                );
                            });
                            $('#locale_code').val(localeCode);
                        }
                    );
                }
            );
        });

    });
}(window.jQuery, window, document));
