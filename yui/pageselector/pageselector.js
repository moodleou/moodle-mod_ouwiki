YUI.add('moodle-mod_ouwiki-pageselector', function(Y) {
    M.mod_ouwiki = M.mod_ouwiki || {};
    M.mod_ouwiki.pageselector = M.mod_ouwiki.pageselector || {
            init: function(pagesselected) {
                Y.all('.ouw_indextree').each(function(list) {
                    list.all('input.ouwiki_page_checkbox').each(function(checkbox) {
                        var pageid = checkbox.get('value');
                        if (pagesselected != null) {
                            for (var a = 0, b = pagesselected.length; a < b; a++) {
                                if (pagesselected[a] == pageid) {
                                    checkbox.set('checked', true);
                                }
                            }
                        }
                        checkbox.on('click', function (e) {
                            // For this checkbox work out if we should select/de-select child pages.
                            var node = e.target;
                            if (node.get('checked')) {
                                // Selecting - select any child pages.
                                node.get('parentNode').all('ul li input.ouwiki_page_checkbox').each(
                                        function (child) {
                                            child.set('checked', true);
                                });
                            } else {
                             // De-selecting - de-select any child pages.
                                node.get('parentNode').all('ul li input.ouwiki_page_checkbox').each(
                                        function (child) {
                                            child.set('checked', false);
                                });
                            }
                        });
                    });
                });
            }
        };
    }, '@VERSION@', {requires: ['node', 'array']}
);
