<?php
/**
 * @var bool $exists_started_task
 * @var \app\models\ToursReports $task
 * @var string $button_label Текст кнопки запуска
 */
?>
<div class="adm_form_block">
    <button
        type="submit"
        <?= $exists_started_task ? 'disabled' : '' ?>
    ><?= $button_label ?></button>
    <?php
    if (!empty($task)) :
        ?>
        <p class="sv_p <?= $task->status?>"
            <?php
            if ('processing' === $task->status) :
                ?>
                style="--progress-percent: <?= $task->getPercentProgress() ?>%"
                <?php
            endif;
            ?>
        ><?= $task->label ?? '' ?> (<?= $task->getProgressFromTotal() ?>)</p>
        <?php
    endif;
    ?>
</div>
