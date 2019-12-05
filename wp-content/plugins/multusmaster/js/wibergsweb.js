jQuery(function ($) {        
    
    $('.acf-th-acf_customer_trailers_totalweight').addClass('hidetextfield');
    $('.acf-th-acf_customer_trailers_yearmodel').addClass('hidetextfield');
    $('.acf-th-acf_customer_trailers_delivery').addClass('hidetextfield');
    $('.field.sub_field.field_type-text.field_key-field_58780a43ce538').addClass('hidetextfield');
    $('.field.sub_field.field_type-text.field_key-field_58780a68ce539').addClass('hidetextfield');
    $('.field.sub_field.field_type-date_picker.field_key-field_58780ad0ce53b ').addClass('hidetextfield');
    
    //Toggle show and hide
    $("a.showandhide-textfields").on("click",function(e) {
        e.preventDefault();
        $("a.showandhide-textfields").toggleClass('showing');
        $('.acf-th-acf_customer_trailers_totalweight').toggleClass('hidetextfield');
        $('.acf-th-acf_customer_trailers_yearmodel').toggleClass('hidetextfield');
        $('.acf-th-acf_customer_trailers_delivery').toggleClass('hidetextfield');
        $('.field.sub_field.field_type-text.field_key-field_58780a43ce538').toggleClass('hidetextfield');
        $('.field.sub_field.field_type-text.field_key-field_58780a68ce539').toggleClass('hidetextfield');
        $('.field.sub_field.field_type-date_picker.field_key-field_58780ad0ce53b ').toggleClass('hidetextfield');        
    });
    
});