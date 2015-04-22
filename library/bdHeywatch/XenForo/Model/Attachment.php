<?php

XenForo_Model_Attachment::$dataColumns .= ', data.bdheywatch_options';

class bdHeywatch_XenForo_Model_Attachment extends XFCP_bdHeywatch_XenForo_Model_Attachment
{
    public function prepareAttachment(array $attachment, $fetchContentLink = false)
    {
        $attachment = parent::prepareAttachment($attachment, $fetchContentLink);

        if (empty($attachment['bdheywatch_options'])) {
            $attachment['bdheywatch_options'] = array();
        } elseif (!is_array($attachment['bdheywatch_options'])) {
            $attachment['bdheywatch_options'] = unserialize($attachment['bdheywatch_options']);
        }

        if (empty($attachment['thumbnailUrl']) AND !empty($attachment['bdheywatch_options']['thumbnails'][0])) {
            if (method_exists('bdImage_Integration', 'buildThumbnailLink')) {
                $attachment['thumbnailUrl'] = call_user_func(
                    array('bdImage_Integration', 'buildThumbnailLink'),
                    $attachment['bdheywatch_options']['thumbnails'][0],
                    XenForo_Application::getOptions()->get('attachmentThumbnailDimensions')
                );
            }
        }

        return $attachment;
    }

    public function bdHeywatch_processCandidate(array $attachment, array $data)
    {
        $url = XenForo_Link::buildPublicLink('canonical:attachments', array_merge($attachment, $data));
        $fileName = sprintf('%d_%s', $data['data_id'], preg_replace('#[^a-zA-Z0-9_\-]#', '', $data['filename']));
        $outputFormats = bdHeywatch_Option::get('outputFormats');

        $configParams = bdHeywatch_Helper_Api::prepareConfigParams($url, $fileName, $outputFormats, array(
            'pingParams' => array(
                'time' => XenForo_Application::$time,
                'data_id' => $data['data_id'],
                'hash' => $this->bdHeywatch_calculateHash(XenForo_Application::$time, $data['data_id']),
            ),
        ));
        $config = bdHeywatch_Helper_Api::buildConfig($configParams);
        $response = bdHeywatch_Helper_Api::createJob($config);

        bdHeywatch_Logger::log($data['data_id'], $configParams, $response);

        /** @var bdHeywatch_XenForo_DataWriter_AttachmentData $dw */
        $dw = XenForo_DataWriter::create('XenForo_DataWriter_AttachmentData');
        $dw->setExistingData($data, true);
        $dw->bdHeywatch_updateOptions(array('configParams' => $configParams));
        $dw->save();
    }

