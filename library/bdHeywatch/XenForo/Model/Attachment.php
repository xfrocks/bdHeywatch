<?php

XenForo_Model_Attachment::$dataColumns .= ', data.bdheywatch_options';

class bdHeywatch_XenForo_Model_Attachment extends XFCP_bdHeywatch_XenForo_Model_Attachment
{
	public function prepareAttachment(array $attachment, $fetchContentLink = false)
	{
		$attachment = parent::prepareAttachment($attachment, $fetchContentLink);

		if (empty($attachment['bdheywatch_options']))
		{
			$attachment['bdheywatch_options'] = array();
		}
		else
		{
			$attachment['bdheywatch_options'] = unserialize($attachment['bdheywatch_options']);
		}

		if (empty($attachment['thumbnailUrl']) AND !empty($attachment['bdheywatch_options']['thumbnails'][0]))
		{
			// TODO: find a way to do this
			// $attachment['thumbnailUrl'] =
			// $attachment['bdheywatch_options']['thumbnails'][0];
		}

		return $attachment;
	}

	public function bdHeywatch_processCandidate(array $attachment, array $data)
	{
		$url = XenForo_Link::buildPublicLink('canonical:attachments', array_merge($attachment, $data));
		$fileName = sprintf('%d_%s', $data['data_id'], preg_replace('#[^a-zA-Z0-9_\-]#', '', $data['filename']));
		$formats = bdHeywatch_Option::get('outputFormats');

		$ini = bdHeywatch_Helper_Api::robotIni($url, $fileName, $formats, array('pingParams' => array(
				'time' => XenForo_Application::$time,
				'data_id' => $data['data_id'],
				'hash' => $this->bdHeywatch_calculateHash(XenForo_Application::$time, $data['data_id']),
			)));
		$response = bdHeywatch_Helper_Api::robotJob($ini);

		bdHeywatch_Logger::log($data['data_id'], $ini, $response);
	}

	public function bdHeywatch_processPing($dataId, XenForo_Input $input, array $json)
	{
		$jobs = $input->filterSingle('jobs', XenForo_Input::ARRAY_SIMPLE);
		$formats = array();
		$thumbnails = array();
		$width = 0;
		$height = 0;

		foreach ($jobs as $format => $jobFileName)
		{
			if (!empty($json['ping']['data']['output_urls']))
			{
				foreach ($json['ping']['data']['output_urls'] as $jobId => $outputUrl)
				{
					if ($jobId == 'post:preview/thumbnails')
					{
						$thumbnails = array_values($outputUrl);
					}
					elseif (is_string($outputUrl) AND basename($outputUrl) == $jobFileName)
					{
						$formats[$format] = $outputUrl;
					}
				}
			}
		}

		if (!empty($json['ping']['data']['task_results']))
		{
			foreach ($json['ping']['data']['task_results'] as $taskResult)
			{
				if (isset($taskResult['get:video']))
				{
					// found our [get:video] job, parse it to find the original width and height
					$getVideo = $taskResult['get:video'];

					if (!empty($getVideo['specs']['video']['width']) AND !empty($getVideo['specs']['video']['height']))
					{
						$width = $getVideo['specs']['video']['width'];
						$height = $getVideo['specs']['video']['height'];
					}
				}
			}
		}

		if (!empty($formats))
		{
			$dw = XenForo_DataWriter::create('XenForo_DataWriter_AttachmentData');
			$dw->setExistingData($dataId);
			$dw->set('bdheywatch_options', array(
				'formats' => $formats,
				'thumbnails' => $thumbnails,
				'width' => $width,
				'height' => $height,
			));
			$dw->save();
		}
	}

	public function bdHeywatch_calculateHash($time, $dataId)
	{
		return md5($time . $dataId . XenForo_Application::getConfig()->get('globalSalt'));
	}

}
