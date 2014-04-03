<?php

class bdHeywatch_Listener
{
	public static function load_class($class, array &$extend)
	{
		static $classes = array(
			'XenForo_ControllerPublic_Misc',

			'XenForo_DataWriter_Attachment',
			'XenForo_DataWriter_AttachmentData',

			'XenForo_Model_Attachment',
		);

		if (in_array($class, $classes, true))
		{
			$extend[] = 'bdHeywatch_' . $class;
		}
	}

}
