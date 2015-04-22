<?php

class bdHeywatch_Helper_Template
{
    public static function getBestVideoHeight($formats)
    {
        $height = 0;

        if (is_array($formats)) {
            foreach ($formats as $format) {
                if (!empty($format['height']) AND $format['height'] > $height) {
                    $height = $format['height'];
                }
            }
        }

        return $height;
    }

    public static function getBestViewableHeight($formats)
    {
        $visitorMaxHeight = XenForo_Visitor::getInstance()->hasPermission('general', 'bdHeywatch_maxHeight');
        if ($visitorMaxHeight == -1) {
            return XenForo_Template_Helper_Core::callHelper(strtolower('bdHeywatch_getBestVideoHeight'), array($formats));
        }

        $height = 0;
        if (is_array($formats)) {
            foreach ($formats as $format) {
                if (!empty($format['height']) AND $format['height'] <= $visitorMaxHeight AND $format['height'] > $height) {
                    $height = $format['height'];
                }
            }
        }

        return $height;
    }

    public static function getMimeType(array $format)
    {
        if (!empty($format['mime_type'])) {
            return $format['mime_type'];
        }

        if (!empty($format['format_id'])) {
            $container = bdHeywatch_Helper_Api::getContainerFromDynamicFormatId($format['format_id']);
            return sprintf('video/%s', $container);
        }

        return '';
    }

}
