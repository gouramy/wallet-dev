<?php

namespace Wallet;

class Render
{
    public function button($text, $action, $action_data = [], $args = [], $return = false)
    {
        $class = $args['class'] ?? '';
        if ($return) ob_start();
        ?>
        <div class="wallet-btn wallet-<?= $action ?><?= $class ? " {$class}" : '' ?>"
             data-js-action="<?= $action ?>"
             data-js-data='<?= json_encode($action_data) ?>'>
            <button><?= $text ?></button>
        </div>
        <?php
        if ($return) {
            return ob_get_clean();
        }
        return '';
    }

    public function popup($popup_id, $args = [], $return = false)
    {
        $class = $args['class'] ?? '';
        if ($return) ob_start();
        ?>
        <div class="wallet-popup<?= $class ? " {$class}" : ""; ?>" id="<?= $popup_id ?>-popup">
            <div class="wallet-popup-close">X</div>
            <?php wallet_template("parts/popup/{$popup_id}", $args) ?>
        </div>
        <?php
        if ($return) {
            return ob_get_clean();
        }
        return '';
    }

    public function add_balance_button($user_id, $return = false)
    {
        return $this->button('Add Balance', 'add-balance', ['user_id' => $user_id], [], $return);
    }

    public function use_balance_button($user_id, $return = false)
    {
        $wallet = user_wallet($user_id);
        return $this->button('Use Balance', 'use-balance', ['user_id' => $user_id,'balance'=>$wallet->get_balance()], [], $return);
    }


}