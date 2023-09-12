<?php
if (!isset($page)) return;
if (!isset($label)) $label = $page;
if (!isset($page_url)) $page_url = false;
if (!isset($class)) $class = '';


?>
    <div class="pagination-link-wrap <?= $class ?>">
        <?php if ($page_url) { ?>
            <a href="<?= $page_url ?>&paginate=<?= $page ?>" class="pagination-item"><?= $label ?></a>
        <?php } else { ?>
            <span class="pagination-item"><?= $label ?></span>
        <?php } ?>
    </div>
<?php