<?php
/**
 * Plugin Name: WP My Product Webspark
 * Description: Плагін для додавання CRUD операцій для товарів через сторінку "My Account".
 * Version: 1.0
 * Author: v.v
 */

// Перевірка наявності WooCommerce
if ( ! class_exists( 'WooCommerce' ) ) {
    exit;
}

// Додаємо сторінки до меню "My Account"
function my_product_webspark_add_my_account_menu_items( $items ) {
    $items['add-product'] = 'Add product';
    $items['my-products'] = 'My products';
    return $items;
}
add_filter( 'woocommerce_account_menu_items', 'my_product_webspark_add_my_account_menu_items' );

// Створення сторінки Add product
function my_product_webspark_add_product_endpoint() {
    add_rewrite_endpoint( 'add-product', EP_ROOT | EP_PAGES );
}
add_action( 'init', 'my_product_webspark_add_product_endpoint' );

function my_product_webspark_add_product_content() {
    ?>
    <form method="post" enctype="multipart/form-data" class="mpv">
        <div class="form-block">
            <label for="product_name"><?php _e('Назва товару:') ?></label>
            <input type="text" id="product_name" name="product_name" required>
        </div>        

        <div class="form-block">
            <label for="product_price"><?php _e('Ціна товару:') ?></label>
            <input type="number" id="product_price" name="product_price" required>
        </div>        

        <div class="form-block">
            <label for="product_quantity"><?php _e('Кількість:') ?></label>
            <input type="number" id="product_quantity" name="product_quantity" required>
        </div>        

        <div class="form-block">
            <label for="product_description"><?php _e('Опис товару:') ?></label>
            <?php wp_editor( '', 'product_description' ); ?>
        </div>

        <div class="form-block">
            <label for="product_image"><?php _e('Зображення товару:') ?></label>
            <input type="button" class="button" value="Вибрати зображення" id="product_image_button">
            <input type="hidden" id="product_image" name="product_image" value="">
        </div>
        
        <!-- Мiнiатюра зображення -->
        <div id="product_image_preview" style="margin-top: 10px;"></div>
        
        <button type="submit" name="save_product"><?php _e('Зберегти продукт') ?></button>
    </form>

    <?php
}

add_action( 'woocommerce_account_add-product_endpoint', 'my_product_webspark_add_product_content' );

// Обробка форми додавання продукту
function my_product_webspark_handle_product_submission() {
    if ( isset( $_POST['save_product'] ) ) {
        $product_name = sanitize_text_field( $_POST['product_name'] );
        $product_price = floatval( $_POST['product_price'] );
        $product_quantity = intval( $_POST['product_quantity'] );
        $product_description = wp_kses_post( $_POST['product_description'] );

        // Завантаження зображення через WP Media
        $image_id = isset( $_POST['product_image'] ) ? intval( $_POST['product_image'] ) : 0;

        // Створення продукту
        $product_data = array(
            'post_title' => $product_name,
            'post_content' => $product_description,
            'post_status' => 'pending',
            'post_type' => 'product',
        );

        $product_id = wp_insert_post( $product_data );

        // Збереження додаткових мета-даних
        update_post_meta( $product_id, '_regular_price', $product_price );
        update_post_meta( $product_id, '_stock', $product_quantity );
        if ( $image_id ) {
            update_post_meta( $product_id, '_thumbnail_id', $image_id );
        }

        // Повідомлення про успішне додавання товару
        if ( $product_id ) {
            wc_add_notice( 'Товар успішно додано!', 'success' );
        }

        // Відправка листа адміну
        my_product_webspark_send_admin_email( $product_id );
    }
} 
add_action( 'template_redirect', 'my_product_webspark_handle_product_submission' );

function my_product_webspark_send_admin_email( $product_id ) {

    $admin_email = get_option( 'admin_email' );

    $product_name = get_the_title( $product_id );

    // Декодуємо символи & у посиланні редагування
    $product_edit_url = html_entity_decode( get_edit_post_link( $product_id ) );

    $author_id = get_post_field( 'post_author', $product_id );

    $author_url = admin_url( "user-edit.php?user_id={$author_id}" );

    // Підготовка даних для шаблону
    $email_data = array(
        'product_name'     => $product_name,
        'author_url'       => $author_url,
        'product_edit_url' => $product_edit_url,
    );

    $template_path = plugin_dir_path( __FILE__ ) . 'emails/custom-product-notification.php';

    // Перевірка наявності файлу шаблону
    if ( file_exists( $template_path ) ) {
        // Завантажуємо та обробляємо шаблон
        ob_start();
        include( $template_path );
        $email_content = ob_get_clean();
    } else {
        // Якщо шаблон не знайдено, надсилаємо простий лист
        $email_content = "Назва товару: $product_name\n";
        $email_content .= "Посилання на сторінку автора: $author_url\n";
        $email_content .= "Посилання на сторінку редагування продукту: $product_edit_url\n";
    }

    $subject = 'Новий продукт на перевірку: ' . $product_name;

    $headers = array('Content-Type: text/html; charset=UTF-8');

    // Надсилаємо лист адміну з використанням кастомного шаблону
    $mail_sent = wp_mail( $admin_email, $subject, $email_content, $headers );

    // Якщо листа не надіслано, виводимо помилку
    if ( !$mail_sent ) {
        error_log('Помилка надсилання листа.');
    }
}


// Додаємо можливість вмикати/вимикати лист через налаштування WooCommerce
function my_product_webspark_email_settings( $settings ) {
    $settings[] = array(
        'name'     => __( 'Enable Product Review Notification', 'my-product-webspark' ),
        'id'       => 'my_product_webspark_enable_email',
        'type'     => 'checkbox',
        'desc'     => __( 'Enable email notification for new/edited products pending review.', 'my-product-webspark' ),
        'default'  => 'yes',
    );
    return $settings;
}

