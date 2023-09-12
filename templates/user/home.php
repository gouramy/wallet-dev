<?php
$wallet = user_wallet($_REQUEST['user_id']);
$page = $_REQUEST['paginate'] ?: 1;
$per_page = $_REQUEST['per_page'] ?: 10;
$offset = (max(1, $page) - 1) * $per_page;
$render = WLT()->render;

$history_records_all = $wallet->get_history();
$history_records = $wallet->get_history($per_page, $offset);
?>

<div class="wallet-page-wrap">
    <div class="wallet-banner">
        <h2 class="page-title">Wallet of <strong><?= $wallet->get_user()->display_name ?></strong></h2>
    </div>
    <div class="wallet-user-summary">
        <div class="widget-active-balance">
            <span class="title">Available balance</span>
            <?= wc_price($wallet->get_balance()) ?>
        </div>
        <div class="widget-active-balance">
            <span class="title">Pending balance</span>
            <small class="pending-balance"><?= wc_price($wallet->get_pending_balance()) ?></small>
        </div>
        <div class="widget-active-balance">
            <span class="title">Referral code</span>
            <span class="wallet-referral-code"><?= $wallet->get_referral_coupon() ?></span>
        </div>
        <div class="wallet-buttons">
            <?php $render->add_balance_button($wallet->get_user()->ID) ?>
            <?php $render->use_balance_button($wallet->get_user()->ID) ?>
        </div>
    </div>
    <h2 class="page-title">Balance History</h2>
    <div class="wallet-list-wrap">
        <div class="wallet-table-head">
            <span class="th-item" data-col="user">Record</span>
            <span class="th-item" data-col="source">Source</span>
            <span class="th-item" data-col="balance">Balance</span>
            <span class="th-item" data-col="date">Date</span>
        </div>
        <div class="wallet-table">
            <?php foreach ($history_records as $history_record) {
                $deduct = $history_record['number'] < 0;
                $flat_num = abs($history_record['number']);
                ?>
                <div class="wallet-item-row wallet-active <?= $deduct ? "record-balance-use" : "record-balance-add" ?>"
                     data-record-id="<?= $history_record['ID'] ?>">
                        <span class="td-item" data-col="user">
                            <span class="record-name"><?= $history_record['name'] ?></span>
                        </span>
                    <span class="td-item" data-col="source"><?= $history_record['source']['title'] ?? '' ?></span>
                    <span class="td-item" data-col="balance">
                        <span class="balance-change"><?=$deduct?"-":""?>$<?= $flat_num ?? 0 ?></span>
                        <span class="balance-final">$<?= $history_record['balance'] ?? '' ?></span>
                    </span>
                    <span class="td-item"
                          data-col="date"><?= wp_date('d M Y, H:i', $history_record['date']) ?></span>
                </div>
                <?php
            } ?>
        </div>
        <div class="wallet-navigation">
            <?php
            wallet_template("parts/pagination", [
                'page' => (int)$page,
                'page_num' => ceil(count($history_records_all) / $per_page),
                'base_url' => $wallet->get_edit_url()
            ]);
            ?>
        </div>
    </div>
    <?php if ($wallet->get_pending_balance_items()){ ?>
        <h2 class="page-title">Pending Balance</h2>
        <div class="wallet-list-wrap">
            <div class="wallet-table-head">
                <span class="th-item" data-col="user">Record</span>
                <span class="th-item" data-col="source">Source</span>
                <span class="th-item" data-col="balance">Balance</span>
                <span class="th-item" data-col="date">Date</span>
            </div>
            <div class="wallet-table">
                <?php foreach ($wallet->get_pending_balance_items() as $pending) {

                    ?>
                    <div class="wallet-item-row wallet-active"
                         data-record-id="<?= $pending['ID'] ?>">
                        <span class="td-item" data-col="user">
                            <span class="record-name"><?= $pending['data']['name']?:"Pending balance" ?></span>
                        </span>
                        <span class="td-item" data-col="source"><?= $pending['data']['order_id'] ?"Order #{$pending['data']['order_id']}": '' ?></span>
                        <span class="td-item" data-col="balance"><span class="balance-change">$<?= $pending['amount'] ?? 0 ?></span></span>
                        <span class="td-item"
                              data-col="date"><?= wp_date('d M Y, H:i', $pending['date']) ?></span>
                    </div>
                    <?php
                } ?>
            </div>
        </div>
    <?php }?>
</div>

<div class="wallet-popup-wrap">
    <?php
    $render->popup('add-balance', ['user_id' => $wallet->get_user()->ID]);
    $render->popup('use-balance', ['user_id' => $wallet->get_user()->ID]);
    ?>
</div>
