<?php

namespace Wallet;

class Dashboard
{
    public function __construct()
    {
        add_action('admin_enqueue_scripts', array($this, 'assets'));
        add_action('admin_menu', array($this, 'register_pages'));

        add_action('pre_get_posts', array($this, 'filter_coupons'));
        add_filter('query_vars', array($this, 'title_not_starts_with_query_var'));
        add_filter('posts_where', array($this, 'modify_posts_where'), 10, 2);

        add_filter('views_edit-shop_coupon', array($this, 'add_custom_coupon_tabs'));
        add_action('pre_get_posts', array($this, 'filter_custom_coupon_tab_query'));
        add_filter('posts_where', array($this, 'modify_title__start_posts_where'), 10, 2);
    }

    public function filter_coupons($query)
    {
        global $pagenow;

        if (is_admin() && $pagenow == 'edit.php' && isset($_GET['post_type']) && $_GET['post_type'] == 'shop_coupon' && !isset($_GET['coupon_filter'])) {
            $query->set('title__not_start', array('ref_', 'wallet_', 'gift_'));
        }
    }

    public function title_not_starts_with_query_var($qvars)
    {
        $qvars[] = 'title__not_start';
        return $qvars;
    }

    public function modify_posts_where($where, $query)
    {
        global $wpdb;

        if ($post_title_not_starts_with = $query->get('title__not_start') && !$query->get('title__start')) {
            foreach ($post_title_not_starts_with as $post_title_not_like) {
                $where .= " AND " . $wpdb->posts . ".post_title NOT LIKE '" . esc_sql($wpdb->esc_like($post_title_not_like)) . "%'";
            }
        }

        return $where;
    }

    public function add_custom_coupon_tabs($views)
    {
        $ref_count = count(get_posts(array(
            'post_type' => 'shop_coupon',
            'title__start' => 'ref_',
            'numberposts' => -1,
            'suppress_filters' => false,
        )));

        $wallet_count = count(get_posts(array(
            'post_type' => 'shop_coupon',
            'title__start' => 'wallet_',
            'numberposts' => -1,
            'suppress_filters' => false,
        )));

        $gift_count = count(get_posts(array(
            'post_type' => 'shop_coupon',
            'title__start' => 'gift_',
            'numberposts' => -1,
            'suppress_filters' => false,
        )));

        if (isset($views['all'])) {
            $all_count = preg_replace("/\D/", '', strip_tags($views['all']));
            $all_count = intval($all_count) - $ref_count - $wallet_count - $gift_count;
            $views['all'] = preg_replace('/\(.+\)/U', '(' . $all_count . ')', $views['all']);
        }

        $class_ref = (isset($_GET['coupon_filter']) && $_GET['coupon_filter'] == 'ref') ? 'current' : '';
        $all_url = admin_url('edit.php?post_type=shop_coupon');
        $ref_url = add_query_arg(array('coupon_filter' => 'ref'), $all_url);

        $views['ref'] = sprintf(__('<a href="%s" class="%s">Referral <span class="count">(%d)</span></a>', 'woocommerce'), esc_url($ref_url), $class_ref, $ref_count);

        $class_wallet = (isset($_GET['coupon_filter']) && $_GET['coupon_filter'] == 'wallet') ? 'current' : '';
        $wallet_url = add_query_arg(array('coupon_filter' => 'wallet'), $all_url);

        $views['wallet'] = sprintf(__('<a href="%s" class="%s">Wallet Usage<span class="count">(%d)</span></a>', 'woocommerce'), esc_url($wallet_url), $class_wallet, $wallet_count);

        $class_gift = (isset($_GET['coupon_filter']) && $_GET['coupon_filter'] == 'gift') ? 'current' : '';
        $gift_url = add_query_arg(array('coupon_filter' => 'gift'), $all_url);

        $views['gift'] = sprintf(__('<a href="%s" class="%s">Gift Cards<span class="count">(%d)</span></a>', 'woocommerce'), esc_url($gift_url), $class_gift, $gift_count);

        return $views;
    }


    /** @var \WP_Query $query */
    public function filter_custom_coupon_tab_query($query)
    {
        global $pagenow;

        if ($pagenow == 'edit.php' && isset($_GET['post_type']) && $_GET['post_type'] == 'shop_coupon' && isset($_GET['coupon_filter'])) {
            if ($query->get('title__start')) {
                return $query;
            }

            if ($_GET['coupon_filter'] == 'ref') {
                $query->set('title__start', 'ref_');
            } elseif ($_GET['coupon_filter'] == 'wallet') {
                $query->set('title__start', 'wallet_');
            } elseif ($_GET['coupon_filter'] == 'gift') {
                $query->set('title__start', 'gift_');
            }
        }
    }

    public function modify_title__start_posts_where($where, $query)
    {
        global $wpdb;

        if ($title__start = $query->get('title__start')) {
            $where .= " AND " . $wpdb->posts . ".post_title LIKE '" . esc_sql($wpdb->esc_like($title__start)) . "%'";
        }

        return $where;
    }

    public function assets()
    {
        wp_enqueue_style('wallet-admin-icon-style', Wallet_URL . "assets/css/icon.css", '', Wallet_VER);

        if ($_REQUEST['page'] === 'wallet') {
            wp_enqueue_style('wallet-admin-style', Wallet_URL . "assets/css/admin.css", '', Wallet_VER);
            wp_enqueue_script('wallet-actions', Wallet_URL . "assets/js/actions.js", '', Wallet_VER);
            wp_enqueue_script('wallet-admin-script', Wallet_URL . "assets/js/admin.js", '', Wallet_VER);
        }
    }

    public function register_pages()
    {
        if (!has_wallet_access()) return;

        add_menu_page(
            "Wallet",
            "Wallet",
            'manage_options',
            'wallet',
            array($this, 'dashboard'),
            Wallet_URL . 'assets/icons/wallet.svg?i',
            2);

    }

    public function dashboard()
    {
        if ($_REQUEST['user_id']) {
            wallet_template('user/home');
            return;
        }
        wallet_template('dashboard/home');
    }
}