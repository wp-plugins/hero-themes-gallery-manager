jQuery(document).ready(function($){
    //selector functions
   $( '#ht-gallery-select' ).change(function() {
      var selectedID = $( '#ht-gallery-select' ).val();
      console.log(selectedID);
      $( '.ht-gallery-select-preview' ).removeClass('show');
      $( '#ht-gallery-select-preview-' + selectedID ).addClass('show');
    });
});

 