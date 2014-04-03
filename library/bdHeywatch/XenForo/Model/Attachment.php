<?php

class bdHeywatch_XenForo_Model_Attachment extends XFCP_bdHeywatch_XenForo_Model_Attachment
{
	public function bdHeywatch_processCandidate(array $attachment, array $data)
	{
		$url = XenForo_Link::buildPublicLink('canonical:attachments', array_merge($attachment, $data));
		$fileName = $attachment['attachment_id'];
		$formats = bdHeywatch_Option::get('outputFormats');

		$ini = bdHeywatch_Helper_Api::robotIni($url, $fileName, $formats);
		$response = bdHeywatch_Helper_Api::robotJob($ini);

		bdHeywatch_Logger::log($data['data_id'], $ini, $response);
	}

}
