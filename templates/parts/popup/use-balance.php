<?php
if(!isset($user_id)) return;

?>
<h2>Use Balance</h2>
<p>Set amount of balance you want to use(deduct) and the reason for it.</p>
<form class="wallet-form wallet-add-balance">
    <input type="hidden" name="user_id" value="<?=$user_id?>">
    <input type="hidden" name="balance" value="<?=user_wallet($user_id)->get_balance()?>">
    <label>
        <span class="label">Amount</span>
        <input type="number" name="amount" value="1" min="1" required>
    </label>
    <label>
        <span class="label">Name/reason of transaction</span>
        <input type="text" name="reason" placeholder="Created coupon of value" value="" required>
    </label>
    <input class="wallet-btn" type="submit" value="Use">
</form>
