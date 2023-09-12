<?php

$page = $_REQUEST['paginate'] ?: 1;
$per_page = $_REQUEST['per_page'] ?: 20;
$offset = (max(1, $page) - 1) * $per_page;

$wallets = get_wallet_users($per_page, $offset);
$total_users = _total_user_count();

?>
    <div class="wallet-page-wrap">
        <div class="wallet-banner">
            <h2 class="page-title">Wallets <?= ($offset + 1) . "-" . count($wallets) . " of $total_users" ?></h2>
        </div>
        <div class="wallet-search">
            <form id="search-form">
                <label class="search-label">
                    <input type="text" name="search" id="user-search" placeholder="Name or email">
                </label>
                <label class="submit-label">
                    <input type="submit">
                    <span class="icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 460 512"><path d="M220.6 130.3l-67.2 28.2V43.2L98.7 233.5l54.7-24.2v130.3l67.2-209.3zm-83.2-96.7l-1.3 4.7-15.2 52.9C80.6 106.7 52 145.8 52 191.5c0 52.3 34.3 95.9 83.4 105.5v53.6C57.5 340.1 0 272.4 0 191.6c0-80.5 59.8-147.2 137.4-158zm311.4 447.2c-11.2 11.2-23.1 12.3-28.6 10.5-5.4-1.8-27.1-19.9-60.4-44.4-33.3-24.6-33.6-35.7-43-56.7-9.4-20.9-30.4-42.6-57.5-52.4l-9.7-14.7c-24.7 16.9-53 26.9-81.3 28.7l2.1-6.6 15.9-49.5c46.5-11.9 80.9-54 80.9-104.2 0-54.5-38.4-102.1-96-107.1V32.3C254.4 37.4 320 106.8 320 191.6c0 33.6-11.2 64.7-29 90.4l14.6 9.6c9.8 27.1 31.5 48 52.4 57.4s32.2 9.7 56.8 43c24.6 33.2 42.7 54.9 44.5 60.3s.7 17.3-10.5 28.5zm-9.9-17.9c0-4.4-3.6-8-8-8s-8 3.6-8 8 3.6 8 8 8 8-3.6 8-8z"/></svg></span>
                </label>
            </form>
            <div class="search-results" style="display: none"></div>
        </div>
        <div class="wallet-list-wrap">
            <div class="wallet-table-head">
                <span class="th-item" data-col="user">User</span>
                <span class="th-item" data-col="email">Email</span>
                <span class="th-item" data-col="balance">Balance</span>
                <span class="th-item" data-col="orders">Orders</span>
            </div>
            <div class="wallet-table">
                <?php foreach ($wallets as $wallet) {
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
            <div class="wallet-navigation">
                <?php
                wallet_template("parts/pagination", [
                    'page' => (int)$page,
                    'page_num' => ceil($total_users / $per_page),
                ]);
                ?>
            </div>
        </div>
    </div>
<?php