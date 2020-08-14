<div class="adm_perekl_container__v">
    <form
        class="export_icaos"
        data-confirm-text="Запустить экспорт ICAO для ozon?"
        action="export-icaos"
        method="POST"
    >
        <input type="hidden" name="format_name" value="ozon">
        <div class="adm_form_block">
            <button
                type="submit"
                <?= $exists_started_task ? 'disabled' : '' ?>
            >Экспорт ozon</button>
            <input
                type="checkbox"
                name="export_all_icaos_ozon"
                id="export_all_icaos_ozon"
                <?= $settings['export_all_icaos_ozon'] ? 'checked' : '' ?>
            >
            <label for="export_all_icaos_ozon" class="setting"></label>
            <p>Включить в экспорт все нулевые ICAO</p>
            <p class="sv_p <?= $export_icaos_ozon->status ?? '' ?>"><?= $export_icaos_ozon->label ?? '' ?></p>
        </div>
    </form>

    <form class="export_icaos" data-confirm-text="Запустить импорт ICAO для ozon?" action="import-icaos" method="POST">
        <input type="hidden" name="format_name" value="ozon">
        <div class="adm_form_block">
            <button
                type="submit"
                <?= $exists_started_task ? 'disabled' : '' ?>
            >Импорт ozon</button>
            <p class="sv_p <?= $import_icaos_ozon->status ?? '' ?>"><?= $import_icaos_ozon->label ?? '' ?></p>
        </div>
    </form>

    <form
        class="export_icaos"
        data-confirm-text="Запустить экспорт ICAO для seatguru?"
        action="export-icaos"
        method="POST"
    >
        <input type="hidden" name="format_name" value="seatguru">
        <div class="adm_form_block">
            <button
                type="submit"
                <?= $exists_started_task ? 'disabled' : '' ?>
            >Экспорт seatguru</button>
            <input
                type="checkbox"
                name="export_all_icaos_seatguru"
                id="export_all_icaos_seatguru"
                <?= $settings['export_all_icaos_seatguru'] ? 'checked' : '' ?>
            >
            <label for="export_all_icaos_seatguru" class="setting"></label>
            <p>Включить в экспорт все нулевые ICAO</p>
            <p class="sv_p <?= $export_icaos_seatguru->status ?? '' ?>"><?= $export_icaos_seatguru->label ?? '' ?></p>
        </div>
    </form>

    <form class="export_icaos" data-confirm-text="Запустить импорт ICAO для seatguru?" action="import-icaos" method="POST">
        <input type="hidden" name="format_name" value="seatguru">
        <div class="adm_form_block">
            <button
                type="submit"
                <?= $exists_started_task ? 'disabled' : '' ?>
            >Импорт seatguru</button>
            <p class="sv_p <?= $import_icaos_seatguru->status ?? '' ?>"><?= $import_icaos_seatguru->label ?? '' ?></p>
        </div>
    </form>
</div>
