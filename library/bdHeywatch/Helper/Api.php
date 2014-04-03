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

	public static function robotIni($url, $fileName, $formats, $params = array())
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

		$ini['robot:env']['output_url'] = sprintf('s3://%s:%s@%s/${post:download::title}', $s3Key, $s3Secret, $s3Bucket);
		$ini['robot:env']['filename'] = $fileName;

		$ini['post:download']['url'] = $url;
		$ini['post:download']['title'] = '${robot:env::filename}';

		foreach (array_values($formats) as $i => $format)
		{
			$ini['post:download']['post:job:' . $i]['video_id'] = '${post:download:ping::video_id}';
			$ini['post:download']['post:job:' . $i]['format_id'] = $format;
			$ini['post:download']['post:job:' . $i]['output_url'] = sprintf('${robot:env::output_url}/%s/${robot:env::filename}.%s', $format, self::getContainerFromDynamicFormatId($format));
		}

		$ini['robot:ping']['url'] = XenForo_Link::buildPublicLink('canonical:misc/heywatch/robot-ping');

		return self::robotIniFromArray($ini);
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
