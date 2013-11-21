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

   showSelectedGalleryPreview();
});

 