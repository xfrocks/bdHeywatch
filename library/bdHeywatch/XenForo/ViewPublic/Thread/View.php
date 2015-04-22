<?php

class bdHeywatch_XenForo_ViewPublic_Thread_View extends XFCP_bdHeywatch_XenForo_ViewPublic_Thread_View
{
    public function renderHtml()
    {
        parent::renderHtml();

        if (!empty($this->_params['canViewAttachments']) AND !empty($this->_params['posts'])) {
            foreach ($this->_params['posts'] as &$post) {
                if (empty($post['attachments'])) {
                    continue;
                }

                foreach (array_keys($post['attachments']) as $attachmentId) {
                    if (empty($post['attachments'][$attachmentId]['bdheywatch_options'])) {
                        // empty array
                        continue;
                    }

                    if (empty($post['attachments'][$attachmentId]['bdheywatch_options']['processed'])) {
                        // not yet processed
                        continue;
                    }

                    // pick it up and render at the bottom of the post
                    $post['bdHeywatch_videos'][$attachmentId] = $post['attachments'][$attachmentId];
                    unset($post['attachments'][$attachmentId]);
                }
            }
        }
    }

}
