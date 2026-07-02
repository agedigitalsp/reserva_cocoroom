jQuery(document).ready(function($) {
    $(document).on('click', '.remove-from-cart', function(e) {
        e.preventDefault();
        var cart_item_key = $(this).data('cart_item_key');
        
        $.ajax({
            url: ajax_object.ajax_url, // O tu URL de AJAX si no estás usando ajaxurl de WordPress
            type: 'POST',
            data: {
                'action': 'remove_item_from_cart',
                'cart_item_key': cart_item_key
            },
            success: function(response) {
                // Actualiza el carrito y la página de pago
                $(document.body).trigger('update_checkout');
            },
            error: function(error) {
                console.log(error);
            }
        });
    });
});
