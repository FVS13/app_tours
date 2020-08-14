<?php
/**
 * @var app\models\Citys[] $transit_citys
 */
?>
<table cellspacing="0" class="goroda_table transit-city" id="transit-city-table">
    <thead>
        <tr>
            <th>Название</th>
            <th data-orderable="false" >Альт. название 1</th>
            <th data-orderable="false" >Альт. название 2</th>
            <th data-orderable="false" >Альт. название 3</th>
            <th data-orderable="false" >Альт. название 4</th>
            <th data-orderable="false" >Альт. название 5</th>
            <th>От GMT</th>
            <th>Код&nbsp;</th>
            <th data-orderable="false">Редактировать, удалить</th>
        </tr>
    </thead>
    <tbody>
        <?php
        foreach ($transit_citys as $city) :
            ?>
                <tr
                    data-city-id="<?= $city->id ?>"
                    data-airport-id="<?= $city->airport->id ?>"
                    data-airport-location="<?= $city->airport->location ?>"
                >
                    <td class="city_name"><?= $city->name ?></td>

                    <?php
                    $count_alt_names = 0;

                    if (!empty($city->altNames)) :
                        $count_alt_names = count($city->altNames);
                        $i = 1;

                        foreach ($city->altNames as $alt_name_obj) :
                            ?>
                                <td class="alt_name alt-name-<?= $i++ ?>"><?= $alt_name_obj->alt_name ?></td>
                            <?php
                        endforeach;
                    endif;

                    for ($i = $count_alt_names + 1; $i < 6; $i++) {
                        echo '<td class="alt_name alt-name-' . $i . '"></td>';
                    }
                    ?>

                    <td class="time_zone_gmt"><?= $city->time_zone_gmt ?></td>
                    <td class="airport_code"><?= $city->airport->airport_code ?></td>
                    <td>
                        <a
                            class="edit_transit_city"
                            data-action="edit-transit-city"
                            data-title="Редактировать аэропорт"
                        ><img src="images/karandash.png"></a>
                        <a
                            class="remove_transit_city"
                            data-action="remove-transit-city"
                        ><img src="images/remove-icon.png"></a>
                    </td>
                </tr>
            <?php
        endforeach;
        ?>
    </tbody>
</table>
