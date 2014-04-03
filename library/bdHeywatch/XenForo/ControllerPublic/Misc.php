<?php

class bdHeywatch_XenForo_ControllerPublic_Misc extends XFCP_bdHeywatch_XenForo_ControllerPublic_Misc
{
	public function actionHeywatchRobotPing()
	{
		$this->_assertPostOnly();

		$input = $this->_input->filter(array(
			'time' => XenForo_Input::UINT,
			'data_id' => XenForo_Input::UINT,
			'hash' => XenForo_Input::STRING,
		));

		if ($this->getModelFromCache('XenForo_Model_Attachment')->bdHeywatch_calculateHash($input['time'], $input['data_id']) !== $input['hash'])
		{
			bdHeywatch_Logger::logException(new XenForo_Exception('Hashes do not match'));
			return $this->responseNoPermission();
		}

		$raw = file_get_contents('php://input');
		$json = @json_decode($raw, true);
		if (empty($json))
		{
			bdHeywatch_Logger::logException(new XenForo_Exception(sprintf('Unable to parse JSON: %s', $raw)));
			return $this->responseNoPermission();
		}

		bdHeywatch_Logger::log($input['data_id'], 'callback', array(
			'_REQUEST' => $_REQUEST,
			'json' => $json
		));

		$this->getModelFromCache('XenForo_Model_Attachment')->bdHeywatch_processPing($input['data_id'], $this->_input, $json);

		header('HTTP/1.0 200 OK');
		exit ;
	}

}
