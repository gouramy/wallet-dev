<?php

/**
 * @return \Wallet\Core
 */
function WLT()
{
    return \Wallet\Core::get_instance();
}

function wallet_template($template, $args = array(), $get = false)
{
    if ($get) ob_start();
    $file_path = Wallet_DIR . "/templates/{$template}.php";
    if (!is_array($args)) $args = array();
    if (file_exists($file_path)) {
        extract($args);
        include $file_path;
    }
    if ($get) return ob_get_clean();
    return '';
}

function wallet_str_rand($length = 10)
{
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

/**
 * @param WP_User $user
 * @return false
 */
function has_wallet_access($user = false)
{
    if (!$user) {
        $user = wp_get_current_user();
    }

    return user_can($user, 'administrator');
}

function user_wallet($user_id = false)
{
    if (!$user_id){
        $user_id = get_current_user_id();
    }

    $user = get_user_by('ID',$user_id);

    if(is_wp_error($user) || !$user){
        return false;
    }

    return new \Wallet\Wallet($user_id);
}

/**
 * @param $limit
 * @param $offset
 * @return \Wallet\Wallet[]
 */
function get_wallet_users($limit = 10, $offset = 0) {
    global $wpdb;

    $wallet_users_query = "SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = '__wallet_balance'";
    $wallet_users_ids = $wpdb->get_col($wallet_users_query);

    $wallet_users_count = count($wallet_users_ids);
    $remaining = max(0, $limit - $wallet_users_count);
    $wallet_users_ids = array_slice($wallet_users_ids, $offset, $limit);

    if($remaining > 0 && (($offset > 0 && $wallet_users_count <= $offset) || $offset == 0)){
        $global_offset = 0;
        if ($offset > 0) {
            $global_offset = max(0, $offset - $wallet_users_count);
        }
        $global_not__in = "";
        if ($wallet_users_ids) {
            $global_not__in = " AND ID NOT IN (" . implode(',', $wallet_users_ids) . ") ";
        }
        $global_users_query = "SELECT ID FROM {$wpdb->users} WHERE 1 = 1 {$global_not__in} LIMIT %d OFFSET %d";
        $global_users_query = $wpdb->prepare($global_users_query, $remaining, $global_offset);
        $global_users_ids = $wpdb->get_col($global_users_query);

        $users_ids = array_merge($wallet_users_ids, $global_users_ids);
    } else {
        $users_ids = $wallet_users_ids;
    }

    $wallets = array();
    foreach ($users_ids as $user_id) {
        $wallets[] = new \Wallet\Wallet($user_id);
    }

    return $wallets;
}

function _total_user_count() {
    global $wpdb;

    $number = $wpdb->get_var("select count(ID) from {$wpdb->users} where 1=1;");
    return $number;
}

function get_user_order_count($user_id) {
    global $wpdb;

    $count = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*)
            FROM $wpdb->postmeta 
            WHERE meta_key = '_customer_user' 
            AND meta_value = %d",
            $user_id
        )
    );

    return $count;
}
