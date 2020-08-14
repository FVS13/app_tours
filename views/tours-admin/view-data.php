<?php

use app\models\Tours;

$this->title = 'Входящие данные';

?>

<script src="/js/view-data.js"></script>

<div class="adm_cont_head">
    <form class="vhod_dannie_filter_form" method="post">
        <div class="adm_head_selects">
            <div class="adm_select">
                <p>Транспорт</p>
                <select
                    name="transport"
                    class="select"
                    id="select_transp"
                    multiple="multiple"
                    style="width: 130px;"
                >
                    <option value="Y"><?= Tours::getTourTypeLabel('Y') ?></option>
                    <option value="C"><?= Tours::getTourTypeLabel('C') ?></option>
                    <option value="BUS"><?= Tours::getTourTypeLabel('BUS') ?></option>
                </select>
            </div>
        </div>
        <div class="adm_head_buttons">
            <button class="primenit_istochnik apply" disabled type="submit">Применить</button>
        </div>
    </form>
</div>
<table cellspacing="0" class="napravl_table" id="view-data-table">
    <thead>
        <tr>
            <th class="td_center">Источник</th>
            <th>Транспорт</th>
            <th>Направлений</th>
            <th data-orderable="false">Записей всего / сег. (ош.)</th>
            <th>Посл.обновление, мск.</th>
        </tr>
    </thead>
    <tbody>
        <?php
        foreach ($statistics as $summary) :
            $tour_type_label = Tours::getTourTypeShortLabel($summary['tour_type']);
            ?>
                <tr data-tour-type="<?= $summary['tour_type'] ?>">
                    <td><?= $summary['validity'] ?></td>
                    <td><?= $tour_type_label ?></td>
                    <td><?= $summary['count_routes'] ?? 0 ?></td>
                    <td>
                        <?= $summary['all_days'] ?? 0 ?>
                        /
                        <?= $summary['today'] ?? 0 ?>

                        <?php
                        if (isset($summary['errors_count']) && $summary['errors_count'] > 0) :
                            ?>

                            <span class="fiol">
                                (<?= $summary['errors_count'] ?>)
                            </span>

                            <?php
                        else :
                            ?>

                            (<?= $summary['errors_count'] ?? 0 ?>)

                            <?php
                        endif;
                        ?>
                    </td>
                    <td><?= $summary['collected-gmt'] ?? '-' ?></td>
                </tr>
            <?php
        endforeach;
        ?>
    </tbody>
</table>

<script>
$(document).ready(function () {
    // $.noConflict();
    $('#view-data-table').DataTable( {
        "paging": false,
        "language": {
            "search": "Поиск по вкладке:",
            "info": "Показано записей _TOTAL_",
            "infoEmpty": 'Не найдено ни одной записи',
            "emptyTable": 'Нет записей',
            "infoFiltered": '(отфильтровано из 200 общих записей)',
        },
    } );
});
$(document).ready(function() {
    $('#select_transp').select2({
        placeholder: 'Все',
        width: 'style',
    });
});
</script>
