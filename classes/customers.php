<?php

namespace Wallet;

/** Class to represent Wallet features to customers. Adds tabs to Woocommerce, controls the way to use referral and wallet coupons */
class Customers
{

    public static $referral_amount = 20;

    public function __construct()
    {
        add_action('woocommerce_checkout_order_review', array($this, 'checkout_field'), 10);
        add_filter('woocommerce_update_order_review_fragments', array($this, 'checkout_field_refresh'));

        add_action('wp_ajax_checkout_apply_wallet', array($this, 'checkout_apply_wallet'));
        add_action('wp_ajax_nopriv_checkout_apply_wallet', array($this, 'checkout_apply_wallet'));

        add_action('woocommerce_order_status_changed', array($this, 'coupon_redeemed'), 10, 3);
        add_action('woocommerce_removed_coupon', array($this, 'coupon_removed'));

        add_action('init', array($this, 'wallet_endpoints'));
        add_filter('query_vars', array($this, 'wallet_query_wars'), 0);
        add_filter('woocommerce_account_menu_items', array($this, 'wallet_tab'), 10, 1);
        add_action('woocommerce_account_wallet_endpoint', array($this, 'wallet_endpoint_render'));
        add_action('woocommerce_account_referral_endpoint', array($this, 'referral_endpoint_render'));
        add_action('woocommerce_account_support_endpoint', array($this, 'support_endpoint_render'));

        add_action('woocommerce_order_status_changed', array($this, 'referral_coupon_redeemed'), 10, 3);
        add_action('woocommerce_applied_coupon', array($this, 'referral_coupon_validation'), 10, 1);

        add_action('maybe_apply_wallet_referral_code', array($this, 'maybe_apply_wallet_referral_code'));
        add_action('maybe_process_referral_code', array($this, 'maybe_process_referral_code'));

        add_action('wp_ajax_referral_send_email', array($this, 'referral_send_email'));
        add_action('wp_ajax_nopriv_referral_send_email', array($this, 'referral_send_email'));

        add_action('wp_footer', array($this, 'maybe_process_referral_code'), 10000);

        add_filter('woocommerce_registration_redirect', array($this, 'registration_redirect'));
    }

    public function wallet_endpoints()
    {
        add_rewrite_endpoint('referral', EP_ROOT | EP_PAGES);
        add_rewrite_endpoint('wallet', EP_ROOT | EP_PAGES);
        add_rewrite_endpoint('support', EP_ROOT | EP_PAGES);
    }

    public function wallet_query_wars($vars)
    {
        $vars[] = 'referral';
        $vars[] = 'wallet';
        $vars[] = 'support';
        return $vars;
    }

    public function wallet_tab($items)
    {
        array_insert_after('orders', $items, 'referral', __('Referral', 'woocommerce'));
        array_insert_after('orders', $items, 'wallet', __('Wallet', 'woocommerce'));
        array_insert_after('orders', $items, 'support', __('Web Support', 'woocommerce'));

        return $items;
    }

    public function wallet_endpoint_render()
    {
        $wallet = user_wallet();

        $history_records = $wallet->get_history(10, 0);
        ?>
        <style>div#wpcontent {
                background: #242731;
                color: #ffffff;
                padding-right: 20px;
            }

            h2.page-title {
                color: white;
            }

            .wallet-list-wrap {
                display: flex;
                flex-direction: column;
                background: #242731;
                color: #ffffff;
                padding: 25px 0 25px;
                border-radius: 24px;
                box-shadow: 0 0 20px #0000007a;
            }

            .wallet-list-wrap .wallet-table-head,
            .wallet-list-wrap .wallet-table .wallet-item-row {
                display: grid;
                grid-template-columns: 1fr clamp(100px, 10vw, 160px) clamp(140px, 12vw, 180px);
                font-size: 16px;
                padding: 12px 30px;
                align-items: center;
                position: relative;
            }

            .wallet-list-wrap .wallet-table .wallet-item-row:nth-child(2n+1) {
                background: #00000012;
            }

            .wallet-list-wrap .wallet-table-head {
                font-weight: 600;
            }

            .wallet-list-wrap .td-item[data-col=user] {
                display: flex;
                flex-direction: column;
                gap: 7px;
            }

            .wallet-list-wrap .td-item[data-col=user] a:first-child {
                color: white;
                text-decoration: none;
                font-size: 18px;
                line-height: 1.2;
                font-weight: 600;
            }

