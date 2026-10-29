<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// -----------------------------------------------------------------------------
// 1. REGISTER SETTINGS & DEFAULTS
// -----------------------------------------------------------------------------
add_action( 'admin_menu', 'cwdn_add_admin_menu', 99 );
function cwdn_add_admin_menu() {
    add_submenu_page( 
        'woocommerce', 
        'Discord Notifications', 
        'Discord Notifications', 
        'manage_woocommerce', 
        'cwdn-settings', 
        'cwdn_render_settings_page' 
    );
}

add_action( 'admin_init', 'cwdn_register_settings' );
function cwdn_register_settings() {
    register_setting( 'cwdn_settings_group', 'cwdn_config' );
}

function cwdn_get_options() {
    $defaults = [ 'min_order' => '1', 'notifs' => [] ];

    $order_defaults = ['enable' => '0', 'url' => '', 'mention' => '', 'color' => '#5cb85c', 'data' => ['name' => '1', 'total' => '1', 'items' => '1']];
    
    $statuses = ['completed', 'pending', 'on-hold', 'processing', 'failed', 'refunded', 'cancelled'];
    foreach ( $statuses as $s ) {
        $defaults['notifs'][$s] = $order_defaults;
    }
    
    // Adjust colors for specific order statuses
    $defaults['notifs']['pending']['color']    = '#f0ad4e';
    $defaults['notifs']['on-hold']['color']    = '#f0ad4e';
    $defaults['notifs']['processing']['color'] = '#9b59b6';
    $defaults['notifs']['failed']['color']     = '#d9534f';
    $defaults['notifs']['refunded']['color']   = '#7f8c8d';
    $defaults['notifs']['cancelled']['color']  = '#d9534f';

    $defaults['notifs']['new_customer'] = ['enable' => '0', 'url' => '', 'mention' => '', 'color' => '#3498db', 'data' => ['name' => '1']];
    $defaults['notifs']['low_stock']    = ['enable' => '0', 'url' => '', 'mention' => '', 'color' => '#f1c40f', 'data' => []];
    $defaults['notifs']['new_review']   = ['enable' => '0', 'url' => '', 'mention' => '', 'color' => '#2ecc71', 'data' => ['name' => '1', 'hide_title' => '1']];

    $saved = get_option( 'cwdn_config', [] );
    $options = wp_parse_args( $saved, $defaults );

    // Smarter merge to prevent defaults from overriding explicitly unchecked boxes
    foreach ( $defaults['notifs'] as $k => $v ) {
        if ( isset( $saved['notifs'][$k] ) ) {
            $options['notifs'][$k] = wp_parse_args( $saved['notifs'][$k], $v );
            $options['notifs'][$k]['data'] = isset($saved['notifs'][$k]['data']) ? $saved['notifs'][$k]['data'] : [];
        }
    }

    return $options;
}

