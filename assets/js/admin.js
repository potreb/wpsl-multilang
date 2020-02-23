jQuery(document).ready(function($) {

    jQuery(function ($) {
        $('.wpsl-tab a').click(function () {
            var index = $(this).index();
            $('.wpsl-tab a').removeClass('nav-tab-active');
            $(this).addClass('nav-tab-active');
            var children = $('.wpsl-tab-list').children();
            children.hide();
            children.eq(index).show();
        });

    });

    $('.wpsl-multilangual-post-type-select').select2();
    $('.wpslml-blog-posts').select2();

    var translation_select = $('.wpslml-search-translation');
    if( translation_select.length ) {

        translation_select.select2({
            ajax: {
                url: ajaxurl,
                dataType: 'json',
                delay: 300,
                type: 'POST',
                data: function (params) {
                    let post_blog_id = $(this).attr('data-post_blog_id');
                    let post_type = $(this).attr('data-post_type');
                    return {
                        action: 'wpsl_search_translation_post',
                        post_blog_id: post_blog_id,
                        post_type: post_type,
                        term: params.term
                    };
                },
                processResults: function (data) {
                    return {
                        results: data.items,
                    };
                },
                cache: true,
            },
            minimumInputLength: 3,

        });

        $('.wpsl-multilang-remove-translation').click( function() {
            var select = $(this).closest('label').find('.wpslml-search-translation');
            let object_id = select.attr('data-object_id');
            let object_blog_id = select.attr('data-object_blog_id');
            let post_blog_id = select.attr('data-post_blog_id');
            var post_id = select.val();

            if( post_id === undefined || ! post_id ) {
                return false;
            }

            var data = {
                action: 'wpsl_remove_translation',
                object_id: object_id,
                object_blog_id: object_blog_id,
                post_id: post_id,
                post_blog_id: post_blog_id,
            };

            $.post(ajaxurl, data, function (response) {

            });

            select.val('US');
            select.trigger('change.select2');
        });

        translation_select.on("change", function (e) {
            let object_id = $(this).attr('data-object_id');
            let object_blog_id = $(this).attr('data-object_blog_id');
            let post_blog_id = $(this).attr('data-post_blog_id');

            // set ajax data
            var data = {
                action: 'wpsl_insert_translation',
                object_id: object_id,
                object_blog_id: object_blog_id,
                post_id: $(this).val(),
                post_blog_id: post_blog_id,
            };

            $.post(ajaxurl, data, function (response) {

            });
        });

        $('.wpsl-multilang-duplicate').click( function() {
            let row_select = $(this).closest('.wpsl-language-select').find('.wpslml-search-translation');
            let row_wrapper = $(this).closest('.wpsl-language-select').find('.wpsl-multilang-duplicate-wrapper');

            if( row_select.val() !== null ) {
                alert( wpsl_multilang.before_duplicate );
            } else {
                $(this).hide();
                row_wrapper.show();
            }
            return false;
        });

        $('.wpsl-multilang-duplicate-no').click( function() {
            let row_wrapper = $(this).closest('.wpsl-language-select').find('.wpsl-multilang-duplicate-wrapper');
            let row_duplicate = $(this).closest('.wpsl-language-select').find('.wpsl-multilang-duplicate');
            row_duplicate.show();
            row_wrapper.hide();
            return false;
        });

        $('.wpsl-multilang-duplicate-yes').click( function() {
            let object_id = $(this).attr('data-object_id');
            let object_blog_id = $(this).attr('data-object_blog_id');
            let post_blog_id = $(this).attr('data-post_blog_id');
            var this_yes = $(this);
            // set ajax data
            var data = {
                action: 'wpsl_duplicate_translation',
                object_id: object_id,
                object_blog_id: object_blog_id,
                post_id: $(this).val(),
                post_blog_id: post_blog_id,
            };

            $.post(ajaxurl, data, function ( response ) {
                var newOption = new Option( response.post.post_title, response.post.ID, false, false);
                translation_select.append(newOption).trigger('change');
                if( response.post.ID ) {
                    // set ajax data
                    var data = {
                        action: 'wpsl_insert_translation',
                        object_id: object_id,
                        object_blog_id: object_blog_id,
                        post_id: response.post.ID,
                        post_blog_id: post_blog_id,
                    };
                    $.post(ajaxurl, data, function (response) {
                        let row_wrapper = this_yes.closest('.wpsl-language-select').find('.wpsl-multilang-duplicate-wrapper');
                        let row_duplicate = this_yes.closest('.wpsl-language-select').find('.wpsl-multilang-duplicate');
                        row_duplicate.show();
                        row_wrapper.hide();
                    });
                }
            });


            return false;
        });

    }

});





