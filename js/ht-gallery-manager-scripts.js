var HTAjaxFramework = {
    // dummy url - replaced by WordPresses localize scripts function
    ajaxurl: "http://example.com/wordpress/wp-admin/admin-ajax.php",
    ajaxnonce: "abcdef",
};

var pconfig=false;

jQuery(document).ready(function($){
    
    // prepare the variables that holds the custom media management tool and current selection.
    var heroGalleryManagementTool, heroGallerySelection, lastReplacedID;

    //store an array of videos
    var videos = [];

    //keep a rercord of changing url
    var changingURL = false;
    
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
        loadIDsIntoSelection(ids);
        heroGallerySelection = heroGalleryManagementTool.state().get('selection').toJSON();
    }

    // create a hook for the on close action of the manager to get the selection made
    heroGalleryManagementTool.on('close', function(){
        heroGallerySelection = heroGalleryManagementTool.state().get('selection').toJSON();
        $('#ht_gallery_values').val(getHeroGallerySelectionAsIDs());
        refreshHTGalleryImages();
    });

    // create a hook for the on open function to load the inital selection set the variables
    heroGalleryManagementTool.on('open', function(){
        try{
            //hacky refresh from http://wordpress.stackexchange.com/questions/78230/trigger-refresh-for-new-media-manager-in-3-5
            heroGalleryManagementTool.views._views[".media-frame-content"][0].views._views[""][1].collection.props.set({ignore:(+(new Date()))})
        } catch(err) {
            //dont do anything,  can't refresh
        }
        //get selection
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
        //get videos
        getVideoURLsForIDs(ids);
        //reset
        selection.reset();
        ids.forEach(function(id) {
          if(id && id != ""){
              attachment = wp.media.attachment(id);
              if( attachment.id === undefined ){
                
              }
              attachment.fetch({success: function(data){
                                       replacePlaceholderWithAttachment(data.id, data);  
                                       //set the starred image
                                       setStarredImageFromVal();
                                    },
                                error: function(data){
                                    if(data.id!=undefined){
                                        replacePlaceholderWithRemovedItemLi(data.id, data.id);
                                    }
                                }
                            });
              selection.add( attachment ? [ attachment ] : [] );  
          }            
        });
    }

    /**
    * Adds attachment to selection
    *
    * @param attachment The attachment to be added
    */
    function addAttachmentToSelection(attachment){
        heroGalleryManagementTool.state().get('selection').add(attachment);
        heroGallerySelection.push(attachment);
        //add new id
        $('#ht_gallery_values').val(getHeroGallerySelectionAsIDs());
        //update count
        updateGalleryCount();
        
    }

    /**
    * Get the current selection as an array of IDs
    */
    function getHeroGallerySelectionAsIDs() {
        var currentIDs = $('#ht_gallery_values').val().split(',');
        var idList = [];
        var currentSelection = heroGallerySelection ? heroGallerySelection : [];
        currentSelection.forEach(function(element) {
              idList.push(element['id']);
            });
        var concatIDs = arrayUnique(currentIDs.concat(idList));
        //return concated IDs
        return concatIDs;
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
                var id = $( this ).attr('data-delete-id');

                if(id){
                    deleteItemFromList(id);
                }
     });

    // bind the add images buttons click to open the manager
    // uses live as these items are added to the DOM dynamically
    $('.star-ht-gallery-item').live( 'click', function( event ) {
                event.preventDefault();     
                var id = $( this ).attr('data-star-id');
                var alreadyStarred = $( this ).hasClass('starred');
                if(id){
                    if ( alreadyStarred ) {
                        setStarredImage("", true);
                    } else {
                       setStarredImage(id, true); 
                    }
                    
                }     
     });

    // bind the stamp view button to appropriate actions
    $('a.ht-gallery-manager-stamps-view').click( function( event ) {
                event.preventDefault();
                $('ol#ht-gallery-manager-list').removeClass('details-view');
                $('ol#ht-gallery-manager-list').addClass('stamps-view');
                $('a.ht-gallery-manager-details-view').removeClass('active');
                $('a.ht-gallery-manager-stamps-view').addClass('active');
                $('input#ht_gallery_view').val('stamps');
     });

    // bind the details view button to appropriate actions
    $('a.ht-gallery-manager-details-view').click( function( event ) {
                event.preventDefault();
                $('ol#ht-gallery-manager-list').removeClass('stamps-view');
                $('ol#ht-gallery-manager-list').addClass('details-view');
                $('a.ht-gallery-manager-stamps-view').removeClass('active');
                $('a.ht-gallery-manager-details-view').addClass('active');   
                $('input#ht_gallery_view').val('details');             
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
        var currentSelection = heroGallerySelection ? heroGallerySelection : [];
        var i = 0;
        currentSelection.forEach(function(element) {
            //insert placeholder
            elementToInsert = insertPlaceHolderLi(element.id, true);
            i += 1;
            $('ol#ht-gallery-manager-list').append( $( elementToInsert ).hide().fadeIn(2000) );

            //$('#thumbnails').append($('<li><img src="/photos/t/'+data.filename+'"/></li>').hide().fadeIn(2000));
        });
        updateGalleryCount();

    }

    /**
    * Get gallery item markup
    *
    * @param attachment The attachment for the gallery item
    */
    function getHTMLForGalleryItem(attachment){
        element = attachment.attributes;
        
        var liElementToInsert = '';
        liElementToInsert += '<li id="gallery-item-' + element['id'] + '" class="gallery-item" data-id="' + element['id'] + '" >';

        liElementToInsert += '<div id="star-gallery-item-' + element['id'] + '" class="gallery-item-starred-tools">';
        liElementToInsert += '<a href="#" class="star-ht-gallery-item" data-star-id="' + element['id'] + '"> </a>';
        liElementToInsert += '</div><!-- /gallery-item-starred-tools -->';

        liElementToInsert += '<div id="edit-gallery-item-' + element['id'] + '" class="gallery-item-edit-tools">';
        liElementToInsert += '<a href="' + element['editLink'] + '" class="edit-ht-gallery-item" data-edit-id="' + element['id'] + '" target="_blank"> </a>';
        liElementToInsert += '<a href="#" class="delete-ht-gallery-item" data-delete-id="' + element['id'] + '"> </a>';
        liElementToInsert += '</div><!-- /gallery-item-edit-tools -->';
        var thumbnail = null;

        //image thumbnail
        liElementToInsert += '<div class="ht-gallery-item-thumbnail">';
        try {
            thumbnail = element['sizes']['thumbnail']; 
            liElementToInsert += '<span class="spinner"></span>';
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
            liElementToInsert += '<span>' + framework.title + '</span>';
            liElementToInsert += '<input type="text" class="data-change title" id="edit-title-' + element['id'] + '" data-change="title" value="'+ htEscapeHtml(element['title']) +'">';
            liElementToInsert += '</label>';
            liElementToInsert += '<label class="setting" data-setting="caption">';
            liElementToInsert += '<span>' + framework.caption + '</span>';
            liElementToInsert += '<input type="text" class="data-change caption" id="edit-caption-' + element['id'] + '" data-change="caption" value="'+ htEscapeHtml(element['caption']) +'">';
            liElementToInsert += '</label>';
            liElementToInsert += '<label class="setting" data-setting="alt">';
            liElementToInsert += '<span>' + framework.alt + '</span>';
            liElementToInsert += '<input type="text" class="data-change alt" id="edit-alt-' + element['id'] + '" data-change="alt" value="'+ htEscapeHtml(element['alt']) +'">';
            liElementToInsert += '</label>';
            liElementToInsert += '<label class="setting" data-setting="description">';
            liElementToInsert += '<span>' + framework.description + '</span>';
            liElementToInsert += '<input type="text" class="data-change description" id="edit-description-' + element['id'] + '" data-change="description" value="'+ htEscapeHtml(element['description']) +'">';
            liElementToInsert += '</label>';
            if(framework.video_url_support){
                liElementToInsert += '<label class="setting" data-setting="video">';
                liElementToInsert += '<span>' + framework.url + '</span>';
                liElementToInsert += '<input type="text" class="data-change video" id="edit-video-' + element['id'] + '" data-change="video" value="">';
                liElementToInsert += '</label>' 
            }
            
            liElementToInsert += '</div> <!--/ht-gallery-item-attributes -->'; 
       } catch(err) {
            liElementToInsert += '<img  src="" height="150px" width="150px" />';
       } 
       

        liElementToInsert += '</li>';

        lastReplacedID = element['id'];

        return liElementToInsert;
    }


    /**
    * Get a list element for an element that has been removed the media gallery
    *
    * @param itemID The removed item ID
    */
    function getHTMLForRemovedItem(itemID){
        var liElementToInsert = '';
        liElementToInsert += '<li id="gallery-item-' + itemID + '" class="" data-id="' + itemID + '" >';
        liElementToInsert += '<div id="edit-gallery-item-' + itemID + '" class="gallery-item-edit-tools">';
        liElementToInsert += '<a href="" class="delete-ht-gallery-item" data-delete-id="' + itemID + '"> </a>';
        liElementToInsert += '</div><!-- /gallery-item-edit-tools -->';
        var thumbnail = null;

        //image thumbnail
        liElementToInsert += '<div class="ht-gallery-item-thumbnail">';

        liElementToInsert += '<div class="ht-gallery-item-unavailable">';
        liElementToInsert += '<p>Item deleted from media gallery</p>';
        liElementToInsert += '<div> <!--ht-gallery-item-unavailable -->';
        
        /*
        liElementToInsert += '<img  src="" height="150px" width="150px" />';
        */
        liElementToInsert += '</div><!-- /ht-gallery-item-thumbnail -->';

        liElementToInsert += '<div class="ht-gallery-item-attributes" data-id="' + itemID + '" >';
        liElementToInsert += '</div> <!--/ht-gallery-item-attributes -->'; 
       
        liElementToInsert += '</li>';

        return liElementToInsert;
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
            syncListWithIDs();
            saveImageOrder();
        },
        start: function( event, ui ) {
            //can add text placeholder here if required
        }
    });

    /**
    * Delete a gallery item from list 
    *
    * @param id The id to be removed
    */
    function deleteItemFromList(id){
        //hide slowly then remove and resync
        $('#gallery-item-' + id).hide( 'slow', function(){ 
            $('#gallery-item-' + id).remove(); 
            syncListWithIDs(); 
            saveImageOrder();
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
    * Saves the list order
    */
    function saveImageOrder(){
        var listOrder = $('#ht_gallery_values').val();
        var starredImage = $('#ht_gallery_starred_image').val();
        //shout save, even if empty list
        saveImages(listOrder, starredImage);
    }

    function saveImages(imagesList, starredImage){
        $.post( url = framework.ajaxurl + "?saveimages",
                data = {
                    'action': 'save_ht_gallery_images',
                    'images': imagesList,
                    'starred': starredImage,
                    'post_id' : $( "input#post_ID" ).val() || 0,
                    'security': framework.ajaxnonce,
                },
                success = function(data, textStatus, jqXHR){
                }
            );
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
    *
    * @param id 
    * @param nonce
    * @param name
    * @param value
    */
    function saveAttachmentMetaAjax(id, nonce, name, value){
        var changes = {};
        var keyName = name;
        changes[keyName] = value;

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
    *
    * @param data
    * @param textStatus
    * @param jqXHR
    */
    function saveAttachmentMetaAjaxSucess(data, textStatus, jqXHR){
        //ok
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
        var firingElement = $('#'+event.target.id);
        if(firingElement.length<1)
            return;

        var parentEditImageAttributes = firingElement.closest('.ht-gallery-item-attributes');

        var id = parentEditImageAttributes.attr('data-id');
        var nonce = parentEditImageAttributes.attr('data-nonce');
        var name = firingElement.attr('data-change');
        var newValue = firingElement.val();
        //call the ajax function to save the new values
        saveAttachmentMetaAjax(id, nonce, name, newValue);
    });

    // load the gallery after 3s - this is required until a suitable callback for the manager loaded cycle complete is identified
    refreshHTGalleryImages(); 


    /** GALLERY UPLOAD FUNCTIONS **/
    $( ".ht-drop-files" ).each(function() 
    {
        var uploaderId = $( this ).attr( "id" );

        pconfig = 
        {
            runtimes           : htGalleryUploaderInit.runtimes,
            browse_button      : htGalleryUploaderInit.browse_button,
            container          : uploaderId,
            drop_element       : htGalleryUploaderInit.drop_element,
            file_data_name     : htGalleryUploaderInit.file_data_name,
            multiple_queues    : htGalleryUploaderInit.multiple_queues,
            max_file_size      : htGalleryUploaderInit.max_file_size,
            url                : htGalleryUploaderInit.url,
            flash_swf_url      : htGalleryUploaderInit.flash_swf_url,
            silverlight_xap_url: htGalleryUploaderInit.silverlight_xap_url,
            filters            : htGalleryUploaderInit.filters,
            multipart          : htGalleryUploaderInit.multipart,
            urlstream_upload   : htGalleryUploaderInit.urlstream_upload,
            multi_selection    : htGalleryUploaderInit.multi_selection,
            multipart_params   : 
            {
                _wpnonce : htGalleryUploaderInit.multipart_params.wpnonce,
                action      : htGalleryUploaderInit.multipart_params.action,
                imgid       : uploaderId,
                galleryName : $( '#' + uploaderId + '-gallery-name' ).val(),
                post_id    : $( "input#post_ID" ).val() || 0,
                short       : false,
                long        : true
            }
        };

        pconfig.multi_selection = true;

        

        if(framework.pl2){
            //wp version <= 3.9
            var galleryUploader = new wp.Uploader( {
                    container: '#'+pconfig.container,
                    browser: '#ht-select-files',
                    dropzone: '#'+pconfig.drop_element,
                    params: pconfig.multipart_params
                } );

            var uploader = galleryUploader.uploader;

        } else {
            //wp version < 3.9
            var uploader = new plupload.Uploader( pconfig );
            //initialize the uploader
            uploader.bind( 'Init', function( up ){} );
            uploader.init();

        }

        // a file was added in the queue
        uploader.bind( 'FilesAdded', function( up, files )
        {
            $.each( files, function( i, file ) 
            {
                $('ol#ht-gallery-manager-list').append( insertPlaceHolderLi( file.id, false ) );
            });

            up.refresh();
            up.start();
        });

        uploader.bind( 'UploadProgress', function( up, file ) 
        {
            $( '#' + 'ht-gallery-loading-bar-inner-' + file.id ).width( (100-file.percent) + "%" );
            //$( '#' + file.id + " span" ).html( plupload.formatSize( parseInt( file.size * file.percent / 100 ) ) );
        });

        // a file was uploaded
        uploader.bind( 'FileUploaded', function( up, file, response ) 
        {
            $( '#' + file.id ).fadeOut();
            
            //get the id
            var id;
            id = response['response'];

            //get the new attachment object
            var attachment;
            attachment = wp.media.attachment(id);
            attachment.fetch({success: function(data){
                                        //replace with attachment
                                        replacePlaceholderWithAttachment( file.id, data );
                                        addAttachmentToSelection(data ?  data  : null); 
                                    }
                            });        
            
        });


    });

    /**
    * Insert a placeholder li element until the gallery item is ready
    *
    * @param placeholderID The ID of the placeholder
    * @param spinBoolean Boolean of whether to add a spinner span class
    */
    function insertPlaceHolderLi(placeholderID, spinBoolean){
        var spin = spinBoolean ? "<span class ='spinner'></span>" : "";
        var progressBar = spinBoolean ? "" : "<div class='ht-gallery-loading-bar' id='ht-gallery-loading-bar-" + placeholderID + "'><div class='ht-gallery-loading-bar-inner' id='ht-gallery-loading-bar-inner-" + placeholderID + "'></div>";
        var liToInsert = "";
        liToInsert += "<li class='ht-gallery-placeholder' id='ht-gallery-placeholder-" + placeholderID + "'>";
        liToInsert += spin;
        liToInsert += progressBar;
        liToInsert += "</li>";
        return liToInsert;
    }

    /**
    * Insert a gallery item for when a media item has been removed from the media gallery
    *
    * @param placeholderID The ID of the placeholder
    * @param itemID The ID of the item that has been removed
    */
    function replacePlaceholderWithRemovedItemLi(placeholderID, itemID){
        var removedItemHTML = getHTMLForRemovedItem( itemID );
        $( '#' + 'ht-gallery-placeholder-' + placeholderID ).replaceWith( removedItemHTML );
    }

    /**
    * Replace the placeholder with the gallery item
    *
    * @param placeholderID The ID of the placeholder
    * @param attachment The attachment data to replace
    */
    function replacePlaceholderWithAttachment(placeholderID, attachment){
        $( '#' + 'ht-gallery-placeholder-' + placeholderID ).replaceWith( getHTMLForGalleryItem( attachment ) );
        replaceVideoURL(attachment.id);
        //add spinner to this item until image is loaded
        addSpinnerToLastLoadedItem();
    }

    /**
    * Add a spinner until element loaded
    */
    function addSpinnerToLastLoadedItem(){
        var li = $('li#gallery-item-'+lastReplacedID);
        li.addClass('loading-img');
        li.find('img').each( function() {
                if(this.complete) {
                    li.removeClass('loading-img');
                } else {
                    $(this).load( function() {
                        li.removeClass('loading-img');
                    });
                }
            } );
    }

    /**
    * Set the starred image for a given imageID
    *
    * @param imageID The image ID to set as starred
    * @param saveRequired Whether a save is required (set manually)
    */
    function setStarredImage(imageID, saveRequired){
        $('#ht_gallery_starred_image').val(imageID);
        //remove starred from existing starred items
        $('.star-ht-gallery-item').removeClass('starred');
        //add starred to new items
        $('#star-gallery-item-'+imageID).children('.star-ht-gallery-item').addClass('starred');
        //remove starred class from li item
        $('li.gallery-item.starred').removeClass('starred');
        //add starred class to li item
        $('li#gallery-item-'+imageID).addClass('starred');
        if(saveRequired){
            saveImageOrder();
        }
        
    }


    /**
    * Set the starred image from the preset meta value
    */
    function setStarredImageFromVal(){
        setStarredImage( $('#ht_gallery_starred_image').val() , false );
    }

    //drag enter event
    $('.ht-drop-files .drag-drop-inside').on('dragenter', function(event) {
        if(event.target === this) {
            $( this ).addClass('drag-over');
        }  
    }); 

    //drag leave or mouse drop event
    $('.ht-drop-files .drag-drop-inside').on('dragleave drop', function(event) {
        if(event.target === this) {
            $( this ).removeClass('drag-over');
        }  
    });

    /**
    * Get the video urls for a list of ids
    */
    function getVideoURLsForIDs(ids){
         $.post( url = framework.ajaxurl + "?getvideourls",
                data = {
                    'action': 'get-video-urls',
                    'ids': ids,
                    'security': framework.ajaxnonce,
                },
                success = function(data, textStatus, jqXHR){
                    if(typeof data === 'undefined'){

                    } else {
                        if(data.state == 'success'){
                            //populate the urls
                           
                            for(var id in data.urls) {
                                var url  = data.urls[id] || '';
                                //set object
                                videos[id] = url;
                            }
                        }
                    }
                },
                dataType = 'json'
            );
    }

    function isURLValidVimeoOrYouTube(url){
        var validURL  = false;
        var r = new RegExp(/http:\/\/(www\.)?youtube\.com\/watch.*/);
        validURL = validURL || r.test(url);
        r = new RegExp(/https:\/\/(www\.)?youtube\.com\/watch.*/);
        validURL = validURL || r.test(url);
        var r = new RegExp(/http:\/\/(www\.)?youtu\.be\/.*/);
        validURL = validURL || r.test(url);
        r = new RegExp(/https?:\/\/(.+\.)?vimeo\.com\/.*/);
        validURL = validURL || r.test(url);
        return validURL;

    }

    //bind the blur event to identify when video url change is complete
    $('.ht-gallery-item-attributes input.data-change.video').live('blur', function(event, ui) {
        var firingElement = $('#'+event.target.id);
        if(firingElement.length<1)
            return;
        var urlVal = firingElement.val();
        if(urlVal != ''){
           var urlValid = isURLValidVimeoOrYouTube(urlVal);
            if(!urlValid && changingURL){
                console.log('Invalid Video URL');
                alert(urlVal + ' ' + framework.not_valid_url);
                //set the focus
                firingElement.focus();
                //reset changingURL
                changingURL = false;
            } 
        }
        
        });

    //bind the change event to detect when we are changing the video url
    $('.ht-gallery-item-attributes input.data-change.video').live('change', function(event, ui) {
            changingURL = true;        
    });



    /**
    * Replace a url for a given id with the one loaded in the model
    *
    * @param id The id that needs video the url replacing
    */
    function replaceVideoURL(id){
        //get value from data
        var value = videos[id];
        //replace value
        $('input#edit-video-'+id).val(value);
    }

});


//adapted from http://stackoverflow.com/a/1584377/2985710
function arrayUnique(array) {
    var a = array.concat();
    for(var i=0; i<a.length; ++i) {
        if(a[i]===undefined || a[i]==""){
            a.splice(i, 1);
            continue;
        }
            
        for(var j=i+1; j<a.length; ++j) {
            if(a[i] == a[j])
                a.splice(j--, 1);
        }
    }

    return a;
};


//adapted from http://stackoverflow.com/questions/24816/escaping-html-strings-with-jquery

var htEscapedHTMLEntityMap = {
    "&": "&amp;",
    "<": "&lt;",
    ">": "&gt;",
    '"': '&quot;',
    "'": '&#39;',
    "/": '&#x2F;'
  };

function htEscapeHtml(string) {
    return String(string).replace(/[&<>"'\/]/g, function (s) {
      return htEscapedHTMLEntityMap[s];
    });
  }





 