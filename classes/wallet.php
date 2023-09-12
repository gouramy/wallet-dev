<?php

namespace Wallet;

class Wallet
{

    private \WP_User $user;
    private $balance;
    private $balance_key = '__wallet_balance';
    private $pending_balance_key = '__wallet_pending_balance';
    private $freeze_balance_key = '__wallet_freeze_balance';
    private $history_key = '__wallet_balance_history';

    public function __construct($user_id)
    {
        $this->user = get_user_by('ID', $user_id);
    }

    public function wallet_active()
    {
        $balance_initiated = get_user_meta($this->user->ID, $this->balance_key, true);
        return $balance_initiated === 0 || $balance_initiated > 0;
    }

    public function get_user()
    {
        return $this->user;
    }

    public function get_balance()
    {
        if (!$this->balance) {
            $this->balance = get_user_meta($this->user->ID, $this->balance_key, true) ?: 0;
        }
        return $this->balance;
    }
    public function add_balance($amount, $source, $name)
    {
        $this->get_balance();
        $this->balance += $amount;
        update_user_meta($this->user->ID, $this->balance_key, $this->balance);

        $this->add_history_record($name, $source, $amount);
    }

    public function use_balance($amount, $source, $name)
    {
        $this->get_balance();
        if ($this->balance < $amount) {
            return false; // or throw an exception
        }

        $this->balance -= $amount;
        update_user_meta($this->user->ID, $this->balance_key, $this->balance);

        $this->add_history_record($name, $source, -$amount);

        return true;
    }

    public function freeze_balance($amount)
    {
        $this->get_balance();
        if ($this->balance < $amount) {
            return false; // or throw an exception
        }

        update_user_meta($this->user->ID, $this->freeze_balance_key, $amount);

        return true;
    }

    public function releaze_balance()
    {
        delete_user_meta($this->user->ID, $this->freeze_balance_key);

        return true;
    }

    public function releaze_coupon()
    {
        $old_coupon_id = get_user_meta($this->user->ID, 'wallet_active_coupon', true);
        wp_delete_post($old_coupon_id);
        delete_user_meta($this->user->ID, 'wallet_active_coupon');
        return true;
    }

    public function redeem_coupon($coupon_id,$order_id)
    {
        $wallet_coupon_id = get_user_meta($this->user->ID, 'wallet_active_coupon',true);
        $frozen = get_user_meta($this->user->ID, $this->freeze_balance_key,true);
        if ($wallet_coupon_id != $coupon_id){
            return false;
        }
        $source = [
            'author' => 'customer',
            'type' => 'order',
            'title' => 'Used on checkout by customer'
        ];
        $this->use_balance($frozen,$source,"Used as discount for order #{$order_id}");
        update_post_meta($coupon_id, 'coupon_wallet_deducted', true);
        return true;
    }

    public function get_checkout_coupon($amount)
    {
        $this->releaze_coupon();
        $uid = strtoupper(wallet_str_rand());
        $coupon_code = "WALLET_{$uid}"; // Code
        $discount_type = 'fixed_cart'; // Type: fixed_cart, percent, fixed_product, percent_product

        $coupon = new \WC_Coupon();
        $coupon->set_code($coupon_code);
        $coupon->set_discount_type($discount_type);
        $coupon->set_amount($amount);
        $coupon->set_individual_use(true);
        $coupon->set_usage_limit(1);

        $coupon->save(); // Save the coupon

        $coupon_id = wc_get_coupon_id_by_code($coupon_code); // Get coupon ID by code

        update_post_meta($coupon_id, 'coupon_wallet_user', $this->get_user()->ID);
        update_user_meta($this->user->ID, 'wallet_active_coupon', $coupon_id);

        $this->freeze_balance($amount);

        return $coupon_code;
    }

    private function add_history_record($name, $source, $number)
    {
        $history_record = array(
            'name' => $name,
            'source' => $source,
            'number' => $number,
            'balance' => $this->get_balance(),
            'date' => time() // current timestamp
        );

        add_user_meta($this->user->ID, $this->history_key, $history_record, false);
    }

