<?php

class bdHeywatch_Listener
{
    public static function load_class($class, array &$extend)
    {
        static $classes = array(
            'XenForo_ControllerPublic_Attachment',
            'XenForo_ControllerPublic_Misc',

            'XenForo_DataWriter_Attachment',
            'XenForo_DataWriter_AttachmentData',

            'XenForo_Model_Attachment',

            'XenForo_ViewPublic_Thread_View',
        );

        if (in_array($class, $classes, true)) {
            $extend[] = 'bdHeywatch_' . $class;
        }
    }

    public static function init_dependencies(XenForo_Dependencies_Abstract $dependencies, array $data)
    {
        XenForo_Template_Helper_Core::$helperCallbacks['bdheywatch_getmime'] = array(
            'bdHeywatch_Helper_Template',
            'getMime'
        );

        XenForo_Template_Helper_Core::$helperCallbacks['bdheywatch_getbestvideoheight'] = array(
            'bdHeywatch_Helper_Template',
            'getBestVideoHeight'
        );

        XenForo_Template_Helper_Core::$helperCallbacks['bdheywatch_getbestviewableheight'] = array(
            'bdHeywatch_Helper_Template',
            'getBestViewableHeight'
        );
    }

    public static function template_hook($hookName, &$contents, array $hookParams, XenForo_Template_Abstract $template)
    {
        if ($hookName == 'page_container_head') {
            if (strpos($contents, 'bdHeywatch/video-js/video.js') !== false) {
                // found our script
                $contents .= '<link href="' . XenForo_Application::$javaScriptUrl . '/bdHeywatch/video-js/video-js.min.css" rel="stylesheet">';
                $contents .= '<script>videojs.options.flash.swf = "' . XenForo_Application::$javaScriptUrl . '/bdHeywatch/video-js/video-js.swf";</script>';
            }
        }
    }

    public static function file_health_check(XenForo_ControllerAdmin_Abstract $controller, array &$hashes)
    {
        $hashes += bdHeywatch_FileSums::getHashes();
    }
}
