let baseUrl = document.location.protocol+"//"+document.location.hostname+"/woocommerce/"

if (document.location.hostname !== "localhost") {
    baseUrl = document.location.protocol+"//"+document.location.hostname+"/"
}

jQuery( 'body' ).on( 'updated_checkout', function() {
    firstLoad()
});

jQuery( document ).ready(function() {
    jQuery.get(baseUrl+'?wc-ajax=setFeeZero')
    .done(function(result){
        jQuery( 'body' ).trigger( 'update_checkout' );
        setTimeout(() => {
            jQuery('#payment_method_tdc').change(function(e, params){
                jQuery( 'body' ).trigger( 'update_checkout' );
            })
        }, 2000);
    })
    .fail(function(){
        console.log('ajax request failed. check network log.');
    });
});

function firstLoad() {
    setTimeout(() => {

        jQuery('#E_WALLET').unbind("change")
        jQuery('#VIRTUAL_ACCOUNT').unbind("change")
        jQuery('#QRIS').unbind("change")
        jQuery('#CC').unbind("change")

        jQuery.ajaxSetup({
            beforeSend: function(xhr, settings) {
                jQuery("#divPaymentTdcProcessing").show()
            },
            complete: function() {
                jQuery("#divPaymentTdcProcessing").hide()
            }
        })

        jQuery('#E_WALLET').change(function(e, params){
            const val = jQuery(this).val()
            const splitVal = val.split("|")

            if (splitVal[1] === "OVO") {
                const html = "<div class='divOvoNumber'><label>No. Handphone OVO</label><input type='text' placeholder='OVO Number, Ex. +628577034578' class='form-control' id='ovoNumber' name='ovoNumber' /></div>"
                jQuery(".divOvoNumber").remove();
                jQuery("#E_WALLET").after(html)
            } else {
                jQuery(".divOvoNumber").remove();
            }
             
            // jQuery('#GOPAY').val("GOPAY|0")
            jQuery('#VIRTUAL_ACCOUNT').val("VA|0")
            jQuery('#QRIS').val("QRIS|0")
            jQuery('#CC').val("CC|0")

            let data = {
                paymentMethod : "EWALLET",
                namePayment : splitVal[1]
            }

            if (splitVal[1] === "GOPAY") {
                data = {
                    paymentMethod : "GOPAY",
                    namePayment : splitVal[1]
                }
            }

            jQuery.post(baseUrl+'?wc-ajax=checkFee', data)
            .done(function(result){
                jQuery( 'body' ).trigger( 'update_checkout' );
                jQuery( 'body' ).trigger( 'firstLoad' );
            })
            .fail(function(){
                console.log('ajax request failed. check network log.');
            });
        });

        jQuery('#VIRTUAL_ACCOUNT').change(function(e, params){
            jQuery('#E_WALLET').val("WALLET|0")
            // jQuery('#GOPAY').val("GOPAY|0")
            jQuery('#QRIS').val("QRIS|0")
            jQuery(".divOvoNumber").remove();
            jQuery('#CC').val("CC|0")

            const val = jQuery(this).val()
            const splitVal = val.split("|")

            const data = {
                paymentMethod : splitVal[0],
                namePayment : splitVal[1]
            }

            jQuery.post(baseUrl+'?wc-ajax=checkFee', data)
            .done(function(result){
                jQuery( 'body' ).trigger( 'update_checkout' );
                jQuery( 'body' ).trigger( 'firstLoad' );
            })
            .fail(function(){
                console.log('ajax request failed. check network log.');
            });
            
        });

        jQuery('#QRIS').change(function(e, params){
            jQuery('#E_WALLET').val("WALLET|0")
            // jQuery('#GOPAY').val("GOPAY|0")
            jQuery('#VIRTUAL_ACCOUNT').val("VA|0")
            jQuery('#CC').val("CC|0")
            jQuery(".divOvoNumber").remove();

            const val = jQuery(this).val()
            const splitVal = val.split("|")

            const data = {
                paymentMethod : splitVal[0],
                namePayment : splitVal[1]
            }

            jQuery.post(baseUrl+'?wc-ajax=checkFee', data)
            .done(function(result){
                jQuery( 'body' ).trigger( 'update_checkout' );
                jQuery( 'body' ).trigger( 'firstLoad' );
            })
            .fail(function(){
                console.log('ajax request failed. check network log.');
            });
        });

        jQuery('#CC').change(function(e, params){
            jQuery('#E_WALLET').val("WALLET|0")
            // jQuery('#GOPAY').val("GOPAY|0")
            jQuery('#QRIS').val("QRIS|0")
            jQuery('#VIRTUAL_ACCOUNT').val("VA|0")
            jQuery(".divOvoNumber").remove();

            const val = jQuery(this).val()
            const splitVal = val.split("|")

            const data = {
                paymentMethod : splitVal[0],
                namePayment : splitVal[1]
            }

            jQuery.post(baseUrl+'?wc-ajax=checkFee', data)
            .done(function(result){
                jQuery( 'body' ).trigger( 'update_checkout' );
                jQuery( 'body' ).trigger( 'firstLoad' );
            })
            .fail(function(){
                console.log('ajax request failed. check network log.');
            });
        });

        checkDevice();
    }, 2000);
}

function checkDevice() {
    if( /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) ) {
        jQuery('#device_tdc').val("mobile")
    } else {
        jQuery('#device_tdc').val("desktop")
    }
}