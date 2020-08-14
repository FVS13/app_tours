<?php

/**
 * @var app\models\NewCitys[] $new_names
 */

use yii\helpers\Html;

echo Html :: csrfMetaTags();

?>

<table cellspacing="0" class="goroda_table new-city" id="new-city-table">
    <thead>
        <tr>
            <th>Название</th>
            <th data-orderable="false">Прикрепить альт. название</th>
            <th data-orderable="false">Добавить аэропорт</th>
        </tr>
    </thead>
    <tbody>
        <?php
        foreach ($new_names as $new_name) :
            ?>
                <tr>
                    <td class="city_name"><?= $new_name->name ?></td>
                    <td><a class="attach_alt_city_button">Прикрепить</a></td>
                    <td>
                        <a
                            class="add_transit_city"
                            data-action="add-new-transit-city"
                            data-title="Добавить аэропорт"
                        >Добавить</a>
                    </td>
                </tr>
            <?php
        endforeach;
        ?>
    </tbody>
</table>
