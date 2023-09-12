<?php
if (!isset($page_num)) return;

$base_url = $base_url??admin_url('admin.php')."?page=wallet";
$current_page = $page??1;
$prev_page = ($current_page - 1) > 0 ? $current_page - 1 : false;
$next_page = ($current_page + 1) < $page_num ? $current_page + 1 : false;

$start_page = 1;
$end_page = $page_num;
$to_start = $to_end = false;

if ($current_page >= 7) {
    $to_start = true;
    $start_page = $current_page - 3;
}

if ($page_num - $current_page >= 7) {
    $to_end = true;
    $end_page = $current_page + 3;
}


?>
<nav class="wallet-pagination">
    <?php if ($to_start) {
        wallet_template(
            'parts/pagination-item',
            [
                'page' => 1,
                'label' => '<<',
                'page_url' => $base_url,
                'class' => 'to-start',
            ]
        );
    } ?>

    <?php if ($prev_page) {
        wallet_template(
            'parts/pagination-item',
            [
                'page' => $prev_page,
                'label' => '<',
                'page_url' => $base_url,
                'class' => 'prev',
            ]
        );
    } ?>


    <?php if ($to_start) {
        wallet_template(
            'parts/pagination-item',
            [
                'label' => '...',
                'page' => 1,
            ]
        );
    } ?>


    <?php
    for ($i = $start_page; $i <= $end_page; $i++) {
        $selected = $i == $current_page;
        wallet_template(
            'parts/pagination-item',
            [
                'page' => $i,
                'page_url' => $selected ? false : $base_url,
                'class' => $selected ? "active" : "",
            ]
        );
    } ?>

    <?php if ($to_end) {
        wallet_template(
            'parts/pagination-item',
            [
                'label' => '...',
                'page' => 1,
            ]
        );
    } ?>

    <?php if ($next_page) {
        wallet_template(
            'parts/pagination-item',
            [
                'page' => $next_page,
                'label' => '>',
                'page_url' => $base_url,
                'class' => 'next',
            ]
        );
    } ?>


    <?php if ($to_end) {
        wallet_template(
            'parts/pagination-item',
            [
                'page' => $page_num,
                'label' => '>>',
                'page_url' => $base_url,
                'class' => 'to-end',
            ]
        );
    } ?>
</nav>