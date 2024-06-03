<?php

// Copyright (c) 2017 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg, GPLv3, see LICENSE

/**
 * GUI for OER publishing functions
 *
 * @ilCtrl_IsCalledBy ilOERinFormPublishGUI: ilUIPluginRouterGUI
 * @ilCtrl_Calls ilOERinFormPublishGUI: ilOERinFormPublishWizardGUI
 */
class ilOERinFormPublishGUI extends ilOERinFormBaseGUI
{

    protected int $parent_ref_id;
    protected string $parent_type;
    protected string $parent_gui_class;
    protected ?ilObject $parent_obj;
    protected ilOERinFormPublishMD $md_obj;
    protected ilLocatorGUI $locator;

    /**
     * constructor.
     * todo: remove GET access
     */
    public function __construct()
    {
        parent::__construct();

        global $DIC;
        $this->locator = $DIC['ilLocator'];

        $this->parent_ref_id = (int) $_GET['ref_id'];
        $this->parent_type = ilObject::_lookupType($this->parent_ref_id, true);
        $this->parent_obj = ilObjectFactory::getInstanceByRefId($this->parent_ref_id);
        $this->parent_gui_class = ilObjectFactory::getClassByType($this->parent_type) . 'GUI';

        $this->md_obj = new ilOERinFormPublishMD($this->parent_obj->getId(), $this->parent_obj->getId(), $this->parent_type);
    }


    /**
    * Handles all commands
    */
    public function executeCommand(): void
    {
        $this->ctrl->saveParameter($this, 'ref_id');

        $fallback_url = "goto.php?target=" . $this->parent_type . '_' . $this->parent_ref_id;

        if (!$this->access->checkAccess('write', '', $_GET['ref_id'])) {
            $this->tpl->setOnScreenMessage('failure', $this->lng->txt("permission_denied"), true);
            $this->ctrl->redirectToURL($fallback_url);
        }

        $this->ctrl->saveParameter($this, 'ref_id');
        $cmd = $this->ctrl->getCmd('showHelp');

        $next_class = $this->ctrl->getNextClass($this);

        switch ($next_class) {
            case 'iloerinformpublishwizardgui':
                $this->prepareOutput();
                $wizard_gui = new ilOERinFormPublishWizardGUI(
                    $this,
                    $this->parent_obj
                );
                $this->ctrl->forwardCommand($wizard_gui);
                break;

            default:
                switch ($cmd) {
                    case "publish":
                    case "republish":
                    case "unpublish":
                        $this->$cmd();
                        break;

                    default:
                        $this->tpl->setOnScreenMessage('failure', $this->lng->txt("permission_denied"), true);
                        $this->ctrl->redirectToURL($fallback_url);
                        break;
                }
        }
    }

    /**
     * Prepare the test header, tabs etc.
     */
    protected function prepareOutput(): void
    {
        $this->locator->addRepositoryItems($this->parent_obj->getRefId());
        $this->locator->addItem($this->parent_obj->getTitle(), ilLink::_getLink($this->parent_ref_id, $this->parent_type));

        // see https://github.com/ILIAS-eLearning/ILIAS/commit/0c199948c24dc454f36d6dc3fca3765dfa39e5a4#diff-cf5e03a4e2f5e094186a3fd00fa7187d6bcf86b16c8b0907ba923bd8dfdd37fe
        $this->tpl->loadStandardTemplate();
        $this->tpl->setLocator();
        $this->tpl->setTitle($this->parent_obj->getPresentationTitle());
        $this->tpl->setDescription($this->parent_obj->getLongDescription());
        $this->tpl->setTitleIcon(ilObject::_getIcon('', 'big', $this->parent_type), $this->lng->txt('obj_' . $this->parent_type));
    }


    /**
     * Reject the publishing
     */
    public function unpublish(): void
    {
        $this->md_obj->unpublish();
        $this->tpl->setOnScreenMessage('success', $this->plugin->txt('msg_meta_unpublished'), true);
        $this->ctrl->setParameter($this, 'section', $_REQUEST['section']);
        $this->returnToParent();
    }


    /**
     * Add the publishing info to the page
     */
    public function addPublishInfo(): void
    {
        $tpl = $this->plugin->getTemplate('tpl.publish_status.html');
        $tpl->setVariable('HEADER', $this->plugin->txt('publish_oer'));
        $tpl->setVariable('STATUS', $this->md_obj->getPublishInfo());
        $tpl->setVariable('HELP', $this->getHelpButton('oer_publishing'));

        if ($this->md_obj->getPublishStatus() == ilOERinFormPublishMD::STATUS_PUBLIC) {
            $keywords = $this->md_obj->getKeywords();
            if (!empty($keywords)) {
                $tpl->setVariable('LABEL_KEYWORDS', $this->plugin->txt('label_keywords'));
                $tpl->setVariable('KEYWORDS', $keywords);
            }

            $authors = $this->md_obj->getAuthors();
            if (!empty($keywords)) {
                $tpl->setVariable('LABEL_AUTHORS', $this->plugin->txt('label_authors'));
                $tpl->setVariable('AUTHORS', $authors);
            }

            $copyright = $this->md_obj->getCopyrightDescription();
            if (!empty($copyright)) {
                $tpl->setVariable('LICENSE', $copyright);
            }

        }

        $this->ctrl->setParameter($this, 'return', urlencode($_SERVER['SCRIPT_NAME'] . '?' . $_SERVER['QUERY_STRING']));

        switch ($this->md_obj->getPublishStatus()) {
            case ilOERinFormPublishMD::STATUS_PRIVATE:
            case ilOERinFormPublishMD::STATUS_READY:
                $button = $this->factory->button()->standard($this->plugin->txt('publish'), $this->ctrl->getLinkTargetByClass(array('ilUIPluginRouterGUI', 'ilOERinFormPublishGUI', 'ilOERinFormPublishWizardGUI')));
                $tpl->setVariable('PUBLISH', $this->renderer->render($button));
                break;

            case ilOERinFormPublishMD::STATUS_PUBLIC:
                $button = $this->factory->button()->standard($this->plugin->txt('republish'), $this->ctrl->getLinkTargetByClass(array('ilUIPluginRouterGUI', 'ilOERinFormPublishGUI', 'ilOERinFormPublishWizardGUI')));
                $tpl->setVariable('REPUBLISH', $this->renderer->render($button));
                break;

            case ilOERinFormPublishMD::STATUS_BROKEN:
                $button = $this->factory->button()->standard($this->plugin->txt('unpublish'), $this->getLinkTarget('unpublish'));
                $tpl->setVariable('UNPUBLISH', $this->renderer->render($button));
                break;
        }

        $this->tpl->setRightContent($tpl->get());
    }

    /**
     * Get the HTML code of a help button for a screen
     */
    public function getHelpButton(string $a_help_id) : string
    {
        $url = $this->config->get($a_help_id);
        if (!empty($url)) {
            $link = $this->factory->link()->standard('➜ ' . $this->lng->txt($a_help_id), $url)
                ->withOpenInNewViewport(true);
            return $this->renderer->render($link);
        }
        return '';
    }

    /**
     * Return to the parent GUI
     */
    protected function returnToParent(): void
    {
        $this->ctrl->redirectToURL($_GET['return']);
    }
}
