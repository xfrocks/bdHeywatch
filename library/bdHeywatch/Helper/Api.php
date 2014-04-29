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
		);

		$parts = explode('_', $format);
		$container = array_shift($parts);

		foreach ($containerAliases as $_container => $aliases)
		{
			foreach ($aliases as $alias)
			{
				if ($container === $alias)
				{
					$container = $_container;
				}
			}
		}

		return $container;
	}

	public static function robotIniArray($url, $fileName, $formats, $params = array())
	{
		$ini = array();
		extract($params);

		if (empty($s3Key))
		{
			$s3Key = bdHeywatch_Option::get('s3Key');
		}
		if (empty($s3Key))
		{
			throw new XenForo_Exception('s3Key not found');
		}

		if (empty($s3Secret))
		{
			$s3Secret = bdHeywatch_Option::get('s3Secret');
		}
		if (empty($s3Secret))
		{
			throw new XenForo_Exception('s3Secret not found');
		}

		if (empty($s3Bucket))
		{
			$s3Bucket = bdHeywatch_Option::get('s3Bucket');
		}
		if (empty($s3Bucket))
		{
			throw new XenForo_Exception('s3Bucket not found');
		}

		if (empty($pingParams))
		{
			$pingParams = array();
		}
		$jobDirectory = date('Y/m', XenForo_Application::$time);
		$uniqueId = XenForo_Application::getConfig()->get('globalSalt') . XenForo_Application::$time;
		$pingParams['jobs'] = array();

		$ini['robot:env']['output_url'] = sprintf('s3://%s:%s@%s', $s3Key, $s3Secret, $s3Bucket);

		$ini['post:download']['url'] = $url;

		$ini['post:download']['get:video']['id'] = '${post:download:ping::video_id}';

		$format = 'thumbnail';
		$jobFileName = sprintf('%s_%s_#num#', $fileName, md5($format . $uniqueId));
		$ini['post:download']['get:video']['post:preview/thumbnails']['media_id'] = '${post:download:ping::video_id}';
		$ini['post:download']['get:video']['post:preview/thumbnails']['output_url'] = sprintf('${robot:env::output_url}/%s', $jobDirectory);
		$ini['post:download']['get:video']['post:preview/thumbnails']['filename'] = $jobFileName;
		$ini['post:download']['get:video']['post:preview/thumbnails']['width'] = '${get:video::specs.video.width}';
		$ini['post:download']['get:video']['post:preview/thumbnails']['height'] = '${get:video::specs.video.height}';

		foreach (array_values(array_unique($formats)) as $i => $format)
		{
			$jobId = sprintf('post:job:%d', $i);
			$jobFileName = sprintf('%s_%s.%s', $fileName, md5($format . $uniqueId), self::getContainerFromDynamicFormatId($format));

			$ini['post:download'][$jobId]['video_id'] = '${post:download:ping::video_id}';
			$ini['post:download'][$jobId]['format_id'] = $format;
			$ini['post:download'][$jobId]['keep_video_size'] = 'true';
			$ini['post:download'][$jobId]['output_url'] = sprintf('${robot:env::output_url}/%s/%s', $jobDirectory, $jobFileName);

			$pingParams['jobs'][$format] = $jobFileName;
		}

		$ini['robot:ping']['url'] = XenForo_Link::buildPublicLink('canonical:misc/heywatch/robot-ping', '', $pingParams);

		return $ini;
	}

	public static function robotIniFromArray(array $array, $level = 0)
	{
		$str = '';
		$padding = str_repeat(' ', $level * 2);

		foreach ($array as $section => $values)
		{
			$str .= sprintf("\n%s[%s]\n", $padding, $section);

			foreach ($values as $key => $value)
			{
				if (!is_array($value))
				{
					$str .= sprintf("%s%s = %s\n", $padding, $key, $value);
				}
				else
				{
					$str .= self::robotIniFromArray(array($key => $value), $level + 1);
				}
			}
		}

		return $str;
	}

	public static function robotJob($ini)
	{
		return self::_request('robot/job', $ini);
	}

	protected static function _request($path, $params, $method = 'POST', $apiKey = null)
	{
		try
		{
			$uri = call_user_func_array('sprintf', array(
				'https://heywatch.com/%s.json',
				$path,
			));
			$client = XenForo_Helper_Http::getClient($uri);

			if (is_array($params))
			{
				foreach (array_keys($params) as $paramKey)
				{
					$params[$paramKey] = strval($params[$paramKey]);
				}
			}

			if ($apiKey === null)
			{
				$apiKey = bdHeywatch_Option::get('apiKey');
			}
			if (empty($apiKey))
			{
				throw new XenForo_Exception('apiKey not found');
			}
			$client->setAuth('HW-API-Key', $apiKey);

			if ($method === 'GET')
			{
				$client->setParameterGet($params);
				$response = $client->request('GET');
			}
			else
			{
				if (is_array($params))
				{
					$client->setParameterPost($params);
				}
				else
				{
					$client->setRawData($params);
				}
				$response = $client->request($method);
			}

			$body = $response->getBody();
			$json = @json_decode($body, true);

			if (!is_array($json))
			{
				bdHeywatch_Logger::logException(new XenForo_Exception(sprintf('Unable to parse JSON: %s', $body)));
				return false;
			}

			return array(
				$response->getStatus(),
				$json
			);
		}
		catch (Zend_Http_Client_Exception $e)
		{
			bdHeywatch_Logger::logException($e);
			return false;
		}
	}

}
