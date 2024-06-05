<?php

/**
 * GUI for OER publishing functions
 *
 * @ilCtrl_IsCalledBy ilOERinFormPublishGUI: ilUIPluginRouterGUI
 * @ilCtrl_Calls ilOERinFormPublishGUI: ilOERinFormPublishWizardGUI
 */
class ilOERinFormPublishGUI extends ilOERinFormBaseGUI
{
    /**
    * Handles all commands
    */
    public function executeCommand(): void
    {
        if (!$this->access->checkAccess('write', '', $this->parent_ref_id)) {
            $this->tpl->setOnScreenMessage('failure', $this->lng->txt("permission_denied"), true);
            $this->returnToObject();
        }

        $cmd = $this->ctrl->getCmd();
        switch ($cmd) {
            case "unpublish":
                $this->$cmd();
                break;

            default:
                $this->tpl->setContent('Unknown command ' . $cmd);
                $this->tpl->printToStdout();
        }
    }


    /**
     * Reject the publishing
     */
    public function unpublish(): void
    {
        $meta = new ilOERinFormPublishMD($this->parent_obj_id, $this->parent_obj_id, $this->parent_type);
        $meta->unpublish();
        $this->tpl->setOnScreenMessage('success', $this->plugin->txt('msg_meta_unpublished'), true);
        $this->ctrl->setParameter($this, 'section', $_REQUEST['section']);
        $this->returnToExport();
    }


    /**
     * Add the publishing info to the export page
     * called from the ui hook, no controller command
     */
    public function addPublishInfo(): void
    {
        if (!$this->plugin->isAllowedType($this->parent_type)) {
            return;
        }

        $meta = new ilOERinFormPublishMD($this->parent_obj_id, $this->parent_obj_id, $this->parent_type);

        // panel with publishing data

        $listing = [
            $this->plugin->txt('label_status') =>  $meta->getPublishInfo()
        ];

        if ($meta->getPublishStatus() == ilOERinFormPublishMD::STATUS_PUBLIC) {
            $keywords = $meta->getKeywords();
            if (!empty($keywords)) {
                $listing[$this->plugin->txt('label_keywords')] = $keywords;
            }
            $authors = $meta->getAuthors();
            if (!empty($authors)) {
                $listing[$this->plugin->txt('label_authors')] = $authors;
            }
            $copyright = $meta->getCopyrightDescription();
            if (!empty($copyright)) {
                $listing[$this->plugin->txt('label_license')] = $copyright;
            }
        }

        $right_components = [
            $this->factory->panel()->standard($this->plugin->txt('publish_oer'),
                $this->factory->listing()->descriptive($listing))
        ];


        // Button with publishing action

        if ($this->access->checkAccess('write', '', $this->parent_ref_id)) {

            switch ($meta->getPublishStatus()) {
                case ilOERinFormPublishMD::STATUS_PRIVATE:
                case ilOERinFormPublishMD::STATUS_READY:
                    $this->ctrl->setParameterByClass('ilOERinFormPublishWizardGUI', 'ref_id', $this->parent_ref_id);
                    $right_components[] = $this->factory->button()->standard(
                        $this->plugin->txt('publish'),
                        $this->ctrl->getLinkTargetByClass(['ilUIPluginRouterGUI', 'ilOERinFormPublishWizardGUI'])
                    );
                    break;

                case ilOERinFormPublishMD::STATUS_PUBLIC:
                    $this->ctrl->setParameterByClass('ilOERinFormPublishWizardGUI', 'ref_id', $this->parent_ref_id);
                    $right_components[] = $this->factory->button()->standard(
                        $this->plugin->txt('republish'),
                        $this->ctrl->getLinkTargetByClass(['ilUIPluginRouterGUI', 'ilOERinFormPublishWizardGUI'])
                    );
                    break;

                case ilOERinFormPublishMD::STATUS_BROKEN:
                    $this->ctrl->saveParameter($this, 'ref_id');
                    $right_components[] = $this->factory->button()->standard(
                        $this->plugin->txt('unpublish'),
                        $this->ctrl->getLinkTargetByClass(['ilUIPluginRouterGUI', 'ilOERinFormPublishGUI'], 'unpublish')
                    );
                    break;
            }
        }

        // Help button

        if (!empty($help = $this->getHelpButton('oer_publishing'))) {
            $right_components[] = $this->factory->legacy(' &nbsp; ');
            $right_components[] = $help;
        }

        $this->tpl->setRightContent($this->renderer->render($right_components));
    }
}
