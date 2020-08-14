<?php
/**
 * @var app\models\Citys[] $tracked_citys
 */
?>

<table cellspacing="0" class="goroda_table tracked-city" id="tracked-city-table">
    <thead>
        <tr>
            <th rowspan="2">Название НП</th>
            <th data-orderable="false" rowspan="2">Топоним. аналог</th>
            <th rowspan="2">Код ОКТМО</th>
            <th rowspan="2">От GMT</th>
            <th data-orderable="false" colspan="3" style="text-align: center">Ближайший аэропорт</th>
            <th data-orderable="false" rowspan="2">Редактир.</th>
        </tr>
        <tr class="tr_bottom">
            <th>название</th>
            <th>D, км.</th>
            <th>код</th>
        </tr>
    </thead>
    <tbody>
        <?php
        foreach ($tracked_citys as $city) :
            $is_tracked_airport_class = (!$city->bindedAirport->is_tracked) ? 'binded-airport-not-tracked' : '';

            if (empty($city->bindedAirport->airport->id)) {
                Yii::error('Ошибка вывода привязанного аэропорта city_id: ' . $city->id);
            }
            ?>
                <tr
                    data-airport-id="<?= $city->bindedAirport->airport->id ?>"
                    data-city-id="<?= $city->id ?>"
                    data-airport-location="<?= $city->bindedAirport->airport->location ?>"
                    data-is-tracked-airport="<?= $city->bindedAirport->is_tracked ?>"
                    class="<?= $is_tracked_airport_class ?>"
                >
                    <td class="city_name"><?= $city->name ?></td>
                    <td class="toponymic_analogue"><?= $city->toponymic_analogue ?></td>
                    <td class="city_code"><?= $city->city_code ?></td>
                    <td class="time_zone_gmt"><?= $city->time_zone_gmt ?></td>
                    <td
                        class="tracked_airport_name airport-city-<?= $city->bindedAirport->airport->locationCity->id ?>"
                    ><?= $city->bindedAirport->airport->locationCity->name ?></td>
                    <td
                        class="distance_to_airport"
                    ><?= $city->bindedAirport->distance_to_airport ?></td>
                    <td
                        class="tracked_airport_code"
                    ><?= $city->bindedAirport->airport->airport_code ?></td>
                    <td><a
                        class="edit_city"
                        data-action="edit-tracked-city"
                    ><img src="images/karandash.png"></a></td>
                </tr>
            <?php
        endforeach;
        ?>
    </tbody>
</table>
