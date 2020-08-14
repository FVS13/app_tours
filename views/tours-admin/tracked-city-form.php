<?php
/**
 * @var app\models\Citys[] $transit_citys
 */

use yii\bootstrap\ActiveForm;

?>

<div class="adm_popup tracked-city">
    <div class="popup_over"></div>
    <div class="popup_form">
        <div class="popup_close"></div>
        <div class="popup_container">
            <h2>Редактирование НП (* - обязательные)</h2>
            <div class="errors"></div>
            <?php ActiveForm::begin() ?>
                <input type="hidden" name="action">
                <input type="hidden" name="city[id]">
                <input type="hidden" name="binded_airport[tracked_city_id]">
                <div class="form_stroka">
                    <span>1. Название НП *</span>
                    <input type="text" name="city[name]" required>
                </div>
                <div class="form_stroka">
                    <span>2. Топонимический аналог</span>
                    <input type="text" name="city[toponymic_analogue]">
                </div>
                <div class="form_stroka">
                    <span>3. Код ОКТМО *</span>
                    <input type="text" name="city[city_code]" required class="uzkiy_input" disabled>
                </div>
                <div class="form_stroka">
                    <span>4. Разница GMT *</span>
                    <input type="text" name="city[time_zone_gmt]" required class="input_gmt" disabled>
                </div>
                <div class="form_stroka">
                    <span>5.1. Ближайший аэропорт</span>
                    <select
                        name="binded_airport[binded_airport_id]"
                        id="binded_airports_list"
                        style="width: 200px;"
                        required
                    >
                        <?php
                        foreach ($transit_citys as $city) :
                            ?>
                                <option
                                    value="<?= $city->airport->id ?>"
                                    data-city-id="<?= $city->id ?>"
                                ><?= $city->name ?></option>
                            <?php
                        endforeach;
                        ?>
                    </select>
                </div>
                <div class="form_stroka">
                    <span>5.2. Расст. до ближ. а/п</span>
                    <input type="text" name="binded_airport[distance_to_airport]" class="uzkiy_input">
                </div>
                <div class="form_stroka">
                    <input type="checkbox" name="binded_airport[is_tracked]" id="binded_airport__is_tracked" hidden>
                    <label for="binded_airport__is_tracked">6. Отслеживается по аэропорту</label>
                </div>
                <button type="submit" disabled>Сохранить</button>
            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        jQuery.noConflict();
        $('#binded_airports_list').select2({
            width: 'style',
        }).on('select2:select', function (e) {
            $(".popup_form button").prop('disabled', false);
        });
    });
</script>