add_filter( 'woocommerce_email_settings', 'my_product_webspark_email_settings' );

// Створення сторінки My products
function my_product_webspark_my_products_endpoint() {
    add_rewrite_endpoint( 'my-products', EP_ROOT | EP_PAGES );
}
add_action( 'init', 'my_product_webspark_my_products_endpoint' );

function my_product_webspark_my_products_content() {

    $paged = (get_query_var('paged')) ? get_query_var('paged') : 1; 

    // Отримуємо продукти користувача
    $user_id = get_current_user_id();
    $args = array(
        'post_type'      => 'product',
        'post_status'    => 'pending',
        'author'         => $user_id,
        'posts_per_page' => 10,
        'paged'          => $paged, 
    );

    $products = new WP_Query( $args );

    if ( $products->have_posts() ) { ?>
        <table cellpadding="5" class="mpv">
        <tr>
            <th><?php _e('Назва товару') ?></th>
            <th><?php _e('Кількість') ?></th>
            <th><?php _e('Ціна') ?></th>
            <th><?php _e('Статус') ?></th>
            <th><?php _e('Редагувати') ?></th>
            <th><?php _e('Видалити') ?></th>
        </tr>
        <?php while ( $products->have_posts() ) {
            $products->the_post();
            $product_id = get_the_ID();
            $price = get_post_meta( $product_id, '_regular_price', true );
            $quantity = get_post_meta( $product_id, '_stock', true );
            $status = get_post_status( $product_id );
        ?>
            <tr>
                <td><?php echo get_the_title() ?></td>
                <td><?php echo $quantity ?></td>
                <td><?php echo $price ?></td>
                <td><?php echo $status ?></td>
                <td><a href='<?php echo get_edit_post_link( $product_id ) ?>'><?php _e('Редагувати') ?></a></td>
                <td><button class='delete-product' data-id='<?php echo $product_id ?>'><?php _e('Видалити') ?></button></td>
            </tr>
        <?php } ?>
        </table>
        <?php
        // Пагiнацiя
        echo paginate_links( array(
            'total'   => $products->max_num_pages, 
            'current' => $paged,                   
         
            'prev_next' => true,                  
            'prev_text' => '<<',           
            'next_text' => '>>',            
        ) );

     } else { ?>
        <p><?php _e('У вас ще немає продуктів.') ?></p>
    <?php }

    wp_reset_postdata();
}



add_action( 'woocommerce_account_my-products_endpoint', 'my_product_webspark_my_products_content' );

function my_product_webspark_delete_product_ajax() {
    // Пeрeвiрка nonce
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'delete_product_nonce' ) ) {
        error_log('Помилка nonce: Неправильний або відсутній nonce.');
        wp_send_json_error(['message' => 'Помилка безпеки: неправильний nonce.']);
    }

    // Перевірка наявності product_id
    if ( ! isset( $_POST['product_id'] ) || empty( $_POST['product_id'] ) ) {
        error_log('Помилка: відсутня product_id.');
        wp_send_json_error(['message' => 'Не передано ID товару.']);
    }

    $product_id = intval( $_POST['product_id'] );

    // Перевірка, чи товар належить поточному користувачеві
    $post_author = get_post_field( 'post_author', $product_id );
    if ( $post_author != get_current_user_id() ) {
        error_log("Помилка прав доступу: Товар ID $product_id не належить користувачеві ID " . get_current_user_id());
        wp_send_json_error(['message' => 'У вас немає прав для видалення цього товару.']);
    }

    // Спроба видалити товар
    $deleted = wp_delete_post( $product_id, true );
    if ( $deleted ) {
        error_log("Успішне видалення: Товар ID $product_id видалено.");
        wp_send_json_success(['message' => 'Товар успішно видалено.']);
    } else {
        error_log("Помилка при видаленні товару ID $product_id.");
        wp_send_json_error(['message' => 'Помилка при видаленні товару.']);
    }
}

add_action( 'wp_ajax_delete_product', 'my_product_webspark_delete_product_ajax' );


// Хук для підключення скриптів
add_action( 'wp_enqueue_scripts', 'my_plugin_enqueue_scripts' );

function my_plugin_enqueue_scripts() {
    wp_enqueue_script( 
        'my-plugin-script', 
        plugin_dir_url( __FILE__ ) . 'js/script.js', 
        array( 'jquery' ), 
        '1.0', 
        true 
    );

    // Передаємо дані з PHP до JavaScript
    wp_localize_script( 'my-plugin-script', 'myPluginData', array(
        'userID'       => get_current_user_id(),
        'ajaxURL'      => admin_url( 'admin-ajax.php' ), 
        'deleteNonce'  => wp_create_nonce( 'delete_product_nonce' ), 
    ) );
}

function my_product_webspark_enqueue_styles() {
    wp_enqueue_style('my-product-webspark-styles', plugin_dir_url(__FILE__) . 'css/styles.css');
}
add_action('wp_enqueue_scripts', 'my_product_webspark_enqueue_styles');

// Вставка HTML повідомлення у футер
function my_product_webspark_add_success_message() { ?>
    <div id="success-message" style="display:none;"><?php _e('Товар успішно видалений') ?></div>
<?php }
add_action('wp_footer', 'my_product_webspark_add_success_message');


function my_product_webspark_restrict_media_library( $query ) {

    $current_user_id = get_current_user_id();

    // Обмежуємо запит медіафайлів лише поточним користувачем
    if ( isset( $query['post_type'] ) && $query['post_type'] === 'attachment' ) {
        $query['author'] = $current_user_id;
    }

    return $query;
}
add_filter( 'ajax_query_attachments_args', 'my_product_webspark_restrict_media_library' );

?>