    public function get_history($limit = null, $offset = 0)
    {
        global $wpdb;
        $query = "SELECT umeta_id FROM {$wpdb->usermeta} WHERE user_id = %d AND meta_key = %s ORDER BY umeta_id DESC";
        if ($limit) {
            $query .= " LIMIT %d OFFSET %d";
        }
        $query = $wpdb->prepare($query, $this->user->ID, $this->history_key, $limit, $offset);
        $results = $wpdb->get_col($query);

        $formatted_results = [];
        foreach ($results as $meta_id) {
            $meta_value = get_metadata_by_mid('user', $meta_id)->meta_value;

            $formatted_results[] = [
                'ID' => $meta_id,
                'name' => $meta_value['name'],
                'source' => $meta_value['source'],
                'number' => $meta_value['number'],
                'balance' => $meta_value['balance'],
                'date' => $meta_value['date'],
                'meta' => $meta_value
            ];
        }

        return $formatted_results;
    }

    public function get_history_record($record_id)
    {
        $record = get_metadata_by_mid('user', $record_id)->meta_value;
        return $record;
    }

    public function remove_history_record($record_id)
    {
        delete_metadata_by_mid('user', $record_id);
    }

    public function get_edit_url()
    {
        return admin_url('admin.php') . "?page=wallet&user_id={$this->user->ID}";
    }



    public function add_pending_balance($amount, $data)
    {
        $pending_balance = array(
            'data' => $data,
            'amount' => $amount,
            'date' => time() // current timestamp
        );

        $meta_id = add_user_meta($this->user->ID, $this->pending_balance_key, $pending_balance, false);
        return $meta_id;
    }

    public function get_pending_balance_items()
    {
        global $wpdb;
        $query = "SELECT umeta_id FROM {$wpdb->usermeta} WHERE user_id = %d AND meta_key = %s ORDER BY umeta_id DESC";
        $query = $wpdb->prepare($query, $this->user->ID, $this->pending_balance_key);
        $results = $wpdb->get_col($query);

        $formatted_results = [];
        foreach ($results as $meta_id) {
            $meta_value = get_metadata_by_mid('user', $meta_id)->meta_value;

            $formatted_results[] = [
                'ID' => $meta_id,
                'data' => $meta_value['data'],
                'amount' => $meta_value['amount'],
                'balance' => $meta_value['balance'],
                'date' => $meta_value['date'],
                'meta' => $meta_value
            ];
        }

        return $formatted_results;
    }

    public function get_pending_balance()
    {
        $pb = 0;
        $items = $this->get_pending_balance_items();

        foreach ($items as $item){
            $pb += $item['amount'];
        }

        return $pb;
    }

    public function apply_pending_balance($meta_id)
    {
        $metadata = get_metadata_by_mid('user', $meta_id);

        if (!$metadata) return false;

        $pending_balance = $metadata->meta_value;

        $name = $pending_balance['data']['name']?:"Pending balance activated";

        $source = [
            'author' => $metadata->user_id,
            'type' => 'referral',
            'title' => 'From pending balance',
            'referral_order' => $pending_balance['data']['order_id']
        ];

        $this->add_balance($pending_balance['amount'],$source,$name);
        delete_metadata_by_mid('user', $meta_id);

        return $pending_balance['amount'];
    }

    public function deny_pending_balance($meta_id)
    {
        delete_metadata_by_mid('user', $meta_id);
    }

    public function get_referral_coupon($amount = false)
    {
        $coupon_code = get_user_meta($this->user->ID, 'wallet_referral_coupon_code', true);
        $coupon_id = wc_get_coupon_id_by_code($coupon_code); // Get coupon ID by code
        $coupon_post = get_post($coupon_id);

        if ($coupon_code && $coupon_id && $coupon_post) return $coupon_code;

        $amount = $amount?:WLT()->customers::$referral_amount;

        $uid = strtoupper(wallet_str_rand());
        $coupon_code = "REF_{$uid}"; // Code
        $discount_type = 'fixed_cart'; // Type: fixed_cart, percent, fixed_product, percent_product

        $coupon = new \WC_Coupon();
        $coupon->set_code($coupon_code);
        $coupon->set_discount_type($discount_type);
        $coupon->set_amount($amount);
        $coupon->set_individual_use(true);

        $coupon->save(); // Save the coupon

        $coupon_id = wc_get_coupon_id_by_code($coupon_code); // Get coupon ID by code

        update_post_meta($coupon_id, 'coupon_wallet_user', $this->get_user()->ID);
        update_user_meta($this->user->ID, 'wallet_referral_coupon', $coupon_id);
        update_user_meta($this->user->ID, 'wallet_referral_coupon_code', $coupon_code);

        return $coupon_code;
    }

    public function get_referral_url(){
        $coupon_code = $this->get_referral_coupon();
        return site_url().'/refer-earn/?referral='.$coupon_code;
    }
}