// -----------------------------------------------------------------------------
// 2. RENDER ADMIN UI
// -----------------------------------------------------------------------------
function cwdn_render_settings_page() {
    $options = cwdn_get_options();
    $notifs = $options['notifs'];

    // Define exactly what checkboxes exist for each type
    $order_schema = [
        'name'  => 'Name',
        'email' => 'Email',
        'ip'    => 'IP Address',
        'total' => 'Total',
        'items' => 'Items'
    ];
    
    $customer_schema = [
        'name'  => 'Name',
        'email' => 'Email',
        'ip'    => 'IP Address'
    ];

    $review_schema = [
        'name'       => 'Name',
        'email'      => 'Email',
        'ip'         => 'IP Address',
        'hide_title' => 'Hide Title if Empty'
    ];

    $render_row = function($key, $label, $schema) use ($notifs) {
        $data = $notifs[$key];
        $checked = !empty($data['enable']) ? 'checked' : '';
        
        echo "<tr>";
        echo "<td><strong>{$label}</strong></td>";
        echo "<td><input type='checkbox' name='cwdn_config[notifs][{$key}][enable]' value='1' {$checked}></td>";
        
        echo "<td>";
        echo "<div style='display: flex; gap: 4px; align-items: center;'>";
        echo "<input type='password' id='url_{$key}' name='cwdn_config[notifs][{$key}][url]' value='" . esc_attr($data['url']) . "' placeholder='https://discord.com/api/webhooks/...' style='width: 100%;'>";
        echo "<button type='button' class='button button-secondary' onclick='var x = document.getElementById(\"url_{$key}\"); x.type = (x.type === \"password\" ? \"text\" : \"password\");' title='Show/Hide URL'>👁️</button>";
        echo "</div>";
        echo "</td>";

        echo "<td><input type='text' name='cwdn_config[notifs][{$key}][mention]' value='" . esc_attr($data['mention']) . "' style='width: 80px;' placeholder='@here'></td>";
        echo "<td><input type='color' name='cwdn_config[notifs][{$key}][color]' value='" . esc_attr($data['color']) . "'></td>";
        
        echo "<td style='font-size: 11px;'>";
        foreach ($schema as $data_key => $friendly_name) {
            // Read from the saved data to see if it is checked
            $chk = !empty($data['data'][$data_key]) ? 'checked' : '';
            echo "<label style='display:block; margin-bottom: 2px;'><input type='checkbox' name='cwdn_config[notifs][{$key}][data][{$data_key}]' value='1' {$chk}> {$friendly_name}</label>";
        }
        echo "</td>";
        echo "</tr>";
    };
    ?>
    <div class="wrap">
        <h2>Discord Notifications Configuration</h2>
        <form method="post" action="options.php">
            <?php settings_fields( 'cwdn_settings_group' ); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">Minimum Order Value</th>
                    <td>
                        <input type="number" name="cwdn_config[min_order]" value="<?php echo esc_attr($options['min_order']); ?>" style="width: 80px;">
                        <span class="description">Only notify for orders above this amount. Set to 0 for all.</span>
                    </td>
                </tr>
            </table>

            <h3>Order Notifications</h3>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 100px;">Status</th>
                        <th style="width: 50px;">Enable</th>
                        <th>Webhook URL</th>
                        <th style="width: 100px;">Mention</th>
                        <th style="width: 60px;">Color</th>
                        <th style="width: 180px;">Data to Include</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $render_row('completed', 'Completed', $order_schema);
                    $render_row('pending', 'Pending', $order_schema);
                    $render_row('on-hold', 'On Hold', $order_schema);
                    $render_row('processing', 'Processing', $order_schema);
                    $render_row('failed', 'Failed', $order_schema);
                    $render_row('refunded', 'Refunded', $order_schema);
                    $render_row('cancelled', 'Cancelled', $order_schema);
                    ?>
                </tbody>
            </table>

            <h3>Special Notifications</h3>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 100px;">Status</th>
                        <th style="width: 50px;">Enable</th>
                        <th>Webhook URL</th>
                        <th style="width: 100px;">Mention</th>
                        <th style="width: 60px;">Color</th>
                        <th style="width: 180px;">Data to Include</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $render_row('new_customer', 'New Customer', $customer_schema);
                    $render_row('low_stock', 'Low Stock Alert', []);
                    $render_row('new_review', 'New Product Review', $review_schema);
                    ?>
                </tbody>
            </table>

            <?php submit_button('Save Settings'); ?>
        </form>
    </div>
    <?php
}

// -----------------------------------------------------------------------------
// 3. EVENT DISPATCHER CORE
// -----------------------------------------------------------------------------
function cwdn_dispatch_discord_webhook( $event_key, $message_content, $embed_data ) {
    $options = cwdn_get_options();
    $config  = $options['notifs'][$event_key] ?? null;

    if ( ! $config || empty( $config['enable'] ) || empty( $config['url'] ) ) {
        return;
    }

    $color_dec = hexdec( ltrim( $config['color'], '#' ) );
    $embed_data['color'] = $color_dec;
    $embed_data['timestamp'] = date('c');
    
    $payload = [
        'content' => !empty($config['mention']) ? $config['mention'] . ' ' . $message_content : $message_content,
        'embeds'  => [$embed_data]
    ];

    wp_remote_post( $config['url'], [
        'headers' => [ 'Content-Type' => 'application/json' ],
        'body'    => wp_json_encode( $payload ),
    ]);
}

