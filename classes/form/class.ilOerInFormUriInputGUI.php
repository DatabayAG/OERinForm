<?php

/**
 * Extension of ilUriInput to allow empty values
 */
class ilOERInFormUriInputGUI extends ilUriInputGUI
{
    public function checkInput(): bool
    {
        if (!$this->getRequired() && trim($this->str($this->getPostVar())) == '') {
            return true;
        }

        return parent::checkInput();
    }
}
