<?php
if(!isset($user_id)) return;

?>
<h2>Add Balance</h2>
<p>Set amount of balance you want to add and the reason for it.</p>
<form class="wallet-form wallet-add-balance">
    <input type="hidden" name="user_id" value="<?=$user_id?>">
    <label>
        <span class="label">Amount</span>
        <input type="number" name="amount" value="0" min="1" required>
    </label>
    <label>
        <span class="label">Name/reason of transaction</span>
        <input type="text" name="reason" placeholder="Refund to wallet" value="" required>
    </label>
    <input class="wallet-btn" type="submit" value="Add">
</form>
