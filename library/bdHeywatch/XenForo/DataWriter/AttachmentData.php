<?php

class bdHeywatch_XenForo_DataWriter_AttachmentData extends XFCP_bdHeywatch_XenForo_DataWriter_AttachmentData
{
	protected function _getFields()
	{
		$fields = parent::_getFields();

		$fields['xf_attachment_data']['bdheywatch_options'] = array(
			'type' => XenForo_DataWriter::TYPE_SERIALIZED,
			'default' => 'a:0:{}',
		);

		return $fields;
	}

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
