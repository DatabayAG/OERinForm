<?php
// Copyright (c) 2017 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg, GPLv3, see LICENSE

require_once('Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/OERinForm/classes/class.ilOerBaseGUI.php');

/**
 * GUI for OER publishing functions
 *
 * @author Fred Neumann <fred.neumann@fau.de>
 * @version $Id$
 *
 * @ilCtrl_IsCalledBy ilOerPublishGUI: ilUIPluginRouterGUI
 * @ilCtrl_Calls ilOerPublishGUI: ilWikiPageGUI
 */
class ilOerPublishGUI extends ilOerBaseGUI
{

	/** @var  int parent object ref_id */
	protected $parent_ref_id;

	/** @var  string parent object type */
	protected $parent_type;

	/** @var  string parent gui class */
	protected $parent_gui_class;

	/** @var  ilObject $parent_obj */
	protected $parent_obj;

	/** @var  ilOerPublishMD $md_obj */
	protected $md_obj;

	/**
	 * ilUILimitedMediaControlGUI constructor.
	 */
	public function __construct()
	{
		parent::__construct();

		$this->ctrl->saveParameter($this, 'ref_id');

		$this->parent_ref_id = $_GET['ref_id'];
		$this->parent_type = ilObject::_lookupType($this->parent_ref_id, true);
		$this->parent_obj = ilObjectFactory::getInstanceByRefId($this->parent_ref_id);
		$this->parent_gui_class = ilObjectFactory::getClassByType($this->parent_type).'GUI';

		$this->plugin->includeClass('class.ilOerPublishMD.php');
		$this->md_obj = new ilOerPublishMD($this->parent_obj->getId(), $this->parent_obj->getId(), $this->parent_type);
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

		switch ($cmd)
		{
			case "showHelp":
				if ($this->prepareOutput())
				{
					$this->$cmd();
				}
                break;

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

		require_once('Services/Link/classes/class.ilLink.php');
		$ilLocator->addRepositoryItems($this->parent_obj->getRefId());
		$ilLocator->addItem($this->parent_obj->getTitle(), ilLink::_getLink($this->parent_ref_id, $this->parent_type));

		$this->tpl->getStandardTemplate();
		$this->tpl->setLocator();
		$this->tpl->setTitle($this->parent_obj->getPresentationTitle());
		$this->tpl->setDescription($this->parent_obj->getLongDescription());
		$this->tpl->setTitleIcon(ilObject::_getIcon('', 'big', $this->parent_type), $this->lng->txt('obj_'.$this->parent_type));

		return true;
	}


	/**
	 * Publish the object
	 */
	public function publish()
	{
		$this->md_obj->publish();
		ilUtil::sendSuccess($this->plugin->txt('msg_meta_published'), true);
		$this->ctrl->setParameter($this,'section', $_REQUEST['section']);
		ilUtil::redirect($_GET['return']);

	}

	/**
	 * Update the publishing
	 */
	public function republish()
	{
		$this->md_obj->publish();
		ilUtil::sendSuccess($this->plugin->txt('msg_meta_republished'), true);
		$this->ctrl->setParameter($this,'section', $_REQUEST['section']);
		ilUtil::redirect($_GET['return']);
	}

	/**
	 * Reject the publishing
	 */
	public function unpublish()
	{
		$this->md_obj->unpublish();
		ilUtil::sendSuccess($this->plugin->txt('msg_meta_unpublished'), true);
		$this->ctrl->setParameter($this,'section', $_REQUEST['section']);
		ilUtil::redirect($_GET['return']);
	}


	/**
	 * Show a help page
	 */
	public function showHelp()
	{
		$a_help_id = $_GET['help_id'];

		include_once "Services/UIComponent/Button/classes/class.ilLinkButton.php";
		$button = ilLinkButton::getInstance();
		$button->setCaption($this->plugin->txt('back'), false);
		$button->setUrl(htmlentities($_GET['return']));
		$this->toolbar->addButtonInstance($button);

		$url = $this->plugin->getWikiHelpDetailsUrl($a_help_id);
		if (!empty($url)) {
			$button = ilLinkButton::getInstance();
			$button->setCaption($this->plugin->txt('faq'), false);
			$button->setUrl(htmlentities($url));
			$this->toolbar->addButtonInstance($button);
		}

		$page_id = $this->plugin->getWikiHelpPageId($a_help_id);
		if  (!empty($page_id))
		{$tpl = $this->plugin->getTemplate('tpl.help_page.html');

			require_once('Modules/Wiki/classes/class.ilWikiPageGUI.php');
			$page_gui = new ilWikiPageGUI($page_id);
			if (isset($page_gui))
			{
				$page_gui->setTemplateOutput(false);
				$page_gui->setOutputMode(IL_PAGE_PRESENTATION);
				$page_gui->setEnabledTabs(false);

				$this->tpl->addCss('Services/COPage/css/content.css"');
				$tpl->setVariable('CONTENT', $page_gui->getHTML());
			}
			else
			{
				$tpl->setVariable('CONTENT', 'not found');
			}

			$this->tpl->setContent($tpl->get());
		}

		$this->tabs->clearTargets();
		$this->tabs->clearSubTabs();
		$this->tpl->show();
	}

    /**
	 * Modify the meta data toolbar
	 */
	public function modifyMetaDataToolbar()
	{
		/** @var ilToolbarGUI $ilToolbar */
		global $ilToolbar;

		$this->toolbar->addSeparator();
		$this->toolbar->addText($this->md_obj->getPublishInfo());

		$this->ctrl->setParameterByClass('ilOerPublishGUI', 'return', urlencode($_SERVER['REQUEST_URI'].'?'.$_SERVER['QUERY_STRING']));

		include_once "Services/UIComponent/Button/classes/class.ilLinkButton.php";
		switch ($this->md_obj->getPublishStatus())
		{
			case ilOerPublishMD::STATUS_READY:
				$button = ilLinkButton::getInstance();
				$button->setCaption($this->plugin->txt('publish'), false);
				$button->setUrl($this->getLinkTarget('publish'));
				$this->toolbar->addButtonInstance($button);
				break;

			case ilOerPublishMD::STATUS_PUBLIC:
				$button = ilLinkButton::getInstance();
				$button->setCaption($this->plugin->txt('republish'), false);
				$button->setUrl($this->getLinkTarget('republish'));
				$this->toolbar->addButtonInstance($button);

				$button = ilLinkButton::getInstance();
				$button->setCaption($this->plugin->txt('unpublish'), false);
				$button->setUrl($this->getLinkTarget('unpublish'));
				$this->toolbar->addButtonInstance($button);
				break;

			case ilOerPublishMD::STATUS_BROKEN:
				$button = ilLinkButton::getInstance();
				$button->setCaption($this->plugin->txt('unpublish'), false);
				$button->setUrl($this->getLinkTarget('unpublish'));
				$this->toolbar->addButtonInstance($button);
				break;
		}

		// try to directly show a help page
		$page_id = $this->plugin->getWikiHelpPageId('publish_oai');
		if (!empty($page_id))
		{
			$button = ilLinkButton::getInstance();
			$button->setCaption($this->plugin->txt('help'), false);
			$this->ctrl->setParameter($this, 'help_id', 'publish_oai');
			$button->setUrl($this->getLinkTarget('showHelp'));
			$this->toolbar->addButtonInstance($button);
			return;
		}

		// or try to open the help url in a new window
		$url = $this->plugin->getWikiHelpDetailsUrl('publish_oai');
		if (!empty($url))
		{
			$button = ilLinkButton::getInstance();
			$button->setCaption($this->plugin->txt('help'), false);
			$button->setTarget('_blank');
			$button->setUrl(htmlentities($url));
			$this->toolbar->addButtonInstance($button);
		}
	}
}
?>