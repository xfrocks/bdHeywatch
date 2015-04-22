<?php

class bdHeywatch_Option
{
    public static function get($key, $subKey = null)
    {
        $options = XenForo_Application::getOptions();

        switch ($key) {
            case 'inputExtensions':
                return preg_split('/\s+/', utf8_strtolower($options->get('bdHeywatch_inputExtensions')), NULL, PREG_SPLIT_NO_EMPTY);
            case 'outputFormats':
                return preg_split('/\n/', utf8_strtolower($options->get('bdHeywatch_outputFormats')), NULL, PREG_SPLIT_NO_EMPTY);
        }

        return $options->get(sprintf('bdHeywatch_%s', $key), $subKey);
    }

}
