<?php

/**
 * Class for sending OER notifications per mime mail
 * @see \ilRegistrationMimeMailNotification
 */
class ilOERinFormMimeMailNotification extends ilMimeMailNotification
{
    public function sendPublishNotification(int $public_ref_id, string $recipient, ilObjUser $publisher): bool
    {
        $plugin = ilOERinFormPlugin::getInstance();
        $config = $plugin->getConfig();

        $subject = $config->getNotificationSubject();
        $message = $config->getNotificationMessage();
        $message = str_replace('[CONTENT_LINK]', ilLink::_getStaticLink($public_ref_id), $message);
        $message = str_replace('[PUBLISHER_NAME]', $publisher->getFullname(), $message);
        $message = str_replace('[PUBLISHER_EMAIL]', $publisher->getEmail(), $message);

        try {
            $this->initMimeMail();
            $this->handleCurrentRecipient($recipient);
            $this->setSubject($subject);
            $this->setBody($message);
            $this->appendBody("\n\n");
            $this->appendBody($this->getLanguage()->txt('reg_mail_body_3_confirmation'));
            $this->appendBody(ilMail::_getInstallationSignature());
            $this->sendMimeMail($this->getCurrentRecipient());
        } catch (ilException $e) {
            return false;
        }
        return true;
    }
}
