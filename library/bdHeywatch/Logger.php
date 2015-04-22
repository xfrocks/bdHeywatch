<?php

class bdHeywatch_Logger
{
    public static function log($dataId, $sent, $received)
    {
        XenForo_Application::getDb()->insert('xf_bdheywatch_log', array(
            'log_date' => XenForo_Application::$time,
            'data_id' => $dataId,
            'sent' => serialize($sent),
            'received' => serialize($received),
        ));
    }

    public static function logException($e)
    {
        XenForo_Error::logException($e, false, 'bdHeywatch');
    }

}
