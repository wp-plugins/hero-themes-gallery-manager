var HTAjaxFramework = {
    // dummy url - replaced by WordPresses localize scripts function
    ajaxurl: "http://example.com/wordpress/wp-admin/admin-ajax.php",
};


jQuery(document).ready(function($){
    
    $("#the-list").sortable({
                                helper: function(e, tr){
                                            var originals = tr.children();
                                            var helper = tr.clone();
                                            helper.children().each(function(index)
                                            {
                                              // Set helper cell sizes to match the original sizes
                                              $(this).width($(originals[index]).width());
                                            });
                                            return helper;
                                        },
                                placeholder : "posts-sortable-placeholder",
                                forcePlaceholderSize: true,
                                stop: function(event,ui){ sortFinish(event, ui); }
                            }).disableSelection();



    var sortFinish = function(e, ui){
        console.log('sorting stopped');
        assignNewMenuOrders();
        saveMenuOrder();
    };

    function assignNewMenuOrders(){
        var galleries = $("#the-list").children('tr');
        var order = 10;
        galleries.each(function(){
            var tableRow = $(this);
            tableRow.removeClass('alternate');
            if(order %20 == 0){                
            } else {
                tableRow.addClass('alternate');
            }
            var columnElement = tableRow.children('td.column-order').children('.ht-gallery.post-order');
            var currentOrder = columnElement.html(order);
            columnElement.attr('data-menu-order', order);
            
            order += 10;
            
        });
    }

    function saveMenuOrder(){
        var galleryOrder = new Array();
        //get the items
        var galleryItems = $('.ht-gallery.post-order');
        galleryItems.each(function(){

            var postID = $(this).attr('data-post-id');
            //console.log('postID->' + postID);
            var menuOrder = $(this).attr('data-menu-order');
            //console.log('menuOrder->' + menuOrder);
            //add the new menu order
            galleryOrder.push({ postID: postID, menuOrder: menuOrder});
        });

        //console.log("galleryOrder");
        //console.log(galleryOrder);

        var data = {
            action: 'save_ht_gallery_order',
            gallery_order: galleryOrder
        };

        // since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
        $.post(ajaxurl, data, function(response) {}, 'json');
        }

});

 