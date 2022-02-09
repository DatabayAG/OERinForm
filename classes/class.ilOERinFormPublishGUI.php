<?php
// Copyright (c) 2017 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg, GPLv3, see LICENSE

require_once('Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/OERinForm/classes/class.ilOERinFormBaseGUI.php');

/**
 * GUI for OER publishing functions
 *
 * @author Fred Neumann <fred.neumann@fau.de>
 * @version $Id$
 *
 * @ilCtrl_IsCalledBy ilOERinFormPublishGUI: ilUIPluginRouterGUI
 * @ilCtrl_Calls ilOERinFormPublishGUI: ilOERinFormPublishWizardGUI
 */
class ilOERinFormPublishGUI extends ilOERinFormBaseGUI
{

	/** @var  int parent object ref_id */
	protected $parent_ref_id;

	/** @var  string parent object type */
	protected $parent_type;

	/** @var  string parent gui class */
	protected $parent_gui_class;

	/** @var  ilObject $parent_obj */
	protected $parent_obj;

	/** @var  ilOERinFormPublishMD $md_obj */
	protected $md_obj;

	/**
	 * constructor.
	 */
	public function __construct()
	{
		parent::__construct();

		$this->ctrl->saveParameter($this, 'ref_id');

		$this->parent_ref_id = $_GET['ref_id'];
		$this->parent_type = ilObject::_lookupType($this->parent_ref_id, true);
		$this->parent_obj = ilObjectFactory::getInstanceByRefId($this->parent_ref_id);
		$this->parent_gui_class = ilObjectFactory::getClassByType($this->parent_type).'GUI';

		$this->plugin->includeClass('class.ilOERinFormPublishMD.php');
		$this->md_obj = new ilOERinFormPublishMD($this->parent_obj->getId(), $this->parent_obj->getId(), $this->parent_type);
		$this->md_obj->setPlugin($this->plugin);
    }


	/**
	* Handles all commands
	*/
	public function executeCommand()
	{
		$fallback_url = "goto.php?target=".$this->parent_type.'_'.$this->parent_ref_id;

		if (!$this->access->checkAccess('write','', $_GET['ref_id']))
		{
            ilUtil::sendFailure($this->lng->txt("permission_denied"), true);
            ilUtil::redirect($fallback_url);
		}

		$this->ctrl->saveParameter($this, 'ref_id');
		$cmd = $this->ctrl->getCmd('showHelp');

		$next_class = $this->ctrl->getNextClass($this);

		switch ($next_class)
		{
			case 'iloerinformpublishwizardgui':
				$this->prepareOutput();
				$this->plugin->includeClass('class.ilOERinFormPublishWizardGUI.php');
				$pubGUI = new ilOERinFormPublishWizardGUI();
				$this->ctrl->forwardCommand($pubGUI);
				break;

			default:
				switch ($cmd)
				{
					case "publish":
					case "republish":
					case "unpublish":
						$this->$cmd();
						break;

					default:
						ilUtil::sendFailure($this->lng->txt("permission_denied"), true);
						ilUtil::redirect($fallback_url);
						break;
				}
		}


	}

	/**
	 * Get the plugin object
	 * @return ilOERinFormPlugin|null
	 */
	public function getPlugin()
	{
		return $this->plugin;
	}

    /**
	 * Prepare the test header, tabs etc.
	 */
	protected function prepareOutput()
	{
		global $DIC;

		/** @var ilLocatorGUI $ilLocator */
		$ilLocator = $DIC['ilLocator'];

		$ilLocator->addRepositoryItems($this->parent_obj->getRefId());
		$ilLocator->addItem($this->parent_obj->getTitle(), ilLink::_getLink($this->parent_ref_id, $this->parent_type));

		$this->tpl->loadStandardTemplate(); // see https://github.com/ILIAS-eLearning/ILIAS/commit/0c199948c24dc454f36d6dc3fca3765dfa39e5a4#diff-cf5e03a4e2f5e094186a3fd00fa7187d6bcf86b16c8b0907ba923bd8dfdd37fe
		$this->tpl->setLocator();
		$this->tpl->setTitle($this->parent_obj->getPresentationTitle());
		$this->tpl->setDescription($this->parent_obj->getLongDescription());
		$this->tpl->setTitleIcon(ilObject::_getIcon('', 'big', $this->parent_type), $this->lng->txt('obj_'.$this->parent_type));
	}


	/**
	 * Reject the publishing
	 */
	public function unpublish()
	{
		$this->md_obj->unpublish();
		ilUtil::sendSuccess($this->plugin->txt('msg_meta_unpublished'), true);
		$this->ctrl->setParameter($this,'section', $_REQUEST['section']);
		$this->returnToParent();
	}


    /**
     * Add the publishing info to the page
     */
	public function addPublishInfo()
    {
        global $DIC;
        $factory = $DIC->ui()->factory();
        $renderer = $DIC->ui()->renderer();

        $tpl = $this->plugin->getTemplate('tpl.publish_status.html');
        $tpl->setVariable('HEADER', $this->plugin->txt('publish_oer'));
        $tpl->setVariable('STATUS', $this->md_obj->getPublishInfo());
       // $tpl->setVariable('HELP', $this->plugin->getHelpGUI()->getHelpButton('oer_publishing'));

        if ($this->md_obj->getPublishStatus() == ilOERinFormPublishMD::STATUS_PUBLIC)
        {
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

        $this->ctrl->setParameter($this, 'return', urlencode($_SERVER['SCRIPT_NAME'].'?'.$_SERVER['QUERY_STRING']));

        switch ($this->md_obj->getPublishStatus())
        {
            case ilOERinFormPublishMD::STATUS_PRIVATE:
            case ilOERinFormPublishMD::STATUS_READY:
                $button = $factory->button()->standard($this->plugin->txt('publish'), $this->ctrl->getLinkTargetByClass(array('ilUIPluginRouterGUI', 'ilOERinFormPublishGUI', 'ilOERinFormPublishWizardGUI')));
                $tpl->setVariable('PUBLISH', $renderer->render($button));
            break;

            case ilOERinFormPublishMD::STATUS_PUBLIC:
                $button = $factory->button()->standard($this->plugin->txt('republish'), $this->ctrl->getLinkTargetByClass(array('ilUIPluginRouterGUI', 'ilOERinFormPublishGUI', 'ilOERinFormPublishWizardGUI')));
                $tpl->setVariable('REPUBLISH', $renderer->render($button));
                break;

            case ilOERinFormPublishMD::STATUS_BROKEN:
                $button = $factory->button()->standard($this->plugin->txt('unpublish'), $this->getLinkTarget('unpublish'));
                $tpl->setVariable('UNPUBLISH', $renderer->render($button));
                break;
        }

        $this->tpl->setRightContent($tpl->get());
    }

    /**
     * Return to the parent GUI
     */
    protected function returnToParent()
    {
        $this->ctrl->redirectToURL($_GET['return']);
    }

}
?>