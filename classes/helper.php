<?php

namespace Wallet;

class Helper
{

    public function __construct()
    {
        add_action('wp_ajax_add_balance', array($this, 'add_balance'));
        add_action('wp_ajax_use_balance', array($this, 'use_balance'));
        add_action('wp_ajax_wallet_search_user', array($this, 'search_user'));
    }

    public function add_balance()
    {
        $user_id = $_POST['user_id'];
        $amount = $_POST['amount'] ?? 0;
        $name = $_POST['reason'] ?? "";

        $wallet = user_wallet($user_id);

        if (!$wallet) {
            wp_send_json(['ok' => false, 'message' => "No wallet for user."]);
            wp_die();
        }
        if (!$amount || !$name) {
            wp_send_json(['ok' => false, 'message' => "Need both amount and reason."]);
            wp_die();
        }

        $source = [
            'author' => get_current_user_id(),
            'type' => 'manual',
            'title' => 'Added by admin'
        ];
        $wallet->add_balance($amount, $source, $name);

        wp_send_json(['ok' => true]);
        wp_die();
    }

    public function use_balance()
    {
        $user_id = $_POST['user_id'];
        $amount = sanitize_key($_POST['amount']) ?? 0;
        $name = $_POST['reason'] ?? "";

        $wallet = user_wallet($user_id);

        if (!$wallet) {
            wp_send_json(['ok' => false, 'message' => "No wallet for user."]);
            wp_die();
        }
        if (!$amount || !$name) {
            wp_send_json(['ok' => false, 'message' => "Need both amount and reason."]);
            wp_die();
        }

        $balance = $wallet->get_balance();
        if ($balance < $amount) {
            wp_send_json(['ok' => false, 'message' => "Can not use more than \${$balance}."]);
            wp_die();
        }

        $source = [
            'author' => 'admin',
            'type' => 'manual',
            'title' => 'Added by admin'
        ];

        $used = $wallet->use_balance($amount, $source, $name);

        wp_send_json(['ok' => true]);
        wp_die();
    }

    public function search_user()
    {
        $search_phrase = $_POST['search'] ?: "";

        if (!$search_phrase || strlen($search_phrase) < 3) {
            wp_send_json(['ok' => false, 'results' => '<h2>Input some search phrase.</h2>']);
            wp_die();
        }

        $search_phrase = trim($search_phrase);

        global $wpdb;

        // Ensure the search phrase is sanitized to prevent SQL injection
        $search_phrase = '%' . $wpdb->esc_like($search_phrase) . '%';

        // Query to search users by first name, last name, or email
        $query = $wpdb->prepare("
        SELECT ID FROM {$wpdb->users}
        WHERE ID IN (
            SELECT user_id FROM {$wpdb->usermeta}
            WHERE (meta_key = 'first_name' AND meta_value LIKE %s)
            OR (meta_key = 'last_name' AND meta_value LIKE %s)
        )
        OR user_email LIKE %s
        OR display_name LIKE %s
        LIMIT 10
    ", $search_phrase, $search_phrase, $search_phrase, $search_phrase);

        // Execute the query
        $user_ids = $wpdb->get_col($query);

        if (!$user_ids) {
            wp_send_json(['ok' => false, 'results' => '<h2>No users found</h2>']);
            wp_die();
        }


        ob_start();
        ?>
        <h2>Search results:</h2>
        <div class="wallet-list-wrap">
            <div class="wallet-table-head">
                <span class="th-item" data-col="user">User</span>
                <span class="th-item" data-col="email">Email</span>
                <span class="th-item" data-col="balance">Balance</span>
                <span class="th-item" data-col="orders">Orders</span>
            </div>
            <div class="wallet-table">
                <?php foreach ($user_ids as $user_id) {
                    $wallet = new \Wallet\Wallet($user_id);
                    ?>
                    <div class="wallet-item-row <?= $wallet->wallet_active() ? "wallet-active" : "" ?>"
                         data-user-id="<?= $wallet->get_user()->ID ?>">
                        <span class="td-item" data-col="user">
                            <a href="<?= $wallet->get_edit_url() ?>"><?= $wallet->get_user()->display_name ?></a>
                            <a class="edit-user" href="/wp-admin/user-edit.php?user_id=<?= $wallet->get_user()->ID ?>">edit wp user</a>
                        </span>
                        <span class="td-item" data-col="email"><a
                                    href="mailto:<?= $wallet->get_user()->user_email ?>"><?= $wallet->get_user()->user_email ?></a></span>
                        <span class="td-item" data-col="balance">
                            <span class="available-balance"><?= wc_price($wallet->get_balance()) ?></span>
                            <small class="pending-balance"><?= wc_price($wallet->get_pending_balance()) ?></small>
                        </span>
                        <span class="td-item"
                              data-col="orders"><?= get_user_order_count($wallet->get_user()->ID) ?></span>
                    </div>
                    <?php
                } ?>
            </div>
        </div>
        <?php
        $results_html = ob_get_clean();

        wp_send_json(['ok' => true, 'results' => $results_html]);
        wp_die();
    }

}