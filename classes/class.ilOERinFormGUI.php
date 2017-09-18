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

	/** @var  ilObject $parentObj */
	protected $parentObj;

	/**
	 * ilUILimitedMediaControlGUI constructor.
	 */
	public function __construct()
	{
		global $ilDB, $ilCtrl, $tpl, $lng;

		$this->ctrl = $ilCtrl;
		$this->tpl = $tpl;
		$this->lng = $lng;

		$lng->loadLanguageModule('assessment');

		$this->plugin = ilPlugin::getPluginObject(IL_COMP_SERVICE, 'UIComponent', 'uihk', 'OERinForm');

		$this->parent_ref_id = $_GET['ref_id'];
		$this->parent_type = ilObject::_lookupType($this->parent_ref_id, true);

		$this->parentObj = ilObjectFactory::getInstanceByRefId($this->parent_ref_id);

		$this->parent_gui_class = ilObjectFactory::getClassByType($this->parent_type).'GUI';
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
			case "save":
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

		//$this->ctrl->setParameterByClass($this->parent_gui_class, 'ref_id',  $this->parentObj->getRefId());
		$ilLocator->addRepositoryItems($this->parentObj->getRefId());
		//$ilLocator->addItem($this->parentObj->getTitle(),$this->ctrl->getLinkTargetByClass($this->parent_gui_class));

		$this->tpl->getStandardTemplate();
		$this->tpl->setLocator();
		$this->tpl->setTitle($this->parentObj->getPresentationTitle());
		$this->tpl->setDescription($this->parentObj->getLongDescription());
		$this->tpl->setTitleIcon(ilObject::_getIcon('', 'big', $this->parent_type), $lng->txt('obj_'.$this->parent_type));

		return true;
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
		global $ilCtrl, $ilToolbar;

		require_once 'Services/UIComponent/Button/classes/class.ilLinkButton.php';
		$button = ilLinkButton::getInstance();
		$button->setUrl($ilCtrl->getLinkTargetByClass(array('ilUIPluginRouterGUI','ilOERinFormGUI'), 'show'));
		$button->setCaption($this->plugin->txt('oerinform'), false);
		$button->getOmitPreventDoubleSubmission();
		$ilToolbar->addButtonInstance($button);
    }
}
?>