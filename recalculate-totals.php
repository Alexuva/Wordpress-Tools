<?php
require_once( dirname(__FILE__) . '/wp-load.php' );

// Forzar salida en tiempo real
@ini_set('output_buffering', 'off');
@ini_set('zlib.output_compression', 'Off');
while (ob_get_level()) {
    ob_end_flush();
}
ob_implicit_flush(true);

//Funcion para recalcular todas las suscripciones con un estado concreto
function recalculate_active_subscriptions_totals() {
    
    $args = [
        'post_type'      => 'shop_subscription',
        'post_status'    => ['wc-active', 'wc-on-hold'], 
        'posts_per_page' => -1,            
    ];

    $subscriptions = get_posts($args);

    if (empty($subscriptions)) {
        echo "No se encontraron suscripciones activas.<br>";
        return;
    }

    $total_subscriptions = count($subscriptions);
    echo "Iniciando proceso de recalculado para {$total_subscriptions} suscripciones activas...<br>";

    $counter = 0;

    foreach ($subscriptions as $subscription_post) {
        $subscription = wcs_get_subscription($subscription_post->ID);

        if ($subscription) {
            
            $subscription->calculate_totals();
            $subscription->save();

            $counter++;
            echo "Suscripción ID {$subscription->get_id()} recalculada ({$counter}/{$total_subscriptions})<br>";
        }
    }

    echo "Completado, {$counter} suscripciones actualizadas.";
}

//Funcion para recalcular todos los pedidos con un estado concreto
function recalculate_all_order_totals() {

    $args = [
        'post_type'      => 'shop_order',
        'post_status'    => ['wc-completed', 'wc-processing'],
        'posts_per_page' => -1,
    ];


    $orders = get_posts($args);

    if (empty($orders)) {
        echo "no se encontraron pedidos para recalcular.<br>";
        return;
    }

    foreach ($orders as $order_post) {
        $order = wc_get_order($order_post->id);

        if ($order) {

            $order->calculate_totals();
            $order->save();

            echo "pedido id {$order->get_id()} recalculado correctamente.<br>";
        }
    }

    echo "pedidos recalculados.";
}

//Funcion para recalcular un pedido concreto.
function recalculate_order_totals($order_id) {
    $order = wc_get_order($order_id);

    if ($order) {

        $order->calculate_totals();
        $order->save();

        echo "Totales del pedido recalculados correctamente.";
    } else {
        echo "No se encontró el pedido con el ID: {$order_id}.";
    }
}

//Funcion para recalcular todas las ordenes con un estado concreto
function refresh_all_orders_in_bulk() {
    // Consultar todos los pedidos con estados "procesando" o "pendiente"
    $args = [
        'post_type'      => 'shop_order',
        'post_status'    => ['wc-processing', 'wc-pending'],
        'posts_per_page' => -1,
    ];

    $orders = get_posts($args);

    if (empty($orders)) {
        echo "No se encontraron pedidos en los estados 'procesando' ni 'pendiente'.<br>";
        return;
    }

    echo "Iniciando el proceso para recalcular los pedidos procesando y pendientes...<br>";

    $total_orders = count($orders);
    $counter = 0;

    // Iterar sobre cada pedido
    foreach ($orders as $order_post) {
        $order = wc_get_order($order_post->ID);

        if ($order) {
            echo "Recalculando el pedido con ID {$order->get_id()}...<br>";

            // Iterar sobre los productos del pedido
            foreach ($order->get_items() as $item_id => $item) {
                // Obtener el producto
                $product = $item->get_product();

                if ($product) {
                    // Guardar la ID y la cantidad del producto
                    $product_id = $product->get_id();
                    $quantity = $item->get_quantity();

                    // Eliminar el producto del pedido
                    $order->remove_item($item_id);

                    // Volver a agregar el mismo producto al pedido
                    $order->add_product($product, $quantity);

                    echo "Producto {$product->get_name()} (ID: $product_id) eliminado y vuelto a agregar en el pedido.<br>";
                }
            }

            // Recalcular los totales del pedido
            $order->calculate_totals();
            $order->save();  // Guardar los cambios en el pedido

            echo "El pedido con ID {$order->get_id()} ha sido recalculado correctamente.<br>";
            $counter++;
        }
    }

    echo "Proceso completado. Se actualizaron {$counter} pedidos.<br>";
}

//Funcion para recalcular todas las subs con un estado concreto
function refresh_all_active_and_on_hold_subscriptions() {
    // Configurar la consulta para obtener todas las suscripciones activas y en espera
    $args = [
        'post_type'      => 'shop_subscription',
        'post_status'    => ['wc-active', 'wc-on-hold'],
        'posts_per_page' => -1, 
    ];

    $subscriptions = get_posts($args);

    if (empty($subscriptions)) {
        echo "No se encontraron suscripciones activas ni en espera.<br>";
        return;
    }

    echo "Iniciando el proceso para recalcular las suscripciones activas y en espera...<br>";

    $total_subscriptions = count($subscriptions);
    $counter = 0;

    // Iterar sobre todas las suscripciones
    foreach ($subscriptions as $subscription_post) {
        $subscription = wcs_get_subscription($subscription_post->ID);

        if ($subscription) {
            echo "Recalculando la suscripción con ID {$subscription->get_id()}...<br>";

            // Iterar sobre los productos de la suscripción
            foreach ($subscription->get_items() as $item_id => $item) {
                // Obtener el producto
                $product = $item->get_product();

                if ($product) {
                    // Guardamos la ID y la cantidad del producto
                    $product_id = $product->get_id();
                    $quantity = $item->get_quantity();

                    // Eliminar el producto de la suscripción
                    $subscription->remove_item($item_id);

                    // Volver a agregar el mismo producto
                    $subscription->add_product($product, $quantity);

                    echo "Producto {$product->get_name()} (ID: $product_id) eliminado y vuelto a agregar en la suscripción.<br>";
                }
            }

            // Recalcular los totales de la suscripción
            $subscription->calculate_totals();
            $subscription->save();  // Guardar los cambios en la suscripción

            echo "La suscripción con ID {$subscription->get_id()} ha sido recalculada correctamente.<br>";
            $counter++;
        }
    }

    echo "Proceso completado. Se actualizaron {$counter} suscripciones.<br>";
}

