<?php

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
        $this->md_obj = new ilOERinFormPublishMD($this->parent_obj->getId(), $this->parent_obj->getId(), $this->parent_type);
    }


    /**
    * Handles all commands
    */
    public function executeCommand(): void
    {
        $this->ctrl->saveParameter($this, 'ref_id');

        if (!$this->access->checkAccess('write', '', $this->parent_ref_id))
        {
            $this->tpl->setOnScreenMessage('failure', $this->lng->txt("permission_denied"), true);
            $this->ctrl->redirectToURL(ilLink::_getLink($this->parent_ref_id));
        }

        $this->ctrl->saveParameter($this, 'ref_id');
        $cmd = $this->ctrl->getCmd('showHelp');

        $next_class = $this->ctrl->getNextClass($this);

        switch ($next_class) {
            case 'iloerinformpublishwizardgui':
                $this->prepareOutput();
                $wizard_gui = new ilOERinFormPublishWizardGUI($this);
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

        $this->tpl->loadStandardTemplate();
        $this->tpl->setLocator();
        $this->tpl->setTitle($this->parent_obj->getPresentationTitle());
        $this->tpl->setDescription($this->parent_obj->getLongDescription());
        $this->tpl->setTitleIcon(ilObject::_getIcon(0, 'big', $this->parent_type), $this->lng->txt('obj_' . $this->parent_type));
    }


    /**
     * Reject the publishing
     */
    public function unpublish(): void
    {
        $this->md_obj->unpublish();
        $this->tpl->setOnScreenMessage('success', $this->plugin->txt('msg_meta_unpublished'), true);
        $this->ctrl->setParameter($this, 'section', $_REQUEST['section']);
        $this->returnToExport();
    }


    /**
     * Add the publishing info to the export page
     * @todo: remove GET
     */
    public function addPublishInfo(): void
    {
        $type = ilObject::_lookupType($_GET['ref_id'], true);
        if (!$this->plugin->isAllowedType($type)) {
            return;
        }

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

        $this->ctrl->saveParameter($this, 'ref_id');
        $this->ctrl->setParameter($this, 'return', urlencode($_SERVER['SCRIPT_NAME'] . '?' . $_SERVER['QUERY_STRING']));

        switch ($this->md_obj->getPublishStatus()) {
            case ilOERinFormPublishMD::STATUS_PRIVATE:
            case ilOERinFormPublishMD::STATUS_READY:
                $button = $this->factory->button()->standard($this->plugin->txt('publish'),
                    $this->ctrl->getLinkTargetByClass(['ilUIPluginRouterGUI', 'ilOERinFormPublishGUI', 'ilOERinFormPublishWizardGUI']));
                $tpl->setVariable('PUBLISH', $this->renderer->render($button));
                break;

            case ilOERinFormPublishMD::STATUS_PUBLIC:
                $button = $this->factory->button()->standard($this->plugin->txt('republish'),
                    $this->ctrl->getLinkTargetByClass(['ilUIPluginRouterGUI', 'ilOERinFormPublishGUI', 'ilOERinFormPublishWizardGUI']));
                $tpl->setVariable('REPUBLISH', $this->renderer->render($button));
                break;

            case ilOERinFormPublishMD::STATUS_BROKEN:
                $button = $this->factory->button()->standard($this->plugin->txt('unpublish'),
                    $this->getLinkTarget('unpublish'));
                $tpl->setVariable('UNPUBLISH', $this->renderer->render($button));
                break;
        }

        $this->tpl->setRightContent($tpl->get());
    }


}
