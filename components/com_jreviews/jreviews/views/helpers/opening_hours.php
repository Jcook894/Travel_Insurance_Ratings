<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2016 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/
defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class OpeningHours
{
    protected $currentTime;

    protected $schedule = array(
        1 => array(),
        2 => array(),
        3 => array(),
        4 => array(),
        5 => array(),
        6 => array(),
        7 => array()
    );

    /**
     * This is a processed version of the provided schedule that splits periods that overlap two days (past midnight)
     * so it can be used to correctly identify if the business is open
     * @var array
     */
    protected $scheduleReal = array(
        1 => array(),
        2 => array(),
        3 => array(),
        4 => array(),
        5 => array(),
        6 => array(),
        7 => array()
    );

    public $daysStatus = array();

    protected $today;

    protected $dayKey = 'day';

    protected $startKey = 'hours-start';

    protected $endKey = 'hours-end';

    protected $timezone;

    protected $timeformat = 'g:i a';

    public function __construct($timezone = null)
    {
        $this->timezone = $timezone;

        if (!$this->timezone)
        {
            $this->timezone = (new DateTime('NOW'))->getTimezone();
        }
        else {
            $this->timezone = new DateTimeZone($this->timezone);
        }

        $this->currentTime = new DateTime('NOW', $this->timezone);

        $this->today = $this->currentTime->format('N');

        return $this;
    }

    public function addPeriods($periods)
    {
        foreach ($periods AS $period)
        {
            $this->addPeriod($period[$this->dayKey], $period[$this->startKey], $period[$this->endKey]);
        }

        return $this;
    }

    public function addPeriod($day, $start, $end)
    {
        $this->schedule[$day][] = array(
            'start' => self::convertHourstoHHMM($start),
            'end' => self::convertHourstoHHMM($end)
        );

        if ($end < $start)
        {
            $this->scheduleReal[$day][] = array(
                'start' => self::convertHourstoHHMM($start),
                'end' => self::convertHourstoHHMM('24.0')
            );

            $this->scheduleReal[$day+1][] = array(
                'start' => self::convertHourstoHHMM('0.0'),
                'end' => self::convertHourstoHHMM($end)
            );
        }
        else {
            $this->scheduleReal[$day][] = array(
                'start' => self::convertHourstoHHMM($start),
                'end' => self::convertHourstoHHMM($end)
            );
        }

        return $this;
    }

    public function getOrderedPeriods()
    {
        $output = array();

        $format = $this->timeformat;

        foreach ($this->schedule as $day => $periods)
        {
            $periods = & $this->schedule[$day];

            $start = array();

            foreach ($periods as $key => $period) {
                $start[$key] = $period['start'];
            }

            array_multisort($start, SORT_ASC, $periods);

            foreach ($periods AS $period)
            {
                $output[$day][] = date($format, strtotime($period['start'])) . ' - ' . date($format, strtotime($period['end']));
            }
        }

        return $output;
    }

    public function twentyfour($use = false)
    {
        if ($use == true)
        {
            $this->setTimeFormat('H:i');
        }

        return $this;
    }

    public function setTimeFormat($format)
    {
        $this->timeformat = $format;

        return $this;
    }

    /**
     * Displays the open/close status for a particular day, only if it's currently that day
     * @param  integer $day day of the week in numeric format
     * @return mixed      boolean false if day is not a match, otherwise string "open" or "closed"
     */
    public function showDayStatus($day)
    {
        $status = false;

        if ($this->today == $day)
        {
            $status = $this->isOpenDay($day, $this->currentTime) ? 'open' : 'closed';
        }

        return $status;
    }

    public function isOpenNow()
    {
        return $this->isOpen($this->currentTime);
    }

    public function isOpen($currentTime)
    {
        $status = $this->isOpenDay($this->today, $currentTime);

        return $status;
    }

    protected function isOpenDay($day, $currentTime)
    {
        $status = false;

        if (isset($this->scheduleReal[$day]))
        {
            foreach ($this->scheduleReal[$day] as $hours)
            {
                $startTime = DateTime::createFromFormat('H:i', $hours['start'], $this->timezone);

                $endTime   = DateTime::createFromFormat('H:i', $hours['end'], $this->timezone);

                if ($endTime < $startTime)
                {
                    $endTime = $endTime->modify('+1 day');
                }

                if ($startTime <= $currentTime && $currentTime <= $endTime)
                {
                    $status = true;
                    break;
                }

                // prx('-----' . $day . '-----', $currentTime, $startTime, $endTime);
            }

            return $status;
        }

        return false;
    }

    static public function convertHourstoHHMM($time)
    {
        $hour = floor($time);

        $minutes = ($time - $hour)*60;

        return str_pad($hour, 2, '0', STR_PAD_LEFT).':'.str_pad($minutes, 2, '0', STR_PAD_LEFT);
    }
}