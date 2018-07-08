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

        $('.simple-pages #content form section, .exhibits #content form section fieldset').filter(':first').each(function(index) {
            var metadata = $(this);
            var record_type;
            var record_id;
            var insertNthChild;
            var form;
            var recordExists;
            if ($('body').hasClass('simple-pages')) {
                record_type = 'SimplePagesPage';
                insertNthChild = '2';
                form = metadata.parent('form');
            } else if ($('body').hasClass('exhibits')) {
                record_type = 'Exhibit';
                insertNthChild = '3';
                form = metadata.parent().parent();
            } else {
                return;
            }
            recordExists = form.find('#save a.delete-confirm');

            // TODO How to get the id of the new record? Use slug?
            var record_id = recordExists.length ? recordExists.attr('href').split('/').pop() : null;

            if (!record_id) {
                var html = '<div class="field">'
                    + '<div id="locale_code-label" class="two columns alpha">'
                    + '<label for="locale_code" class="optional">Locale</label>'
                    + '</div>'
                    + '<div class="inputs five columns omega">'
                    + '<p class="explanation">The locale and the related records can be set only after a first save of this record.</p>'
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
                    $.get(baseUrl + '/admin/multilanguage/translations/list-related-records',
                        {record_type: record_type, record_id: record_id},
                        function(relatedRecords) {
                            $.get(baseUrl + '/admin/multilanguage/translations/list-records-by-slug',
                                {record_type: record_type},
                                function(records) {
                                    $.get(baseUrl + '/admin/multilanguage/translations/locale-code-record',
                                        {record_type: record_type, record_id: record_id},
                                        function(localeCode) {
                                            // Input fields for the current record.
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
                                            html += '<div class="field">'
                                                + '<div id="related_records-label" class="two columns alpha">'
                                                + '<label for="related_records[]" class="optional">Translations</label>'
                                                + '</div>'
                                                + '<div class="inputs five columns omega">'
                                                + '<p class="explanation">The records that are a translation of the current record.</p>'
                                                + '<select name="related_records[]" id="related_records" multiple="multiple">'
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
                                            // Columns are inverted to avoid order issue with array.
                                            $.each(records, function(text, value) {
                                                if (value != record_id) {
                                                    $('#related_records').append(
                                                        $('<option></option>').val(value).html(text)
                                                    );
                                                }
                                            });
                                            var relatedRecordIds = Object.values(relatedRecords);
                                            $('#related_records').val(relatedRecordIds);

                                            // Links to the related records.
                                            html = '<div class="field">'
                                                + '<div>'
                                                + '<label>Translations</label>'
                                                + '</div>'
                                                + '<div class="inputs">'
                                                + '<ul id="related_record_links"></ul>'
                                                + '</div>'
                                                + '</div>';
                                            form.find('#save').append(html);
                                            if (relatedRecordIds.length) {
                                                $.each(relatedRecords, function(text, value) {
                                                    if (value != record_id) {
                                                        if (record_type == 'SimplePagesPage') {
                                                            $('#related_record_links').append(
                                                                $('<li></li>').html('<a href="' + baseUrl + '/admin/simple-pages/index/edit/id/' + value + '">' + text + '</a>')
                                                            );
                                                        } else if (record_type == 'Exhibit') {
                                                            $('#related_record_links').append(
                                                                $('<li></li>').html('<a href="' + baseUrl + '/admin/exhibits/edit/' + value + '">' + text + '</a>')
                                                            );
                                                        }
                                                    }
                                                });
                                            } else  {
                                                $('#related_record_links').append(
                                                    $('<li>No translation</li>')
                                                );
                                            }

                                            // Reset the warning for the full form.
                                            Omeka.warnIfUnsaved();
                                    });
                            });
                    });
            });
        });

    });
}(window.jQuery, window, document));
