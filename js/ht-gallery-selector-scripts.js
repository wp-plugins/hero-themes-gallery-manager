jQuery(document).ready(function($){
    //selector functions
   $( '#ht-gallery-select' ).change(function() {
      showSelectedGalleryPreview();
    });


   function showSelectedGalleryPreview(){
   	 var selectedID = $( '#ht-gallery-select' ).val();
      console.log(selectedID);
      $( '.ht-gallery-select-preview' ).removeClass('show');
      $( '#ht-gallery-select-preview-' + selectedID ).addClass('show');
   }

   window.htSelectHTGallery = function() {
    return fnHTSelectHTGallery();
   }

   function fnHTSelectHTGallery(){
    var galleryIDSelected = $('#ht-gallery-select').val();
    var galleryNameSelected = $('#ht-gallery-select').children('option').filter(':selected').text();
    var galleryColumnsSelected = $('#ht-gallery-columns-select').children('option').filter(':selected').text();
    window.send_to_editor( '[ht_gallery id="' + galleryIDSelected + '" name="' + galleryNameSelected + '"  columns="' + galleryColumnsSelected + '"]' ); 
    $('#select-hero-gallery-dialog').fadeOut();
   }

   window.cancelSelectHTGallery = function() {
    return fnCancelSelectHTGallery();
   }

   function fnCancelSelectHTGallery() {
    window.send_to_editor( '' ); $('#select-hero-gallery-dialog').fadeOut();
   }



   showSelectedGalleryPreview();
});

 