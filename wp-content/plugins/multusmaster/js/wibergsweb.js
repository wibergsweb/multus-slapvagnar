jQuery(function ($) {        

    $( "ul.gecoitems li" ).on( "click", "a.collage", function() {
        var href = $(this).attr('href');
        if (href.charAt(0) == '#') {
                 
            if ( href == '#infoclick') {
                //Get nearest title and description
                //and apply it to actual section below (called info-click)
                var collage_title = $(this).next('span').html();
                var collage_desc = $(this).next('span').next('span').html();
                $(".infoclick-title").html ( collage_title );
                $(".infoclick-desc").html ( collage_desc );                
                $(".info-click").show();
            }
            else if ( href == '#workingprocess') {
                $('.image-lightbox-gallery').find('#geco-image-0').show().trigger('click').hide(); //Show first to able to trigger!
            }
            else {    
                var splittedVal = href.split('[img]'); //Split if [img] is set                               
                var gallery_ul = splittedVal[0];
                var gallery_ul_splitted = gallery_ul.split('-');
                var html_gallery_element = gallery_ul_splitted[1];
                if ( html_gallery_element !== 'undefined' ) {
                    var what_to_click = '#' + html_gallery_element + '-image-' + splittedVal[1];
                    $(what_to_click).show().trigger('click').hide();
                }
                else {
                    alert(href); //Not a gallery and not nothing spec.
                }
            }
        }
        
      });

    //var href = $(this).attr('href');
});