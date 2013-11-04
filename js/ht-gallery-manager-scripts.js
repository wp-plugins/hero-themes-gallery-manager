var HTAjaxFramework = {
    // dummy url - replaced by WordPresses localize scripts function
    ajaxurl: "http://example.com/wordpress/wp-admin/admin-ajax.php",
};


jQuery(document).ready(function($){
    console.log('gallery-manager-loaded');
    
    // prepare the variables that holds the custom media management tool and current selection.
    var heroGalleryManagementTool, heroGallerySelection;
    
    // if the frame already exists, re-open it.
    if (heroGalleryManagementTool) {
        heroGalleryManagementTool.open();
        return;
    }


    // create the media frame with options if it doesn't exist
    heroGalleryManagementTool = wp.media.frames.heroGalleryManagementTool = wp.media({
        className: 'media-frame tgm-media-frame',
        frame: 'select',
        multiple: true,
        title: "Select Images",
        library: {
            type: 'image'
        },
        button: {
            text: "Insert selection"
        }
    });

    // hide the gallery manager empty message
    hideGalleryEmptyMessage();

    // open/close cycle to initialize variables (hence delay requirement todo - integrate a callback hook)
    heroGalleryManagementTool.open();
    heroGalleryManagementTool.close();


    /**
    * Load the initial selection from the ht_gallery_values hidden input box
    */
    function loadSelectionFromIDs(){
        ids = $('#ht_gallery_values').val().split(',');
        console.log("loading as");
        loadIDsIntoSelection(ids);
        heroGallerySelection = heroGalleryManagementTool.state().get('selection').toJSON();
    }

    // create a hook for the on close action of the manager to get the selection made
    heroGalleryManagementTool.on('close', function(){
        console.log ("close event");
        heroGallerySelection = heroGalleryManagementTool.state().get('selection').toJSON();
        $('#ht_gallery_values').val(getHeroGallerySelectionAsIDs());
        refreshHTGalleryImages();
    });

    // create a hook for the on open function to load the inital selection set the variables
    heroGalleryManagementTool.on('open', function(){
        //get selection
        console.log('setting selection');
        loadSelectionFromIDs();
        var currentSelectionIds = [];
        var currentSelection = heroGallerySelection ? heroGallerySelection : [];
        currentSelection.forEach(function(element) {
              currentSelectionIds.push(element['id']);
            });
        loadIDsIntoSelection(currentSelectionIds);
    });

    /**
    * Load a set of IDs into the selected media attachments
    *
    * @param ids The ids to be loaded
    */
    function loadIDsIntoSelection(ids){
        var selection = heroGalleryManagementTool.state().get('selection');
        //reset
        selection.reset();
        console.log('ids');
        console.log(ids);  
        ids.forEach(function(id) {
          if(id && id != ""){
              attachment = wp.media.attachment(id);
              attachment.fetch();
              selection.add( attachment ? [ attachment ] : [] );
              console.log("attachment");
              console.log(attachment);
          }            
        });
    }

    /**
    * Get the current selection as an array of IDs
    */
    function getHeroGallerySelectionAsIDs() {
        var idList = [];
        var currentSelection = heroGallerySelection ? heroGallerySelection : [];
        console.log(currentSelection);
        currentSelection.forEach(function(element) {

              console.log(element);  
              idList.push(element['id']);
            });
        return idList;
    }
    

    // bind the add images buttons click to open the manager
    $('.ht-gallery-add-images').click( function( event ) {
                event.preventDefault();     
                heroGalleryManagementTool.open();     
     });

    // bind the refresh images buttons to refresh the manager
    $('.ht-gallery-refresh-images').click( function( event ) {
                event.preventDefault();
                refreshHTGalleryImages();     
     });

    // bind the delete gallery item button to delete function
    // uses live as these items are added to the DOM dynamically
    $('a.delete-ht-gallery-item').live( 'click', function( event ) {
                event.preventDefault();
                console.log("deleting event");
                var id = $( this ).attr('data-delete-id');

                if(id){
                    deleteItemFromList(id);
                }
     });

    // bind the stamp view button to appropriate actions
    $('a.ht-gallery-manager-stamps-view').click( function( event ) {
                event.preventDefault();
                $('ol#ht-gallery-manager-list').removeClass('details-view');
                $('ol#ht-gallery-manager-list').addClass('stamps-view');
                $('a.ht-gallery-manager-details-view').removeClass('active');
                $('a.ht-gallery-manager-stamps-view').addClass('active');
     });

    // bind the details view button to appropriate actions
    $('a.ht-gallery-manager-details-view').click( function( event ) {
                event.preventDefault();
                $('ol#ht-gallery-manager-list').removeClass('stamps-view');
                $('ol#ht-gallery-manager-list').addClass('details-view');
                $('a.ht-gallery-manager-stamps-view').removeClass('active');
                $('a.ht-gallery-manager-details-view').addClass('active');                
     });

    
    /**
    * Clear the current list of images
    */
    function clearHTGalleryImages(){
        $('ol#ht-gallery-manager-list').empty();
    }
    
    /**
    * Refresh the list of gallery items
    */
    function refreshHTGalleryImages(){
        loadSelectionFromIDs();
        clearHTGalleryImages();
        console.log('refreshHTGalleryImages');
        var currentSelection = heroGallerySelection ? heroGallerySelection : [];
        currentSelection.forEach(function(element) {
            console.log('element');
            console.log(element);
            var liElementToInsert = '';
            liElementToInsert += '<li id="gallery-item-' + element['id'] + '" class="" data-id="' + element['id'] + '" >';
            liElementToInsert += '<div id="edit-gallery-item-' + element['id'] + '" class="gallery-item-edit-tools">';
            liElementToInsert += '<a href="' + element['editLink'] + '" class="edit-ht-gallery-item" data-edit-id="' + element['id'] + '"> </a>';
            liElementToInsert += '<a href="" class="delete-ht-gallery-item" data-delete-id="' + element['id'] + '"> </a>';
            liElementToInsert += '</div><!-- /gallery-item-edit-tools -->';
            var thumbnail = null;

            //image thumbnail
            liElementToInsert += '<div class="ht-gallery-item-thumbnail">';
            try {
                thumbnail = element['sizes']['thumbnail']; 
                liElementToInsert += '<img class="img-item"  src="' + thumbnail['url'] + '" height="' + thumbnail['height'] + 'px" width="' + thumbnail['width'] + 'px" />';
           } catch(err) {
                liElementToInsert += '<img  src="" height="150px" width="150px" />';
           }  
           liElementToInsert += '</div><!-- /ht-gallery-item-thumbnail -->';
            try {
                //image attributes
                liElementToInsert += '<div class="ht-gallery-item-attributes" data-id="' + element['id'] + '" data-nonce="' + element['nonces']['update'] + '">';
          
                //todo ajaxify form ;) and i18n
                liElementToInsert += '<label class="setting" data-setting="title">';
                liElementToInsert += '<span>Title</span>';
                liElementToInsert += '<input type="text" class="data-change title" id="edit-title-' + element['id'] + '" data-change="title" value="'+element['title']+'">';
                liElementToInsert += '</label>';
                liElementToInsert += '<label class="setting" data-setting="caption">';
                liElementToInsert += '<span>Caption</span>';
                liElementToInsert += '<input type="text" class="data-change caption" id="edit-caption-' + element['id'] + '" data-change="caption" value="'+element['caption']+'">';
                liElementToInsert += '</label>';
                liElementToInsert += '<label class="setting" data-setting="alt">';
                liElementToInsert += '<span>Alternative Text</span>';
                liElementToInsert += '<input type="text" class="data-change alt" id="edit-alt-' + element['id'] + '" data-change="alt" value="'+element['alt']+'">';
                liElementToInsert += '</label>';
                liElementToInsert += '<label class="setting" data-setting="description">';
                liElementToInsert += '<span>Description</span>';
                liElementToInsert += '<input type="text" class="data-change description" id="edit-description-' + element['id'] + '" data-change="description" value="'+element['description']+'">';
                liElementToInsert += '</label>';
                liElementToInsert += '</div> <!--/ht-gallery-item-attributes -->'; 
           } catch(err) {
                liElementToInsert += '<img  src="" height="150px" width="150px" />';
           } 
           

            liElementToInsert += '</li>';
            $('ol#ht-gallery-manager-list').append( $(liElementToInsert).hide().fadeIn(2000) );

            //$('#thumbnails').append($('<li><img src="/photos/t/'+data.filename+'"/></li>').hide().fadeIn(2000));
        });
        updateGalleryCount(currentSelection.length);
    }

    // make the list of gallery items sortable
    $('#ht-gallery-manager-list').sortable({
        revert: "invalid",
        cursor: "move" ,
        helper: "clone",
        placeholder : "sortable-placeholder",
        change: function(event, ui) {
        },
        stop: function( event, ui ) {
            console.log('sorting stopped');
            syncListWithIDs();
        },
        start: function( event, ui ) {
            //can add text placeholder here if required
        }
    });

    /**
    * Delete a gallery item from list 
    */
    function deleteItemFromList(id){
        console.log("deleting->"+id);
        //hide slowly then remove and resync
        $('#gallery-item-' + id).hide( 'slow', function(){ 
            $('#gallery-item-' + id).remove(); 
            syncListWithIDs(); 
            updateGalleryCount(); 
        } );
    }

    /**
    * Sync the list with hidden gallery values input, used to save the post meta
    */
    function syncListWithIDs(){
        //loop through the ol and parse the ids
        var list = $('#ht-gallery-manager-list');
        var newListString = '';
        var separator = '';
        list.children('li').each(function( index ) {
           newListString += separator;
           newListString += $( this ).attr('data-id');
           separator = ',';
        });
        $('#ht_gallery_values').val(newListString);
    }

    /**
    * Update the count of the gallery items
    */
    function updateGalleryCount(){
        var idVal = $('#ht_gallery_values').val()
        var idLength =  idVal.split(',').length;
        var idListValid = idVal && idVal != "" ? true : false;
        newIDLength = idLength && idListValid ? idLength : 0;
        $('.ht-gallery-manager-gallery-details-count').html(newIDLength);

        //show or hide empty gallery message
        if(newIDLength>0){
            hideGalleryEmptyMessage();
        } else {
            showGalleryEmptyMessage();
        }
    }

    /**
    * Save attachement meta via ajax
    */
    function saveAttachmentMetaAjax(id, nonce, name, value){
        var changes = {};
        var keyName = name;
        changes[keyName] = value;

        console.log("changes");
        console.log(changes);
        $.post( url = framework.ajaxurl + "?update",
                data = {
                    'action': 'save-attachment',
                    'id': id,
                    'nonce': nonce,
                    'changes' : changes
                },
                success = function(data, textStatus, jqXHR){
                    saveAttachmentMetaAjaxSucess(data, textStatus, jqXHR);
                }
            );
    }

    /**
    * A callback function for success of the saveAttachmentMetaAjax ajax call
    */
    function saveAttachmentMetaAjaxSucess(data, textStatus, jqXHR){
        console.log("success");
        console.log(data);
    }

    /**
    * Hide the gallery empty message
    */
    function hideGalleryEmptyMessage(){
        $('.ht-gallery-manager-gallery-empty').hide();
    }

    /**
    * Hide the gallery empty message
    */
    function showGalleryEmptyMessage(){
        $('.ht-gallery-manager-gallery-empty').fadeIn('slow');
    }  

    // bind the data input change blur event to save the attachment meta
    $('.ht-gallery-item-attributes input.data-change').live('blur', function(event, ui) {
                            console.log( 'blur event' );
                            console.log(event.target.id);
                            var firingElement = $('#'+event.target.id);
                            if(firingElement.length<1)
                                return;

                            console.log("firingElementID");
                            console.log(firingElement);

                            var parentEditImageAttributes = firingElement.closest('.ht-gallery-item-attributes');
                            console.log("parentEditImageAttributes");
                            console.log(parentEditImageAttributes);

                            var id = parentEditImageAttributes.attr('data-id');
                            console.log(id);
                            var nonce = parentEditImageAttributes.attr('data-nonce');
                            console.log(nonce);
                            var name = firingElement.attr('data-change');
                            console.log(name);
                            var newValue = firingElement.val();
                            console.log(newValue);
                            //call the ajax function to save the new values
                            saveAttachmentMetaAjax(id, nonce, name, newValue);
                        });

    // load the gallery after 3s - this is required until a suitable callback for the manager loaded cycle complete is identified
    setTimeout( function(){ 
        refreshHTGalleryImages(); 
    }, 3000);

});

 