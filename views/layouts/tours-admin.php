<?php

use yii\helpers\Html;
use yii\widgets\Menu;

/**
 * @var $this yii\web\View
 * @var $content string
 */
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="ru-RU" data-livestyle-extension="available">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width">
    <?php echo Html::csrfMetaTags() ?>
    <title><?php echo Html::encode($this->title) ?></title>

    <link rel="stylesheet" type="text/css" href="/css/bootstrap-grid.min.css">
    <link rel="stylesheet" type="text/css" href="/datatables/datatables.min.css">
    <link rel="stylesheet" type="text/css" href="/css/style.css">
    <script src="/js/jquery-3.1.0.min.js"></script>
    <script src="/js/script.js"></script>
    <script src="/datatables/datatables.min.js"></script>

    <link href="/css/select2.min.css" rel="stylesheet"/>
    <script src="/js/select2.min.js"></script>

    <?php $this->head() ?>
</head>
<body class="adm-body">
    <?php $this->beginBody() ?>

    <div class="adm-container">
        <div class="adm-header">
            <div class="logo">Ticket</div>
        </div>
        <div class="adm-main">
            <div class="adm-sidebar">
                <?php echo Menu::widget([
                    'items' => [
                        ['label' => 'Города', 'url' => ['citys/default']],
                        ['label' => 'Направления', 'url' => ['routes/default']],
                        ['label' => 'Входящие данные', 'url' => ['view-data/default']],
                        ['label' => 'Обработка и выгрузка данных', 'url' => ['processing-data/default']],
                    ],
                    'activeCssClass' => 'active',
                    'encodeLabels' => false,
                    'linkTemplate' => '<a href="{url}"><p>{label}</p></a>',
                ]) ?>
            </div>
            <div class="adm-content">
                <?php echo $content ?>
            </div>
        </div>
    </div>

    <?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
