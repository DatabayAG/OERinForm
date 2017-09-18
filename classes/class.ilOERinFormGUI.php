<?php
// Copyright (c) 2017 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg, GPLv3, see LICENSE


/**
 * Main GUI for OERinForm plugin
 *
 * @author Fred Neumann <fred.neumann@fau.de>
 * @version $Id$
 *
 * @ilCtrl_IsCalledBy ilOERinFormGUI: ilUIPluginRouterGUI
 */
class ilOERinFormGUI
{
	/** @var ilCtrl $ctrl */
	protected $ctrl;

	/** @var ilTemplate $tpl */
	protected $tpl;

	/** @var  ilLanguage $lng */
	protected $lng;

	/** @var ilOERinFormPlugin $plugin */
	protected $plugin;

	/** @var  int parent object ref_id */
	protected $parent_ref_id;

	/** @var  string parent object type */
	protected $parent_type;

	/** @var  string parent gui class */
	protected $parent_gui_class;

	/** @var  ilObject $parent_obj */
	protected $parent_obj;

	/** @var  ilOERinFormMD $md_obj */
	protected $md_obj;

	/**
	 * ilUILimitedMediaControlGUI constructor.
	 */
	public function __construct()
	{
		global $ilDB, $ilCtrl, $tpl, $lng;

		$this->ctrl = $ilCtrl;
		$this->tpl = $tpl;
		$this->lng = $lng;

		$this->ctrl->saveParameter($this, 'ref_id');

		$this->plugin = ilPlugin::getPluginObject(IL_COMP_SERVICE, 'UIComponent', 'uihk', 'OERinForm');
		$this->plugin->includeClass('class.ilOERinFormMD.php');

		$this->parent_ref_id = $_GET['ref_id'];
		$this->parent_type = ilObject::_lookupType($this->parent_ref_id, true);
		$this->parent_obj = ilObjectFactory::getInstanceByRefId($this->parent_ref_id);
		$this->parent_gui_class = ilObjectFactory::getClassByType($this->parent_type).'GUI';

		$this->md_obj = new ilOERinFormMD($this->parent_obj->getId(), $this->parent_obj->getId(), $this->parent_type);
		$this->md_obj->setPlugin($this->plugin);
    }


	/**
	* Handles all commands, default is "show"
	*/
	public function executeCommand()
	{
		/** @var ilAccessHandler $ilAccess */
		/** @var ilErrorHandling $ilErr */
		global $ilAccess, $ilErr, $lng;

		$fallback_url = "goto.php?target=".$this->parent_type.'_'.$this->parent_ref_id;

		if (!$ilAccess->checkAccess('write','', $_GET['ref_id']))
		{
            ilUtil::sendFailure($lng->txt("permission_denied"), true);
            ilUtil::redirect($fallback_url);
		}

		$this->ctrl->saveParameter($this, 'ref_id');
		$cmd = $this->ctrl->getCmd('show');

		switch ($cmd)
		{
			case "show":
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
                ilUtil::sendFailure($lng->txt("permission_denied"), true);
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
		/** @var ilLocatorGUI $ilLocator */
		/** @var ilLanguage $lng */
		global $ilLocator, $lng;

		//$this->ctrl->setParameterByClass($this->parent_gui_class, 'ref_id',  $this->parent_obj->getRefId());
		$ilLocator->addRepositoryItems($this->parent_obj->getRefId());
		//$ilLocator->addItem($this->parent_obj->getTitle(),$this->ctrl->getLinkTargetByClass($this->parent_gui_class));

		$this->tpl->getStandardTemplate();
		$this->tpl->setLocator();
		$this->tpl->setTitle($this->parent_obj->getPresentationTitle());
		$this->tpl->setDescription($this->parent_obj->getLongDescription());
		$this->tpl->setTitleIcon(ilObject::_getIcon('', 'big', $this->parent_type), $lng->txt('obj_'.$this->parent_type));

		return true;
	}

	public function publish()
	{
		global $lng, $ilCtrl;
		$this->md_obj->publish();
		ilUtil::sendSuccess($this->plugin->txt('msg_meta_published'), true);
		$ilCtrl->setParameter($this,'section', $_REQUEST['section']);
		ilUtil::redirect($_GET['return']);

	}

	public function republish()
	{
		global $lng, $ilCtrl;
		$this->md_obj->publish();
		ilUtil::sendSuccess($this->plugin->txt('msg_meta_republished'), true);
		$ilCtrl->setParameter($this,'section', $_REQUEST['section']);
		ilUtil::redirect($_GET['return']);
	}

	public function unpublish()
	{
		global $lng, $ilCtrl;
		$this->md_obj->unpublish();
		ilUtil::sendSuccess($this->plugin->txt('msg_meta_unpublished'), true);
		$ilCtrl->setParameter($this,'section', $_REQUEST['section']);
		ilUtil::redirect($_GET['return']);
	}


	public function show()
	{
		global $tpl;

		$tpl->setContent('hello world');
		$tpl->show();
	}

    /**
	 * Set the Toolbar
	 */
	public function modifyMetaDataToolbar()
	{
		/** @var ilToolbarGUI $ilToolbar */
		global $ilToolbar;

		$ilToolbar->addSeparator();
		$ilToolbar->addText($this->md_obj->getPublishInfo());

		$this->ctrl->setParameterByClass('ilOERinFormGUI', 'return', urlencode($_SERVER['REQUEST_URI'].'?'.$_SERVER['QUERY_STRING']));

		include_once "Services/UIComponent/Button/classes/class.ilLinkButton.php";
		switch ($this->md_obj->getPublishStatus())
		{
			case ilOERinFormMD::STATUS_READY:
				$button = ilLinkButton::getInstance();
				$button->setCaption($this->plugin->txt('publish'), false);
				$button->setUrl($this->ctrl->getLinkTargetByClass(array('ilUIPluginRouterGUI','ilOERinFormGUI'), 'publish'));
				$ilToolbar->addButtonInstance($button);
				break;

			case ilOERinFormMD::STATUS_PUBLIC:
				$button = ilLinkButton::getInstance();
				$button->setCaption($this->plugin->txt('republish'), false);
				$button->setUrl($this->ctrl->getLinkTargetByClass(array('ilUIPluginRouterGUI','ilOERinFormGUI'), 'republish'));
				$ilToolbar->addButtonInstance($button);

				$button = ilLinkButton::getInstance();
				$button->setCaption($this->plugin->txt('unpublish'), false);
				$button->setUrl($this->ctrl->getLinkTargetByClass(array('ilUIPluginRouterGUI','ilOERinFormGUI'), 'unpublish'));
				$ilToolbar->addButtonInstance($button);
				break;

			case ilOERinFormMD::STATUS_BROKEN:
				$button = ilLinkButton::getInstance();
				$button->setCaption($this->plugin->txt('unpublish'), false);
				$button->setUrl($this->ctrl->getLinkTargetByClass(array('ilUIPluginRouterGUI','ilOERinFormGUI'), 'unpublish'));
				$ilToolbar->addButtonInstance($button);
				break;
		}
    }
}
?>