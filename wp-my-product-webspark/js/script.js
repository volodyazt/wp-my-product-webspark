jQuery(document).ready(function($){
    // Відкриття WP Media Library при натисканні на кнопку
    $('#product_image_button').click(function(e) {
        e.preventDefault();
        var image_frame;
        if (image_frame) {
            image_frame.open();
            return;
        }

        image_frame = wp.media({
            title: 'Вибір зображення',
            button: {
                text: 'Вибрати зображення'
            },
            multiple: false, 
            library: {
                type: 'image',
                query: {
                    author: myPluginData.userID // Фільтр за користувачем
                }
            }
        });

        image_frame.on('select', function() {
            var attachment = image_frame.state().get('selection').first().toJSON();
            $('#product_image').val(attachment.id);
            // Відображаємо мініатюру зображення
            $('#product_image_preview').html('<img src="' + attachment.url + '" style="max-width: 200px; max-height: 200px;" />');
        });

        image_frame.open();
    });

    // AJAX запит на видалення товару
    $('.delete-product').click(function(e) {
        e.preventDefault();
        var product_id = $(this).data('id');

        var nonce = myPluginData.deleteNonce;

        $.ajax({
            url: myPluginData.ajaxURL,
            method: 'POST',
            data: {
                action: 'delete_product',
                product_id: product_id,
                nonce: nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#success-message').fadeIn().delay(3000).fadeOut();
                    window.location.reload(); 
                } else {
                    alert('Помилка при видаленні товару');
                }
            }
        });
    });

});