            .wallet-list-wrap .td-item[data-col=user] a:first-child:hover {
                text-decoration: underline;
            }

            .wallet-list-wrap :is(.td-item, .th-item):is([data-col=balance], [data-col=orders]) {
                text-align: center;
                font-size: 18px;
                font-weight: 600;
            }

            .wallet-list-wrap .wallet-table .wallet-item-row:not(.wallet-active) .td-item[data-col=balance] {
                opacity: 0.2;
            }

            .wallet-list-wrap .td-item[data-col=balance] {
                display: flex;
                flex-direction: column;
            }

            .wallet-list-wrap .td-item[data-col=balance] .balance-final {
                font-size: 14px;
                line-height: 1;
            }

            .wallet-list-wrap .wallet-item-row:after {
                display: flex;
                position: absolute;
                inset: 0;
                content: "";
                pointer-events: none;
                opacity: 0.04;
            }

            .wallet-list-wrap .wallet-item-row.record-balance-use:after {
                background: red;
            }

            .wallet-list-wrap .wallet-item-row.record-balance-use .td-item[data-col=balance] .balance-change {
                color: #eb4848;
            }

            .wallet-list-wrap .wallet-item-row.record-balance-add:after {
                background: green;
            }

            .wallet-list-wrap .wallet-item-row.record-balance-add .td-item[data-col=balance] .balance-change {
                color: #5ef55e;
            }

            .wallet-list-wrap a.edit-user {
                opacity: 0;
            }

            .wallet-list-wrap .wallet-item-row:hover a.edit-user {
                opacity: 1;
            }

            .wallet-navigation {
                padding: 25px 30px 0;
            }

            nav.wallet-pagination {
                display: flex;
                gap: 4px;
            }

            nav.wallet-pagination .pagination-link-wrap {
                display: flex;
                width: 2em;
                aspect-ratio: 1;
                background: #3d3f49;
                align-items: center;
                justify-content: center;
                border-radius: 4px;
                transition: 0.3s;
                font-family: monospace;
            }

            nav.wallet-pagination .pagination-link-wrap:where(.active,:hover,:focus-within) {
                background: #47556b;
            }

            nav.wallet-pagination .pagination-link-wrap :where(a,span) {
                color: white;
                text-decoration: none;
                opacity: 0.8;
                user-select: none;
                height: 100%;
                width: 100%;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            nav.wallet-pagination .pagination-link-wrap :where(a,span):focus {
                box-shadow: none;
            }

            .wallet-popup-wrap {
                display: flex;
                position: fixed;
                inset: 0;
                z-index: -1;
                backdrop-filter: blur(4px);
                background: #ffffff26;
                align-items: center;
                justify-content: center;
                transition: opacity 250ms ease;
            }

            .wallet-popup-wrap.open {
                opacity: 1;
                pointer-events: all;
                z-index: 99999;
            }

            .wallet-popup {
                display: none;
                width: 500px;
                flex-direction: column;
                gap: 20px;
                padding: 40px 20px;
                background: #242731;
                box-shadow: 0 0 20px #00000085;
                border-radius: 20px;
                position: relative;
                max-width: 100vw;
            }

            .wallet-popup.popup-open {
                display: flex;
            }

            .wallet-popup h2 {
                color: white;
                font-size: 24px;
                margin-top: 0;
            }

            .wallet-popup .wallet-popup-close {
                position: absolute;
                right: 20px;
                font-size: 24px;
                cursor: pointer;
            }

            .wallet-form {
                display: flex;
                flex-direction: column;
                gap: 20px;
            }

            .wallet-form label {
                display: flex;
                flex-direction: column;
                gap: 5px;
            }

            .wallet-form label .label {
                font-size: 16px;
                line-height: 1.2;
            }

            .wallet-form label input {
                background: transparent;
                color: white;
                padding: 10px 15px;
                border-radius: 15px;
            }

            .wallet-btn {
                border: 1px solid white;
                border-radius: 25px;
                background: transparent;
                color: white;
                padding: 10px 15px;
                font-size: 20px;
                cursor: pointer;
                display: inline-flex;
                align-items: center;
                text-align: center;
                text-align-last: center;
                justify-content: center;
                transition: all 250ms ease;
            }

            .wallet-btn > button {
                color: inherit;
                background: transparent;
                border: none;
                width: 100%;
                padding: 0;
                text-align: center;
                height: auto;
                cursor: pointer;
                line-height: 1;
            }

            .wallet-btn:hover {
                color: #242731;
                background: white;
            }

            .widget-active-balance {
                font-size: 20px;
                line-height: 1.4;
                margin-bottom: 20px;
                display: inline-block;
                margin-right: 10px;
            }

            .widget-active-balance .woocommerce-Price-amount.amount {
                font-size: 24px;
            }

            .pending-balance {
                opacity: 0.5;
            }

            .pending-balance:before {
                content: "";
                background: url(<?=Wallet_URL?>/assets/icons/pending.svg);
                width: 0.7em;
                aspect-ratio: 1;
                display: inline-flex;
                background-repeat: no-repeat;
                background-position: center;
                margin-right: 2px;
            }

            .wallet-referral-code {
                color: gold;
                background: black;
                padding: 4px 12px 6px;
                line-height: 1;
                border: 2px dashed;
                margin: 2px;
            }

            .wallet-buttons {
                display: flex;
                gap: 20px;
            }

            .wallet-search form#search-form {
                position: relative;
                display: flex;
                width: 300px;
                margin: 20px 0;
                padding: 0;
                align-items: center;
                max-width: 100%;
                transition: all 250ms ease;
            }

