jQuery(function ($) {    
    
    $(document).on('submit', '.mcwp-currencyconverterform', function(e) {
            var use_ajax = $(this).attr('data-useajax');
            if (parseInt(use_ajax) === 0) {
                return true; //normal submission
            }
            
            //Ajax should be used, return values from form directly
            var this_form = $(this);
            e.preventDefault();
            var amount = $(this).find(".mcwp-selectamount input").val();
            var from_ecb = $(this).find(".mcwp-selectfromto .mcwp-selectfrom select").val();
            var to_ecb = $(this).find(".mcwp-selectfromto .mcwp-selectto select").val();
            var result_decimals = $(this).find(".mcwp-decimals").val();
            var result_sanitize = $(this).find(".mcwp-sanitize").val();
            
            var mcconvert = $.ajax({            
                type: 'POST',
                data:{
                    action: 'convertcurrency',    
                    use_ajax: 1,
                    amount: amount,
                    from: from_ecb,
                    to: to_ecb,
                    result_decimals: result_decimals,
                    result_sanitize: result_sanitize
                    },
                    url: '/wp-admin/admin-ajax.php',
                    dataType: 'json'
            });

            mcconvert.done(function( currency_result ) {
                this_form.next().find('.mcwp-currency').html( currency_result.from );
                this_form.next().find('.mcwp-tocurrency').html( currency_result.to );
            });
            
    });
    
    
                
});