<?php

class bdHeywatch_XenForo_DataWriter_AttachmentData extends XFCP_bdHeywatch_XenForo_DataWriter_AttachmentData
{

	protected function _postSaveAfterTransaction()
	{
		$fileName = $this->get('filename');
		$extension = XenForo_Helper_File::getFileExtension($fileName);
		if (in_array($extension, bdHeywatch_Option::get('inputExtensions')))
		{
			bdHeywatch_Shared::$dataCandidates[$this->get('data_id')] = $this->getMergedData();
		}

		return parent::_postSaveAfterTransaction();
	}

}
