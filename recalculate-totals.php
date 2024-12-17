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

// recalculate_active_subscriptions_totals();

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

// recalculate_all_order_totals();

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


// recalculate_order_totals(10342);