            .wallet-search label.search-label {
                width: 100%;
                height: 100%;
            }

            .wallet-search label.search-label input {
                background: transparent;
                width: 100%;
                border-radius: 30px;
                border: 1px solid white;
                padding: 5px 15px;
                height: 40px;
                line-height: 1;
                display: flex;
                font-size: 18px;
                color: white;
            }

            .wallet-search label.submit-label {
                display: flex;
                position: absolute;
                right: 10px;
                width: 24px;
                aspect-ratio: 1;
                padding-bottom: 4px;
            }

            .wallet-search label.submit-label input {
                opacity: 0;
                position: absolute;
                width: 0;
                height: 0;
            }

            .wallet-search label.submit-label .icon {
                width: 100%;
                height: 100%;
            }

            .wallet-search label.submit-label .icon svg {
                fill: white;
            }

            .wallet-search:focus-within form#search-form {
                width: 100%;
            }

            .wallet-search .search-results {
                margin-bottom: 80px;
            }

            .wallet-search .search-results h2 {
                color: white;
                font-size: 24px;
            }

        </style>
        <div class="wallet-page-wrap">
            <div class="wallet-banner">
                <h2 class="page-title">Your wallet</h2>
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
            </div>
            <h2 class="page-title">Balance History</h2>
            <div class="wallet-list-wrap">
                <div class="wallet-table-head">
                    <span class="th-item" data-col="user">Record</span>
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
                            <span class="td-item" data-col="balance">
                                <span class="balance-change"><?= $deduct ? "-" : "" ?>$<?= $flat_num ?? 0 ?></span>
                                <span class="balance-final">$<?= $history_record['balance'] ?? '' ?></span>
                            </span>
                            <span class="td-item"
                                  data-col="date"><?= wp_date('d M Y, H:i', $history_record['date']) ?></span>
                        </div>
                        <?php
                    } ?>
                </div>
            </div>
        </div>
        <?php
    }

    public function support_endpoint_render()
    {
        ?>
        <div class="wallet-page-wrap">
            <div class="wallet-banner">
                <h2 class="page-title">Web Support</h2>
                <?php echo do_shortcode('[gravityform id="26" ajax="true" title="false"]'); ?>
            </div>
        </div>
        <?php
    }

    public function referral_endpoint_render()
    {
        $wallet = user_wallet();

        ?>
        <style>
            .copy-wrap {
                display: inline-flex;
                line-height: 1;
                border: 1px dashed;
                margin: 2px;
                align-items: center;
                color: #ffffffcc;
                font-size: 10px;
                padding: 4px;
                gap: 7px;
            }

            .copy-trigger {
                display: flex;
                height: 12px;
                width: 12px;
                cursor: pointer;
            }

            .copy-trigger svg path {
                fill: #ffffffcc;
            }

            .copied, .copied .copy-trigger svg path {
                animation: color-change 1s forwards;
            }

            .referral-option {
                margin-bottom: 20px;
                counter-increment: option;
                display: flex;
                gap: 10px;
                position: relative;
            }

            form.wallet-invite-referral {
                display: flex;
                flex-wrap: wrap;
            }

            form.wallet-invite-referral input[type="email"] {
                border-right: none;
                width: 100%;
                max-width: 500px;
            }

            h2.page-title:before {
                content: '';
                width: 40px;
                aspect-ratio: 1;
                flex-shrink: 0;
                display: inline-block;
                background: #2bd842;
                border-radius: 100px;
                background-image: url(https://www.thebusinesstoolkit.com/wp-content/themes/thebusinesstoolkit-child/inc/wallet/assets/icons/refer-person.svg?i);
                background-repeat: no-repeat;
                background-size: 60%;
                background-position: center;
            }

            h2.page-title {
                display: flex;
                align-items: center;
                gap: 10px;
                font-size: 24px;
            }

            .referral-wrap.wallet-page-wrap {
                background: #222126;
                padding: 35px;
                border-radius: 5px;
            }

            .referral-option:before {
                content: counter(option);
                display: flex;
                align-items: center;
                justify-content: center;
                height: 60px;
                width: 60px;
                flex-shrink: 0;
                background: #2bd842;
                border-radius: 8px;
                color: black;
                font-size: 36px;
                font-weight: 900;
                line-height: 1;
            }

            span.referral-options {
                display: flex;
                font-weight: 600;
                margin-bottom: 35px;
                border-bottom: 1px solid #ffffff29;
                margin-left: -35px;
                padding-left: 35px;
                margin-right: -35px;
                padding-bottom: 10px;
                font-size: 18px;
            }

            h3.opt-title {
                font-size: 16px;
                color: white;
            }

            a.referral-action-button {
                position: absolute;
                right: 0;
                top: 0;
                color: white;
                border: 1px solid;
                line-height: 1;
                padding: 4px;
                border-radius: 4px;
                background: #ffffff1f;
            }

            .description {
                font-size: 14px;
                font-weight: 100;
                color: #ffffffcc;
            }

            @keyframes color-change {
                from {
                    fill: #34bb46;
                    color: #34bb46;
                }
                to {
                    fill: #ffffffcc;
                    color: #ffffffcc;
                }
            }

            @media (max-width: 425px) {
                .referral-option:before {
                    position: absolute;
                }

                .description {
                    max-width: 100%;
                }

                .widget-active-balance, form.wallet-invite-referral {
                    margin-top: 64px;
                }

                h3.opt-title {
                    position: absolute;
                    right: 50px;
                    left: 70px;
                }
            }
        </style>
        <div class="wallet-page-wrap referral-wrap">
            <div class="wallet-banner">
                <h2 class="page-title">Refer & Earn: Give $20, Get $20</h2>
            </div>

            <span class="referral-options">Options:</span>

            <div class="referral-option">
                <div class="description">
                    <h3 class="opt-title">Share your Referral Code</h3>
                    <a href="#" class="referral-action-button" data-action="copy" data-target="referral_code">COPY</a>

                    <div class="widget-active-balance">
                        <span class="title">Referral code</span>
                        <div class="copy-wrap"><span
                                    class="wallet-referral-code"><?= $wallet->get_referral_coupon() ?></span>
                            <div class="copy-trigger"
                                 id="referral_code">
                                <svg xmlns="http://www.w3.org/2000/svg"
                                     viewBox="0 0 448 512">
                                    <path d="M384 336H192c-8.8 0-16-7.2-16-16V64c0-8.8 7.2-16 16-16l140.1 0L400 115.9V320c0 8.8-7.2 16-16 16zM192 384H384c35.3 0 64-28.7 64-64V115.9c0-12.7-5.1-24.9-14.1-33.9L366.1 14.1c-9-9-21.2-14.1-33.9-14.1H192c-35.3 0-64 28.7-64 64V320c0 35.3 28.7 64 64 64zM64 128c-35.3 0-64 28.7-64 64V448c0 35.3 28.7 64 64 64H256c35.3 0 64-28.7 64-64V416H272v32c0 8.8-7.2 16-16 16H64c-8.8 0-16-7.2-16-16V192c0-8.8 7.2-16 16-16H96V128H64z"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                    <div class="referral-comment">Share code with a friend and instruct them to apply on final checkout
                    </div>
                </div>
            </div>

            <div class="referral-option">
                <div class="description">
                    <h3 class="opt-title">Use Your Referral Link</h3>
                    <a href="#" class="referral-action-button" data-action="copy" data-target="referral_url">COPY</a>

                    <div class="widget-active-balance">
                        <span class="title">Referral link</span>
                        <div class="copy-wrap"><span
                                    class="wallet-referral-code"><?= $wallet->get_referral_url() ?></span>
                            <div class="copy-trigger"
                                 id="referral_url">
                                <svg xmlns="http://www.w3.org/2000/svg"
                                     viewBox="0 0 448 512">
                                    <path d="M384 336H192c-8.8 0-16-7.2-16-16V64c0-8.8 7.2-16 16-16l140.1 0L400 115.9V320c0 8.8-7.2 16-16 16zM192 384H384c35.3 0 64-28.7 64-64V115.9c0-12.7-5.1-24.9-14.1-33.9L366.1 14.1c-9-9-21.2-14.1-33.9-14.1H192c-35.3 0-64 28.7-64 64V320c0 35.3 28.7 64 64 64zM64 128c-35.3 0-64 28.7-64 64V448c0 35.3 28.7 64 64 64H256c35.3 0 64-28.7 64-64V416H272v32c0 8.8-7.2 16-16 16H64c-8.8 0-16-7.2-16-16V192c0-8.8 7.2-16 16-16H96V128H64z"/>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="referral-comment">Share the link with a friend. Once they click it, the discount will be
                        applied automatically during checkout
                    </div>
                </div>
            </div>

            <div class="referral-option">
                <div class="description">
                    <h3 class="opt-title">Invite by Email</h3>
                    <a href="#" class="referral-action-button" data-action="submit"
                       data-target="referral_email">SEND</a>

                    <form class="wallet-invite-referral" id="referral_email">
                        <input type="hidden" name="referral_user" value="<?= $wallet->get_user()->ID ?>">
                        <input type="hidden" name="action" value="referral_send_email">
                        <input type="email" name="email" placeholder="email">
                        <div class="message" style="display:none;"></div>
                    </form>

                    <div class="referral-comment">Enter friend's email and submit to start sharing your referral
                    </div>
                </div>
            </div>
            <p>
                Have a question about referral? <a href="#help">Click here for help</a>
            </p>
        </div>
        <script>
            (function ($) {
                $('.copy-trigger').click(function () {
                    copy(this);
                });

                $('.referral-action-button[data-action]').click(function (e) {
                    e.preventDefault();
                    switch ($(this).data('action')) {
                        case "copy" : {
                            copy($("#" + $(this).data('target')));
                            break;
                        }
                        case "submit" : {
                            $("#" + $(this).data('target')).submit();
                            break;
                        }
                    }
                });

                $('.wallet-invite-referral').on('submit', function (e) {
                    e.preventDefault();
                    let form = $(this),
                        data = form.serialize();

                    form.find('input').prop('disabled', true);

                    form.find('.message').text('').removeClass('success').removeClass('error').hide();

                    $.post('<?=admin_url('admin-ajax.php')?>', data, function (response) {
                        if (response.message) {
                            let error_class = response.ok ? "success" : "error";
                            form.find('.message').addClass(error_class).text(response.message).slideDown();
                        }
                        form.find('input').prop('disabled', false);
                    });
                });


                function copy(trigger) {

                    var referralCode = $(trigger).siblings('.wallet-referral-code').text();

                    if (typeof navigator.clipboard !== 'undefined') {
                        navigator.clipboard.writeText(referralCode);
                    } else {
                        var tempTextArea = $('<textarea></textarea>');
                        tempTextArea.val(referralCode);
                        $('body').append(tempTextArea);
                        tempTextArea.select();
                        document.execCommand('copy');
                        tempTextArea.remove();
                    }

                    var copyWrap = $(trigger).closest('.copy-wrap');
                    copyWrap.addClass('copied');

                    // Remove the "copied" class after the animation completes
                    setTimeout(function () {
                        copyWrap.removeClass('copied');
                    }, 1000);
                }
            })(jQuery)
        </script>
        <?php
    }

    public function checkout_field()
    {
        if (!is_user_logged_in()) {
            ?>
            <div class="wallet-checkout-field" id="wallet_checkout_wrap"></div><?php
            return;
        }

        $wallet = user_wallet();
        if ($wallet->get_balance() <= 0) {
            ?>
            <div class="wallet-checkout-field" id="wallet_checkout_wrap"></div><?php
            return;
        }

        $has_wallet_coupons = false;
        foreach (WC()->cart->applied_coupons as $applied_coupon) {
            if (str_starts_with(strtoupper($applied_coupon), 'WALLET_')) {
                $has_wallet_coupons = true;
            }
        }

        $cart_total = floatval(WC()->cart->get_total(false));
        if ($cart_total <= 0) {
            ?>
            <div class="wallet-checkout-field" id="wallet_checkout_wrap"></div><?php
            return;
        }

        $discount = 0;
        if ($has_wallet_coupons) {
            $discount = WC()->cart->get_discount_total();
            $cart_total = $cart_total + $discount;
        }
        $max_use = min($wallet->get_balance(), $cart_total);

        if ($max_use <= 0 || $discount == $wallet->get_balance()) {
            ?>
            <div class="wallet-checkout-field" id="wallet_checkout_wrap"></div><?php
            return;
        }

        ?>
        <div class="wallet-checkout-field" id="wallet_checkout_wrap">
            <div class="wallet-container">
                <div class="wallet-balance">You have <?= wc_price($wallet->get_balance()) ?> in your Wallet.</div>
                <div class="use-wallet-wrapper">
                    <label class="use-wallet">
                        <input type="number" name="use_wallet" value="0" min="0" max="<?= $max_use ?>"
                               placeholder="Use balance" id="use_wallet">
                        <span class="use-wallet-button">Use</span>
                    </label>
                </div>
                <span class="note">You can use up to <?= wc_price($max_use) ?>. <a href="#"
                                                                                   class="wallet-use-max">Use <?= wc_price($max_use) ?> >></a></span>
                <script>
                    (function ($) {
                        $('.wallet-use-max').on('click', function (e) {
                            e.preventDefault();
                            let input = $('input#use_wallet');
                            input.val(input.attr('max'));
                            use_balance();
                        });

                        $('input#use_wallet').on('input change', function (e) {
                            if ($(this).val() > parseFloat($(this).attr('max'))) {
                                $(this).val($(this).attr('max'));
                            }
                        });

                        $('input#use_wallet').on('keydown', function (e) {
                            if (e.which === 13 || e.keyCode === 13) {
                                e.preventDefault();
                                use_balance();
                            }
                        });

                        $('.use-wallet-button').on('click', function (e) {
                            e.preventDefault();
                            use_balance();
                        })

                        function use_balance() {
                            let wrap = $('#wallet_checkout_wrap'),
                                input = $('input#use_wallet');

                            wrap.addClass('loading');
                            $.post('<?=admin_url('admin-ajax.php')?>', {
                                action: 'checkout_apply_wallet',
                                balance: input.val()
                            }, function (response) {
                                wrap.removeClass('loading');
                            }).always(function (response) {
                                $(document.body).trigger("update_checkout");
                            });
                        }
                    })(jQuery)
                </script>
            </div>
        </div>
        <?php
    }

    public function checkout_field_refresh($fragments)
    {
        ob_start();
        $this->checkout_field();
        $fragments['.wallet-checkout-field'] = ob_get_clean();

        return $fragments;
    }

    public function checkout_apply_wallet()
    {
        $wallet = user_wallet();
        $balance_to_use = $_POST['balance'];
        $wallet_balance = $wallet->get_balance();

        $has_wallet_coupons = false;
        foreach (WC()->cart->applied_coupons as $applied_coupon) {
            if (str_starts_with(strtoupper($applied_coupon), 'WALLET_')) {
                $has_wallet_coupons = $applied_coupon;
            }
        }

        $cart_total = floatval(WC()->cart->get_total(false));
        if ($has_wallet_coupons) {
            $discount = WC()->cart->get_discount_total();
            $cart_total = $cart_total + $discount;
        }
        $max_use = min($wallet->get_balance(), $cart_total);

        if (!$balance_to_use || $balance_to_use < 0 || $balance_to_use > $wallet_balance || $balance_to_use > $max_use) {
            wc_add_notice("Invalid amount of balance to use. You can not use \${$balance_to_use}.");
            wp_send_json(['ok' => false, 'message' => "Invalid amount of balance to use. You can not use \${$balance_to_use}."]);
            wp_die();
        }

        WC()->cart->remove_coupon($has_wallet_coupons);
        WC()->cart->calculate_totals();

        $coupon_code = $wallet->get_checkout_coupon($balance_to_use);

        WC()->cart->apply_coupon($coupon_code);
        WC()->cart->calculate_totals();

        wp_send_json(['ok' => true]);
        wp_die();
    }

    public function coupon_redeemed($order_id, $old_status, $new_status)
    {
        if ($new_status != 'processing') {
            return;
        }

        $order = wc_get_order($order_id);
        $used_coupons = $order->get_coupon_codes();

        if (!empty($used_coupons)) {
            foreach ($used_coupons as $coupon_code) {
                if (str_starts_with(strtoupper($coupon_code), 'WALLET_')) {
                    $coupon_id = wc_get_coupon_id_by_code($coupon_code); // Get coupon ID by code
                    $deducted = get_post_meta($coupon_id, 'coupon_wallet_deducted', true);
                    if ($deducted) continue;

                    $user_id = get_post_meta($coupon_id, 'coupon_wallet_user', true);
                    $wallet = user_wallet($user_id);
                    if ($wallet) {
                        $wallet->redeem_coupon($coupon_id, $order_id);
                    }
                }
            }
        }
    }

    public function coupon_removed($coupon_code)
    {
        if (str_starts_with(strtoupper($coupon_code), 'WALLET_')) {
            $coupon_id = wc_get_coupon_id_by_code($coupon_code); // Get coupon ID by code
            $user_id = get_post_meta($coupon_id, 'coupon_wallet_user', true);
            $wallet = user_wallet($user_id);
            if ($wallet) {
                $wallet->releaze_balance();
                $wallet->releaze_coupon();
            }
        }
    }

    public function referral_coupon_redeemed($order_id, $old_status, $new_status)
    {
        $referral_processed = get_post_meta($order_id, 'referral_code_processed', true);
        if ($referral_processed) return;

        $order = wc_get_order($order_id);
        $used_coupons = $order->get_coupon_codes();
        $referral_coupon_id = false;

        if (!empty($used_coupons)) {
            foreach ($used_coupons as $coupon_code) {
                if (str_starts_with(strtoupper($coupon_code), 'REF_')) {
                    $referral_coupon_id = wc_get_coupon_id_by_code($coupon_code);
                    break;
                }
            }
        }

        if (!$referral_coupon_id) return;

        $wallet_user_id = get_post_meta($referral_coupon_id, 'coupon_wallet_user', true);
        if (!$wallet_user_id) return;

        $wallet = user_wallet($wallet_user_id);
        if (!$wallet) return;

        if ($new_status == 'processing') {
            $referral_applied = get_post_meta($order_id, 'referral_code_applied', true);
            if ($referral_applied) {
                return;
            }
            $wc_coupon = new \WC_Coupon($coupon_code);

            $amount = $wc_coupon->get_amount();

            $pending_balance_id = $wallet->add_pending_balance($amount,
                [
                    'name' => "Referral reward for invite",
                    'order_id' => $order_id
                ]
            );
            $order->add_order_note("<strong>Order is referral!</strong><br>" .
                "<span>Invited by <a href='" . (has_wallet_access() ? $wallet->get_edit_url() : "/wp-admin/user-edit.php?user_id={$wallet_user_id}") . "'>{$wallet->get_user()->display_name}</a></span><br>" .
                "<span>Added $ {$amount} as pending balance. Would be applied as soon as order Completed.</span>");

            update_post_meta($order_id, 'referral_code_applied', $pending_balance_id);
            return;
        }

        if ($new_status == 'completed') {
            $pending_balance_id = get_post_meta($order_id, 'referral_code_applied', true);
            if (!$pending_balance_id) {
                return;
            }
            $applied = $wallet->apply_pending_balance($pending_balance_id);

            if ($applied) {
                $order->add_order_note("<span>Approved $ {$applied} of pending referral reward. Now it is available as wallet balance for <a href='" . (has_wallet_access() ? $wallet->get_edit_url() : "/wp-admin/user-edit.php?user_id={$wallet_user_id}") . "'>{$wallet->get_user()->display_name}</a>.</span>");
            }
            update_post_meta($order_id, 'referral_code_processed', true);
            return;
        }

        if (in_array($new_status, ['refunded', 'cancelled'])) {
            $pending_balance_id = get_post_meta($order_id, 'referral_code_applied', true);
            if (!$pending_balance_id) {
                return;
            }
            $wallet->deny_pending_balance($pending_balance_id);

            $order->add_order_note("<span>Pending referral reward was canceled due to order cancellation.");
            update_post_meta($order_id, 'referral_code_processed', true);
            return;
        }
    }

    public function referral_coupon_validation($coupon_code)
    {
        if (!is_user_logged_in()) return;

        if (str_starts_with(strtoupper($coupon_code), 'REF_')) {
            $cart = WC()->cart;
            $cart->remove_coupon($coupon_code);
            wc_clear_notices();

            wc_add_notice(__('This referral code could not be applied to your account.', 'woocommerce'), 'error');
        }
    }

    public function maybe_apply_wallet_referral_code()
    {
        if (is_user_logged_in()) return;

        $referral_code = sanitize_text_field($_REQUEST['referral']);

        if (!$referral_code) return;

        if (!str_starts_with(strtoupper($referral_code), 'REF_')) return;

        $coupon_id = wc_get_coupon_id_by_code($referral_code);
        $user_id = get_post_meta($coupon_id, 'coupon_wallet_user', true);
        $wallet = user_wallet($user_id);

        if (!$wallet) return;

        ?>
        <script>(function ($) {
                function addStringToLocalStorageOrCookie(key, value) {
                    if (typeof (Storage) !== "undefined") {
                        localStorage.setItem(key, value);
                    } else {
                        $.cookie(key, value, {path: '/'});
                    }
                }

                addStringToLocalStorageOrCookie('referral_coupon', '<?=$referral_code?>');

                $(document).ready(function () {
                    $('.site-main').addClass('show-discount');
                });
            })(jQuery)</script>
        <?php
    }

    public function maybe_process_referral_code()
    {
        if (!is_checkout()) {
            return;
        }

        $cartItem = cart_item_has_project_details_pending(true);
        $itemHasPendingProjectDetails = cart_item_has_pending_project($cartItem);

        if ($cartItem && $itemHasPendingProjectDetails) {
            return;
        }
        ?>
        <script id="referral">(function ($) {
                function getStringFromLocalStorageOrCookie(key) {
                    if (typeof (Storage) !== "undefined") {
                        return localStorage.getItem(key);
                    } else {
                        return $.cookie(key);
                    }
                }

                let referral_code = getStringFromLocalStorageOrCookie('referral_coupon');
                if (referral_code) {
                    $.ajax({
                        type: 'POST',
                        url: '/?wc-ajax=apply_coupon',
                        data: {
                            coupon_code: referral_code,
                            security: wc_checkout_params.apply_coupon_nonce
                        },
                        dataType: 'html',
                        complete: function () {
                            $(document.body).trigger('update_checkout');
                        }
                    });
                }
            })(jQuery)</script>
        <?php
    }

    public function referral_send_email()
    {
        $referral_user = sanitize_key($_POST['referral_user']);
        $email = sanitize_email($_POST['email']);

        if (!$email) {
            wp_send_json(['ok' => false, 'message' => 'Please input valid email']);
            wp_die();
        }

        $wallet = user_wallet($referral_user);

        if (!$referral_user || !$wallet) {
            wp_send_json(['ok' => false, 'message' => 'Oops something went wrong... Try other option']);
            wp_die();
        }

        $subject = "{$wallet->get_user()->first_name} {$wallet->get_user()->last_name} shared discount with you! | TheBusinessToolkit";

        $mailer = WC()->mailer();

        ob_start();
        ?>

        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
        </head>
        <body style="font-family: Arial;background:#111;background-image:url(<?= Wallet_URL . "/assets/icons/referral-bg.png" ?>); background-size: cover;">
        <div id="wrapper"
             style="width:100%; padding: 100px 5px 120px;box-sizing: border-box;">
            <h2 style="font-weight:bold;line-height:130%;margin:0 0 18px;text-align: center;font-size: 24px;color: white;"><?= "{$wallet->get_user()->first_name} {$wallet->get_user()->last_name}" ?>
                set you a referral link!</h2>
            <p style="color: white;">Follow the link to redeem your $20 off!</p>
            <a style="text-decoration:none;color: black;background: linear-gradient(135deg, #004d08, #46f727 80%);margin: 50px;display: block;font-size: 24px;padding: 8px 16px 7px;border-radius: 3px;font-weight: 600;"
               href="<?= $wallet->get_referral_url() ?>">Get Discount</a>
            <p style="color: white;text-decoration:none;">Have a question about referral? <a style="color: #46f727"
                                                                                             href="<?= site_url() ?>">Click
                    here for help</a></p>
        </div>
        </body>
        </html>

        <?php
        $content = ob_get_clean();

        $headers = "Content-Type: text/html\r\n";

        $mailer->send($email, $subject, $content, $headers);

        wp_send_json(['ok' => true, 'message' => 'Email sent!']);
        wp_die();
    }

    public function registration_redirect($redirect)
    {
        if (isset($_GET['after']) && 'referral' == $_GET['after']) {
            return '/my-account/referral/';
        }
        return $redirect;
    }

//    GIFTCARDS

}