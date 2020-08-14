<?php

use yii\helpers\Html;

if (empty($form_class)) {
    $form_class = 'transit-city';
}

?>

<div class="adm_popup <?= $form_class ?>" id="<?= $form_class ?>" style="display: none;">
    <div class="popup_over"></div>
    <div class="popup_form">
        <div class="popup_close"></div>
        <div class="popup_container">
            <h2><span class="form-title">Редактирование аэропорта</span> (* - обязательные)</h2>
            <div class="errors"></div>
            <?= Html::beginForm() ?>
                <input type="hidden" name="action">
                <input type="hidden" name="city[id]">
                <input type="hidden" name="airport[location]">
                <div class="form_stroka">
                    <span>1. Название *</span>
                    <input type="text" name="city[name]" required>
                </div>
                <?php
                for ($i = 1; $i < 6; $i++) :
                    ?>
                        <div class="form_stroka">
                            <span>2.<?= $i ?>. Альт. название <?= $i ?></span>
                            <input type="text" name="alt_names[<?= $i ?>]" class="alt_names">
                        </div>
                    <?php
                endfor;
                ?>
                <div class="form_stroka">
                    <span>3. Разница GMT *</span>
                    <input type="text" name="city[time_zone_gmt]" required class="input_gmt">
                </div>
                <div class="form_stroka">
                    <span>4. Код Аэропорта *</span>
                    <input type="text" name="airport[airport_code]" class="uzkiy_input" required>
                </div>
                <button type="submit" disabled>Сохранить</button>
            <?= Html::endForm() ?>
        </div>
    </div>
</div>
