<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * @property string $action Наименование задачи
 * @property string $status Наименование статуса
 */
class ToursReports extends ActiveRecord
{
    private static $date_format = 'Y-m-d H:i:s';
    public static $current_report_id;
    private const DEFAULT_LABELS = [
        'started' => 'Начато',
        'processing' => 'В процессе c ',
        'finished' => 'Завершено',
        'error' => 'Завершено с ошибкой',
        'fatal-error' => 'Завершено с ошибкой',
        'stopped' => 'Выполнение прервано',
    ];
    private $label_prefix = '';

    public static function tablename()
    {
        return '{{%tours_reports}}';
    }

    public function getLabel(): string
    {
        $status = static::DEFAULT_LABELS[ $this->status ] ?? (string) $this->status;

        $time = $this->end_time ?? $this->start_time;

        $start_time = strtotime($this->start_time);
        $end_time = !empty($this->end_time) ? strtotime($this->end_time) : time();
        $duration = (int) (($end_time - $start_time) / 60);

        $label = "$status $time ($duration мин.)";

        return $label;
    }

    public function isInProcess()
    {
        return array_search($this->status, ['started', 'processing'], true);
    }

    public static function start(string $action): ToursReports
    {
        $report = new static();
        $report->start_time = date(static::$date_format);
        $report->status = 'started';
        $report->action = $action;

        switch ($action) {
            case 'create':
                $prev_create_report = static::getLastTask(['create'], 'start_time');

                if (empty($prev_create_report)) {
                    break;
                }

                $prev_create_time = strtotime($prev_create_report->start_time);
                $prev_create_date = date('Y-m-d', $prev_create_time);

                $current_create_time = strtotime($report->start_time);
                $current_create_date = date('Y-m-d', $current_create_time);

                if ($current_create_date === $prev_create_date) {
                    $report->action = 'update';
                }

                break;
            default:
                $last_update_task = static::getLastTask(['create', 'update'], 'start_time');
                $report->parse_number = $last_update_task->parse_number ?? -1;
        }

        $report->save();

        static::$current_report_id = (int) $report->id;

        if (empty($report->parse_number)) {
            $report->parse_number = $report->id;
        }

        $report->save();

        return $report;
    }

    public function changeStatus(string $status)
    {
        $this->status = $status;

        $this->save();
    }

    public function end(string $status)
    {
        $this->end_time = date(static::$date_format);
        $this->status = $status;

        $this->save();
    }

    public static function existsStartedTask(array $actions = []): bool
    {
        $cond = ['end_time' => null];

        if (!empty($actions)) {
            $cond['action'] = $actions;
        }

        return static::find()
            ->where($cond)
            ->exists();
    }

    /**
     * @param ?string[] $actions
     * @param string $orderBy
     * @return ToursReports|null
     */
    public static function getLastTask(array $actions = null, string $orderBy = 'id'): ?ToursReports
    {
        $cond = [];

        if (!empty($actions)) {
            $cond['action'] = $actions;
        }

        return static::find()
            ->where($cond)
            ->orderBy([$orderBy => SORT_DESC])
            ->limit(1)
            ->one();
    }

    public function getPercentProgress(): float
    {
        if (empty($this->target)) {
            return 0;
        }

        return round((int) $this->progress * 100 / (int) $this->target, 2);
    }

    public function getProgressFromTotal(): string
    {
        return $this->progress . ' / ' . $this->target;
    }

    public function updateProgress($progress_event)
    {
        $this->progress = $progress_event->result['progress'] ?? $this->progress ?? 0;
        $this->target = $progress_event->result['target'] ?? $this->target ?? 0;
        $this->save(false);
    }
}