// -----------------------------------------------------------------------------
// 4. WOOCOMMERCE ORDER HOOKS
// -----------------------------------------------------------------------------
add_action( 'woocommerce_order_status_changed', 'cwdn_handle_order_status_change', 10, 4 );
function cwdn_handle_order_status_change( $order_id, $old_status, $new_status, $order ) {
    $options = cwdn_get_options();
    
    if ( (float) $order->get_total() < (float) $options['min_order'] ) { return; }
    if ( ! isset( $options['notifs'][$new_status] ) ) { return; }

    $data_prefs = $options['notifs'][$new_status]['data'] ?? [];
    $fields = [[ 'name' => 'Order ID', 'value' => (string)$order_id, 'inline' => true ]];

    if ( !empty($data_prefs['name']) ) {
        $fields[] = [ 'name' => 'Customer', 'value' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(), 'inline' => true ];
    }
    if ( !empty($data_prefs['total']) ) {
        $fields[] = [ 'name' => 'Total', 'value' => $order->get_total() . ' ' . $order->get_currency(), 'inline' => true ];
    }
    if ( !empty($data_prefs['items']) ) {
        $fields[] = [ 'name' => 'Items', 'value' => (string) $order->get_item_count(), 'inline' => true ];
    }
    if ( !empty($data_prefs['email']) ) {
        $fields[] = [ 'name' => 'Email', 'value' => $order->get_billing_email(), 'inline' => true ];
    }
    if ( !empty($data_prefs['ip']) ) {
        $fields[] = [ 'name' => 'IP Address', 'value' => $order->get_customer_ip_address(), 'inline' => true ];
    }

    cwdn_dispatch_discord_webhook( 
        $new_status, 
        "", 
        [
            'title'       => "Order #{$order_id} is now {$new_status}",
            'description' => "Status update processed.",
            'fields'      => $fields
        ]
    );
}

// -----------------------------------------------------------------------------
// 5. PRODUCT REVIEW HOOK
// -----------------------------------------------------------------------------
add_action( 'comment_post', 'cwdn_handle_new_review', 10, 3 );
function cwdn_handle_new_review( $comment_ID, $comment_approved, $commentdata ) {
    if ( get_post_type( $commentdata['comment_post_ID'] ) !== 'product' ) { return; }

    $options = cwdn_get_options();
    $data_prefs = $options['notifs']['new_review']['data'] ?? [];
    
    $post_id = $commentdata['comment_post_ID'];
    $rating  = get_comment_meta( $comment_ID, 'rating', true );
    $stars   = $rating ? str_repeat( '⭐', (int) $rating ) : 'No rating';
    
    $review_title = get_comment_meta( $comment_ID, 'title', true );

    $fields = [
        [ 'name' => 'Product', 'value' => get_the_title( $post_id ), 'inline' => true ],
        [ 'name' => 'Rating',  'value' => $stars, 'inline' => true ],
    ];

    if ( !empty($data_prefs['name']) ) {
        $fields[] = [ 'name' => 'Author', 'value' => $commentdata['comment_author'], 'inline' => true ];
    }
    if ( !empty($data_prefs['email']) ) {
        $fields[] = [ 'name' => 'Email', 'value' => $commentdata['comment_author_email'], 'inline' => true ];
    }
    if ( !empty($data_prefs['ip']) ) {
        $fields[] = [ 'name' => 'IP Address', 'value' => $commentdata['comment_author_IP'], 'inline' => true ];
    }

    $embed_data = [
        'description' => $commentdata['comment_content'],
        'fields'      => $fields
    ];

    if ( empty($review_title) || strtolower(trim($review_title)) === '(no title)' ) {
        if ( empty($data_prefs['hide_title']) ) {
            $embed_data['title'] = "(no title)";
        }
    } else {
        $embed_data['title'] = $review_title;
    }

    $store_name = get_bloginfo('name');
    $content_msg = "[{$store_name}] New {$rating}-star review";

    cwdn_dispatch_discord_webhook( 'new_review', $content_msg, $embed_data );
}
