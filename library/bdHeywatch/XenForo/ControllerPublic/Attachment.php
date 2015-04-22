<?php

class bdHeywatch_XenForo_ControllerPublic_Attachment extends XFCP_bdHeywatch_XenForo_ControllerPublic_Attachment
{
    public function actionWatch()
    {
        $attachmentId = $this->_input->filterSingle('attachment_id', XenForo_Input::UINT);
        $attachment = $this->_getAttachmentOrError($attachmentId);

        $attachmentModel = $this->_getAttachmentModel();
        if (!$attachmentModel->canViewAttachment($attachment)) {
            return $this->responseNoPermission();
        }
        $attachmentModel->logAttachmentView($attachmentId);

        $height = $this->_input->filterSingle('height', XenForo_Input::UINT);
        $this->canonicalizeRequestUrl(XenForo_Link::buildPublicLink('attachments/watch', $attachment, array('height' => $height)));

        $video = $attachmentModel->prepareAttachment($attachment);

        $viewParams = array(
            'video' => $video,
            'height' => $height,
        );

        return $this->responseView('bdHeywatch_ViewPublic_Attachment_Watch', 'bdheywatch_attachment_watch', $viewParams);
    }

}
