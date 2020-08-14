<?php

/**
 * @var app\models\Citys[] $tracked_citys
 * @var app\models\Citys[] $transit_citys
 */

$this->title = 'Города';
?>


<?= $this->render('tracked-city-form.php', [
    'transit_citys' => $transit_citys,
]); ?>
<?= $this->render('transit-city-form.php') ?>

<input type="radio" name="table-tabs" id="table-tab-1" checked hidden>
<input type="radio" name="table-tabs" id="table-tab-2" hidden>
<input type="radio" name="table-tabs" id="table-tab-3" hidden>

<div class="table-tabs">
    <label for="table-tab-1" class="table-tab">Города</label>
    <label for="table-tab-2" class="table-tab">Аэропорты</label>
    <label for="table-tab-3" class="table-tab">Новые аэропорты</label>
</div>

<div id="table-tab-1">
    <?= $this->render('tracked-city-table.php', [
        'tracked_citys' => $tracked_citys,
    ]); ?>
</div>
<div id="table-tab-2">
    <a
        class="add_transit_city"
        data-action="add-new-transit-city"
        data-title="Добавить аэропорт"
    >
        Добавить аэропорт
    </a>
    <?= $this->render('transit-city-table', [
        'transit_citys' => $transit_citys,
    ]); ?>
</div>
<div id="table-tab-3">
    <?= $this->render('new-city-table.php', [
        'new_names' => $new_names,
    ]); ?>
</div>


<script>
$(document).ready(function () {
    jQuery.noConflict();
    $('#tracked-city-table, #transit-city-table, #new-city-table').DataTable({
        "paging": false,
        "order": [[ 0, "asc" ]],
        "language": {
            "search": "Поиск по вкладке:",
            "info": "Показано записей _TOTAL_",
            "infoEmpty": 'Не найдено ни одной записи',
            "emptyTable": 'Нет записей',
            "infoFiltered": '(отфильтровано из всех записей)',
        },
    });
});
</script>


<?= $this->render('transit-city-form.php', [
    'action' => 'add-new-transit-city',
    'form_class' => 'adm_popup_for_new_transit_city',
    'form_title' => 'Добавление аэропорта',
]) ?>

<script src="/js/city.js"></script>
