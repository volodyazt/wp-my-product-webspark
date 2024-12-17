<?php
/**
 * Шаблон повідомлення про новий товар
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; 
}

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #333;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }

        .email-wrapper {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #e1e1e1;
        }

        .email-header {
            background-color: #0073e6;
            color: white;
            padding: 15px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }

        .email-header h1 {
            margin: 0;
            font-size: 24px;
        }

        .email-body {
            padding: 20px;
            font-size: 16px;
            line-height: 1.6;
        }

        .email-footer {
            text-align: center;
            margin-top: 30px;
            font-size: 14px;
            color: #999;
        }

        a {
            color: #0073e6;
            text-decoration: none;
        }

        p {
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-header">
            <h1>Новий продукт на перевірку</h1>
        </div>

        <div class="email-body">
            <p><strong>Назва товару:</strong> <?php echo esc_html( $product_name ); ?></p>
            <p><strong>Посилання на сторінку автора:</strong> <a href="<?php echo esc_url( $author_url ); ?>"><?php echo esc_html( $author_url ); ?></a></p>
            <p><strong>Посилання на сторінку редагування продукту:</strong> <a href="<?php echo esc_url( $product_edit_url ); ?>"><?php echo esc_html( $product_edit_url ); ?></a></p>
        </div>

        <div class="email-footer">
            <p>Це автоматичне повідомлення. Будь ласка, не відповідайте на цей лист.</p>
        </div>
    </div>
</body>
</html>
