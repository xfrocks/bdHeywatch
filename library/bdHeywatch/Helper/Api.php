<?php

class bdHeywatch_Helper_Api
{
    public static function getContainerFromDynamicFormatId($format)
    {
        // http://www.heywatchencoding.com/devcenter/format-usage-made-easier-with-dynamic-format-id
        static $containerAliases = array(
            'mp4' => array(
                'ios',
                'iphone',
                'ipad',
                'ipod',
                'android'
            ),
            'avi' => array(
                'divx',
                'xvid'
            ),
            'asf' => array('wmv'),
            'mpegts' => array('hls'),
            'mov' => array('dnxhd'),
            'flv' => array('flash'),
            'ogg' => array('theora'),
            'webm' => array('matroska'),
        );

        $parts = explode('_', $format);
        $container = array_shift($parts);

        if (strpos($container, ':') !== false) {
            // vcodec and/or acodec are included
            $containerParts = explode(':', $container);
            $container = array_shift($containerParts);
        }

        foreach ($containerAliases as $_container => $aliases) {
            foreach ($aliases as $alias) {
                if ($container === $alias) {
                    $container = $_container;
                }
            }
        }

        return $container;
    }

    public static function getHeightFromDynamicFormatId($format)
    {
        $height = 0;
        $parts = explode('_', $format);

        foreach ($parts as $part) {
            if (preg_match('/^(\d+)(p|i)$/', $part, $matches)) {
                $height = intval($matches[1]);
            } elseif (preg_match('/^(\d+)x(\d+)$/', $part, $matches)) {
                $height = intval($matches[2]);
            }
        }

        return $height;
    }

    public static function prepareConfigParams($url, $fileName, $outputFormats, $params = array())
    {
        $configParams = array(
            // http://www.heywatchencoding.com/docs/api/config/settings
            'source' => $url,

            // http://www.heywatchencoding.com/docs/tutorials/receiving-webhooks
            'webhook' => sprintf('%s, metadata=true', self::buildPingUrl($params)),

            'variables' => array(

                // output prefix to be used in all outputs later
                'outputPrefix' => self::getOutputPrefix($params),

            ),

            'outputs' => array(),
        );
        $configOutputs =& $configParams['outputs'];

        if (true) {
            // animation http://www.heywatchencoding.com/docs/api/config/previews
            $configOutputs['gif_400'] = self::getOutput($url, $fileName, 'gif');
        }

        $containerAndHeights = array();
        foreach (array_unique($outputFormats) as $outputFormat) {
            list($format, $formatParams) = self::_parseOutputFormat($outputFormat);
            if (empty($format)) {
                continue;
            }

            // video http://www.heywatchencoding.com/docs/api/config/video-encoding
            $formatContainer = self::getContainerFromDynamicFormatId($format);
            $configOutputs[$format] = self::getOutput($url, $fileName, $format, $formatContainer);

            $formatHeight = self::getHeightFromDynamicFormatId($format);
            if ($formatHeight == 0) {
                if (!isset($formatParams['keep'])) {
                    $formatParams['keep'] = 'resolution';
                }
            } else {
                if (!isset($formatParams['if'])) {
                    $formatParams['if'] = sprintf('$source_height >= %d', $formatHeight);
                }
            }

            foreach ($formatParams as $formatParamKey => $formatParamValue) {
                $configOutputs[$format] .= sprintf(', %s=%s', $formatParamKey, $formatParamValue);
            }

            $containerAndHeights[$formatContainer][] = $formatHeight;
        }

        foreach ($containerAndHeights as $formatContainer => $formatHeights) {
            sort($formatHeights);
            $formatHeight = array_shift($formatHeights);

            if ($formatHeight > 0) {
                // the smallest height is non-zero...
                // output one additional video in this format if the video is too small
                // we have to do this to make sure all format has at least one output
                $configOutputs[$formatContainer] = sprintf(
                    '%s, keep=resolution, if=$source_height < %d',
                    self::getOutput($url, $fileName, $formatContainer),
                    $formatHeight
                );
            }
        }

        return $configParams;
    }

