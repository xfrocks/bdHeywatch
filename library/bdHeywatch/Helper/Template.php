<?php

class bdHeywatch_Helper_Template
{
	public static function getMime($format)
	{
		// TODO: verify this works
		$container = bdHeywatch_Helper_Api::getContainerFromDynamicFormatId($format);
		return sprintf('video/%s', $container);
	}

}
