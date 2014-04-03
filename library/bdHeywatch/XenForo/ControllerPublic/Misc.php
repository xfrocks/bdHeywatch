<?php

class bdHeywatch_XenForo_ControllerPublic_Misc extends XFCP_bdHeywatch_XenForo_ControllerPublic_Misc
{
	public function actionHeywatchRobotPing()
	{
		bdHeywatch_Logger::log(0, 'callback', $_REQUEST);

		header('HTTP/1.0 200 OK');
		exit ;
	}

}