    public static function buildConfig(array $array)
    {
        $lines = array();

        foreach ($array as $key => $value) {
            switch ($key) {
                case 'variables':
                    foreach ($value as $varKey => $varValue) {
                        $lines[] = sprintf('var %s = %s', $varKey, $varValue);
                    }
                    $lines[] = '';
                    break;
                case 'outputs':
                    foreach ($value as $outputKey => $outputValue) {
                        $lines[] = sprintf('-> %s = %s', $outputKey, $outputValue);
                    }
                    $lines[] = '';
                    break;
                default:
                    $lines[] = sprintf('set %s = %s', $key, $value);
            }
        }

        return implode("\n", $lines);
    }

    public static function createJob($config)
    {
        return self::_request('job', $config);
    }

    public static function getOutputPrefix(array $params)
    {
        $s3Key = bdHeywatch_Option::get('s3Key');
        if (empty($s3Key)) {
            throw new XenForo_Exception('s3Key not found');
        }

        $s3Secret = bdHeywatch_Option::get('s3Secret');
        if (empty($s3Secret)) {
            throw new XenForo_Exception('s3Secret not found');
        }

        $s3Bucket = bdHeywatch_Option::get('s3Bucket');
        if (empty($s3Bucket)) {
            throw new XenForo_Exception('s3Bucket not found');
        }

        if (!empty($params['outputPrefix'])) {
            $path = '/' . trim($params['outputPrefix'], '/');
        } else {
            $path = '';
        }

        return sprintf('s3://%s:%s@%s/%s%s', $s3Key, $s3Secret, $s3Bucket, date('Y/m', XenForo_Application::$time), $path);
    }

    public static function getOutput($url, $fileName, $format, $extension = null)
    {
        if ($extension === null) {
            $extension = $format;
        }

        $uniqueFileName = sprintf(
            '%s_%s.%s',
            $fileName,
            md5($url . $format . XenForo_Application::getConfig()->get('globalSalt')),
            $extension
        );

        return sprintf('$outputPrefix/%s', $uniqueFileName);
    }

    public static function buildPingUrl(array $params)
    {
        if (!empty($params['pingParams'])) {
            $pingParams = $params['pingParams'];
        } else {
            $pingParams = array();
        }

        $pingUrl = XenForo_Link::buildPublicLink('canonical:misc/heywatch/robot-ping', '', $pingParams);

        if (XenForo_Application::debugMode()) {
            $config = XenForo_Application::getConfig();
            $pingUrl = $config->get('bdHeywatch_pingUrl');

            if (!empty($pingUrl)) {
                foreach ($pingParams as $key => $value) {
                    if (strpos($pingUrl, '?') === false) {
                        $pingUrl .= '?';
                    } else {
                        $pingUrl .= '&';
                    }
                    $pingUrl .= sprintf('%s=%s', $key, rawurlencode($value));
                }
            }
        }

        return $pingUrl;
    }

    protected static function _parseOutputFormat($outputFormat)
    {
        $parts = explode(',', $outputFormat);
        $format = utf8_trim(array_shift($parts));
        $params = array();

        foreach ($parts as $part) {
            $paramParts = explode('=', $part);
            if (count($paramParts) == 2) {
                $params[utf8_trim($paramParts[0])] = utf8_trim($paramParts[1]);
            }
        }

        return array(
            $format,
            $params
        );
    }

    protected static function _request($path, $params, $method = 'POST', $apiKey = null)
    {
        try {
            $uri = call_user_func_array('sprintf', array(
                'https://heywatch.com/api/v1/%s',
                $path,
            ));
            $client = XenForo_Helper_Http::getClient($uri);

            if (is_array($params)) {
                foreach (array_keys($params) as $paramKey) {
                    $params[$paramKey] = strval($params[$paramKey]);
                }
            }

            if ($apiKey === null) {
                $apiKey = bdHeywatch_Option::get('apiKey');
            }
            if (empty($apiKey)) {
                throw new XenForo_Exception('apiKey not found');
            }
            $client->setAuth($apiKey);

            if ($method === 'GET') {
                $client->setParameterGet($params);
                $response = $client->request('GET');
            } else {
                if (is_array($params)) {
                    $client->setParameterPost($params);
                } else {
                    $client->setRawData($params);
                }
                $response = $client->request($method);
            }

            $body = $response->getBody();
            $json = @json_decode($body, true);

            if (!is_array($json)) {
                bdHeywatch_Logger::logException(new XenForo_Exception(sprintf('Unable to parse JSON: %s', $body)));
                return false;
            }

            return array(
                $response->getStatus(),
                $json
            );
        } catch (Zend_Http_Client_Exception $e) {
            bdHeywatch_Logger::logException($e);
            return false;
        }
    }

}
