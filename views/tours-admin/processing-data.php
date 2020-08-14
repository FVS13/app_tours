<?php

$this->title = 'Обработка и выгрузка данных';

?>

<div class="adm_perekl_container">
    <form
        class="halt-tasks"
        data-confirm-text="Завершить все задачи?"
        action="halt-tasks"
        method="POST"
    >
        <div class="notice">
            <p>
                Включенный автосбор запускается по графику:
                с 3:00 до 15:00 часов – каждые 15 минут;
                с 15:00 до 23:00 часов – в начале каждого часа.
            </p>
            <p>
                Включенная автовыгрузка запускается в 00:10 часов. Настройки создания архива и мин-макса применяются как к автовыгрузке,<br />так и к ручной выгрузке.
            </p>
            </div>
        <button
            type="submit"
            <?= $exists_started_task ? '' : 'disabled' ?>
        >Завершить все задачи</button>
    </form>
</div>
<div class="adm_perekl_container">
    <div class="adm_perekl_row">Автосбор в базу данных</div>
    <div class="adm_perekl_row">
        <form
            class="sbor_vbazu"
            data-confirm-text="Запустить сбор в базу данных?"
            action="parse"
            method="POST"
        >
            <div class="adm_form_block">
                <input
                    type="checkbox"
                    name="auto_parse"
                    id="auto_parse"
                    <?= $settings['auto_parse'] ? 'checked' : '' ?>
                >
                <label for="auto_parse" class="setting"></label>
            </div>
            <?= $this->render('//tours-admin/task', [
                'exists_started_task' => $exists_started_task,
                'task' => $last_parse,
                'button_label' => 'Запустить сбор вручную',
            ]); ?>
        </form>
    </div>
</div>

<?= $this->render('//tours-admin/processing-data__icaos', [
    'settings' => $settings,
    'exists_started_task' => $exists_started_task,

    'export_icaos_ozon' => $export_icaos_ozon,
    'import_icaos_ozon' => $import_icaos_ozon,
    'export_icaos_seatguru' => $export_icaos_seatguru,
    'import_icaos_seatguru' => $import_icaos_seatguru,
]); ?>

<div class="adm_perekl_container">
    <div class="adm_perekl_row">Автовыгрузка из базы данных</div>
    <div class="adm_perekl_row">
        <form
            class="vigruzka_izbazi"
            data-confirm-text="Запустить выгрузку из базы данных?"
            action="export"
            method="POST"
        >
            <div class="adm_form_block">
                <input
                    type="checkbox"
                    name="auto_export"
                    id="auto_export"
                    <?= $settings['auto_export'] ? 'checked' : '' ?>
                >
                <label for="auto_export" class="setting"></label>
            </div>
            <div class="adm_form_block">
                <p>Создавать архив</p>
                <input
                    type="checkbox"
                    name="create_archive"
                    id="create_archive"
                    <?= $settings['create_archive'] ? 'checked' : '' ?>
                >
                <label for="create_archive" class="setting yes-no"></label>
            </div>
            <div class="adm_form_block">
                <p>Создать файл мин-макс</p>
                <input
                    type="checkbox"
                    name="create_xslx"
                    id="create_xslx"
                    <?= $settings['create_xslx'] ? 'checked' : '' ?>
                >
                <label for="create_xslx" class="setting yes-no"></label>
            </div>
            <?= $this->render('//tours-admin/task', [
                'exists_started_task' => $exists_started_task,
                'task' => $last_export,
                'button_label' => 'Запустить выгрузку вручную',
            ]); ?>
        </form>
    </div>
</div>

<script src="/js/processing-data.js"></script>
