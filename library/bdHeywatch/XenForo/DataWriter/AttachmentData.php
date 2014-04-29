<?php

class bdHeywatch_XenForo_DataWriter_AttachmentData extends XFCP_bdHeywatch_XenForo_DataWriter_AttachmentData
{
	public function bdHeywatch_updateOptions(array $options)
	{
		$existingOptions = $this->get('bdheywatch_options');
		if (empty($existingOptions))
		{
			$existingOptions = array();
		}
		elseif (!is_array($existingOptions))
		{
			$existingOptions = unserialize($existingOptions);
		}

		$options = array_merge($existingOptions, $options);

		return $this->set('bdheywatch_options', $options);
	}

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