    public function bdHeywatch_processPing($dataId, XenForo_Input $input, array $json)
    {
        $formats = array();
        $thumbnails = array();
        $width = 0;
        $height = 0;

        // robot api (deprecated), kept for backward compatibility
        if (!empty($json['ping']['data']['output_urls'])) {
            foreach ($json['ping']['data']['output_urls'] as $taskId => $outputUrl) {
                if (strpos($taskId, 'post:preview/') === 0) {
                    if (is_array($outputUrl)) {
                        $thumbnails = array_merge($thumbnails, $outputUrl);
                    } else {
                        $thumbnails[] = $outputUrl;
                    }
                } elseif (strpos($taskId, 'post:job:') === 0) {
                    $formats[$taskId] = array('output_url' => $outputUrl);
                }
            }
        }

        // robot api (deprecated), kept for backward compatibility
        if (!empty($json['ping']['data']['task_results'])) {
            foreach ($json['ping']['data']['task_results'] as $taskResults) {
                foreach ($taskResults as $taskId => $taskResult) {
                    if ($taskId === 'get:video') {
                        if (!empty($taskResult['specs']['video']['width']) AND !empty($taskResult['specs']['video']['height'])) {
                            $width = doubleval($taskResult['specs']['video']['width']);
                            $height = doubleval($taskResult['specs']['video']['height']);
                        }
                    } elseif (strpos($taskId, 'post:job:') === 0) {
                        $taskId = preg_replace('/:ping$/', '', $taskId);

                        if (!empty($taskResult['format_id'])) {
                            $formats[$taskId]['format_id'] = $taskResult['format_id'];
                            $formats[$taskId]['height'] = bdHeywatch_Helper_Api::getHeightFromDynamicFormatId($taskResult['format_id']);
                        }
                    }
                }
            }
        }

        // 2015-01-08 api
        if (!empty($json['output_urls'])) {
            foreach ($json['output_urls'] as $outputId => $outputUrl) {
                if (strpos($outputId, 'gif') === 0) {
                    $thumbnails[] = $outputUrl;
                } else {
                    $formats[$outputId] = array(
                        'output_url' => $outputUrl,
                    );
                }
            }
        }

        // 2015-04-07 api
        if (!empty($json['metadata'])) {
            foreach ($json['metadata'] as $metadataId => $metadata) {
                if ($metadataId === 'source') {
                    if (!empty($metadata['streams']['video']['width']) && !empty($metadata['streams']['video']['height'])) {
                        $width = doubleval($metadata['streams']['video']['width']);
                        $height = doubleval($metadata['streams']['video']['height']);
                    }
                } elseif (isset($formats[$metadataId])) {
                    if (!empty($metadata['format']['name'])) {
                        $formats[$metadataId]['format_id'] = $metadata['format']['name'];
                    }

                    if (!empty($metadata['format']['mime_type'])) {
                        $formats[$metadataId]['mime_type'] = $metadata['format']['mime_type'];
                    }

                    if (!empty($metadata['streams']['video'])) {
                        $formats[$metadataId] = array_merge($formats[$metadataId], $metadata['streams']['video']);
                    }
                }
            }
        }

        foreach ($formats as &$formatRef) {
            if (empty($formatRef['height'])) {
                $formatRef['width'] = $width;
                $formatRef['height'] = $height;
            }

            // TODO: support HTTPS installation?
        }

        uasort($formats, create_function('$a, $b', 'return $a["height"] - $b["height"];'));

        if (!empty($formats)) {
            /** @var bdHeywatch_XenForo_DataWriter_AttachmentData $dw */
            $dw = XenForo_DataWriter::create('XenForo_DataWriter_AttachmentData');
            $dw->setExistingData($dataId);
            $dw->bdHeywatch_updateOptions(array(
                'processed' => true,
                'formats' => $formats,
                'thumbnails' => $thumbnails,
                'width' => $width,
                'height' => $height,
            ));
            $dw->save();
        }
    }

    public function bdHeywatch_processDeletion($options)
    {
        if (!empty($options['thumbnails'])) {
            foreach ($options['thumbnails'] as $thumbnail) {
                $this->_bdHeywatch_deleteOutputUrl($thumbnail);
            }
        }

        if (!empty($options['formats'])) {
            foreach ($options['formats'] as $format) {
                if (is_string($format)) {
                    // old version store output url directly
                    $this->_bdHeywatch_deleteOutputUrl($format);
                } elseif (!empty($format['output_url'])) {
                    $this->_bdHeywatch_deleteOutputUrl($format['output_url']);
                }
            }
        }
    }

    public function bdHeywatch_calculateHash($time, $dataId)
    {
        return md5($time . $dataId . XenForo_Application::getConfig()->get('globalSalt'));
    }

    protected function _bdHeywatch_deleteOutputUrl($outputUrl)
    {
        if (preg_match('#//(?<bucket>.+)\.s3\.amazonaws\.com/(?<path>.+)$#', $outputUrl, $matches)) {
            $bucket = $matches['bucket'];
            $path = $matches['path'];

            $s3Bucket = bdHeywatch_Option::get('s3Bucket');
            if ($s3Bucket !== $bucket) {
                throw new XenForo_Exception(sprintf('S3 buckets mismatched %s and %s', $bucket, $s3Bucket));
            }

            $connection = new Zend_Service_Amazon_S3(bdHeywatch_Option::get('s3Key'), bdHeywatch_Option::get('s3Secret'));
            return $connection->removeObject($bucket . '/' . $path);
        }

        throw new XenForo_Exception(sprintf('Unrecognized output url %s', $outputUrl));
    }

}
