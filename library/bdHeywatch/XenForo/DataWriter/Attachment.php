<?php

class bdHeywatch_XenForo_DataWriter_Attachment extends XFCP_bdHeywatch_XenForo_DataWriter_Attachment
{

	protected function _postSaveAfterTransaction()
	{
		$dataId = $this->get('data_id');
		if (isset(bdHeywatch_Shared::$dataCandidates[$dataId]))
		{
			// found a data candidate to submit 
			$data = bdHeywatch_Shared::$dataCandidates[$dataId];
			$attachment = $this->getMergedData();
			
			$this->_getAttachmentModel()->bdHeywatch_processCandidate($attachment, $data);
		}

		return parent::_postSaveAfterTransaction();
	}

}
