<?php

/**
 * @var array<int,string> $departure_citys
 * @var array<int,string> $arrival_citys
 * @var string[] $validities
 */

use app\models\Tours;
use yii\helpers\Url;

$this->title = 'Направления';
?>


<script src="/js/inputmask.dependencyLib.js"></script>
<script src="/js/inputmask.js"></script>
<script type="text/javascript" src="/js/inputmask.date.extensions.js"></script>
<script src="/js/routes.js"></script>

<div class="adm_cont_head">
    <form method="post" class="napr_form">
        <div class="adm_head_selects">
            <div class="adm_select">
                <p>Город отправления</p>
                <select
                    class="select_gorod_otpravleniya"
                    id="select_gorod_otpravleniya"
                    name="select_gorod_otpravleniya[]"
                    multiple="multiple"
                    style="width: 157px"
                >
                    <?php
                    foreach ($departure_citys as $id => $departure_city_name) :
                        ?>
                            <option value="<?= $id ?>"><?= $departure_city_name ?></option>
                        <?php
                    endforeach;
                    ?>
                </select>
            </div>
            <div class="adm_select">
                <p>Город прибытия</p>
                <select
                    class="select_gorod_pribitiya"
                    id="select_gorod_pribitiya"
                    name="select_gorod_pribitiya[]"
                    multiple="multiple"
                    style="width: 157px"
                >
                    <?php
                    foreach ($arrival_citys as $id => $arrival_city_name) :
                        ?>
                            <option value="<?= $id ?>"><?= $arrival_city_name ?></option>
                        <?php
                    endforeach;
                    ?>
                </select>
            </div>
            <div class="adm_select">
                <p>Наличие данных</p>
                <select
                    name="zapisi"
                    class="select"
                    id="select_zapisi"
                    style="width: 134px"
                    multiple="multiple"
                >
                    <option value="isset_air">Есть Авиа</option>
                    <option value="isset_auto">Есть Авто</option>
                    <option value="empty">Нет</option>
                </select>
            </div>
            <div class="adm_select">
                <p>Источник данных</p>
                <select
                    name="istochn[]"
                    class="select"
                    id="select_istochn"
                    style="width: 157px"
                    multiple="multiple"
                >
                    <?php
                    foreach ($validities as $validity_name) :
                        ?>
                            <option><?= $validity_name ?></option>
                        <?php
                    endforeach;
                    ?>
                </select>
            </div>
        </div>
        <div class="adm_head_buttons_right">
            <button class="apply" type="submit" disabled>Применить</button>
        </div>
    </form>
</div>
<div id="view-data-table_info" aria-live="polite" style="padding-bottom: 15px;"></div>
<div class="adm_content" style="padding-bottom: 50px;" id="routes-table">
    <div class="adm_tabl_head">
        <div class="adm_tab_head_block">№</div>
        <div class="adm_tab_head_block sorting">
            <a data-sort="napr" class="sort sort_vniz">Направление</a>
        </div>
        <div class="adm_tab_head_block sorting">
            <a data-sort="kod_napr" class="sort">Код направления</a>
        </div>
        <div class="adm_tab_head_block">А-Э вс./сег.</div>
        <div class="adm_tab_head_block">А-Б вс./сег.</div>
        <div class="adm_tab_head_block">АВТО вс./сег.</div>
        <div class="adm_tab_head_block">
            <a
                href="<?= Url::to(['routes/get-routes-xlsx']) ?>"
                target="__blank"
                class="download-exel"><img src="images/exel-download.png"
            ></a>
        </div>
    </div>
    <div class="adm_tabl_head2">
        <div class="adm_tab_head_block">D,км.</div>
        <div class="adm_tab_head_block">Дней вс./104</div>
        <div class="adm_tab_head_block">Посл.обновление</div>
        <div class="adm_tab_head_block">Источники данных</div>
    </div>
    <div class="adm_tabl_div_content">
        <?php
        $i = 1;

        foreach ($routes_data as $route_id => $route_data) :
            $tours_types = Tours::getToursTypesList();
            $html = [];

            foreach ($tours_types as $tour_type) {
                $count_all_days = (int) ($route_data['count_data'][ $tour_type ]['count_all_days'] ?? 0);
                $count_today = (int) ($route_data['count_data'][ $tour_type]['count_today'] ?? 0);
                $count_invalid = (int) ($route_data['count_data'][ $tour_type ]['count_invalid'] ?? 0);

                $html[ $tour_type ]  = '<div class="adm_tab_spoiler_block">';
                $html[ $tour_type ] .= $count_all_days;
                $html[ $tour_type ] .= ' / ';
                $html[ $tour_type ] .= $count_today;

                if ($count_invalid > 0) {
                    $html[ $tour_type ] .= '<span class="fiol"> (' . $count_invalid . ')</span>';
                } else {
                    $html[ $tour_type ] .= ' (' . $count_invalid . ')';
                }

                $html[ $tour_type ] .= '</div>';
                $count[ $tour_type ] = $count_all_days + $count_today;
            }

            $count_auto = $count['BUS'];
            $count_air = $count['Y'] + $count['C'];
            ?>
                <div
                    class="adm_tabl_spoiler"
                    id="napr_<?= $route_id ?>"
                    data-departure_city_id="<?= $route_data['departure_city_id'] ?>"
                    data-arrival_city_id="<?= $route_data['arrival_city_id'] ?>"
                    data-all_count="<?= $route_data['all_count'] ?>"
                    data-count_air="<?= $count_air ?>"
                    data-count_auto="<?= $count_auto ?>"
                    data-validities="<?= $route_data['validities'] ?>"
                    data-route-id="<?= $route_id ?>"
                >
                    <div class="adm_tabl_spoiler_otkrito">
                        <div class="adm_spoiler_plus">+</div>
                        <div class="adm_tab_spoiler_block route_id">
                            <?= $i++ ?>
                        </div>
                        <div class="adm_tab_spoiler_block route_name">
                            <a href="#"><?= $route_data['route_name'] ?></a>
                        </div>
                        <div class="adm_tab_spoiler_block route_code">
                            <?= $route_data['route_code'] ?>
                        </div>
                        <?= $html['Y'] ?>
                        <?= $html['C'] ?>
                        <?= $html['BUS'] ?>
                    </div>
                    <div class="adm_tabl_spoiler_skrito"></div>
                </div>
            <?php
        endforeach;
        ?>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#select_gorod_otpravleniya, #select_gorod_pribitiya').select2({
        placeholder: 'Все города',
    });
});
$(document).ready(function() {
    $('#select_zapisi').select2({
        placeholder: 'Все',
        width: 'style',
    });
});
$(document).ready(function() {
    $('#select_istochn').select2({
        placeholder: 'Все источники',
        width: 'style',
    });
});
</script>
