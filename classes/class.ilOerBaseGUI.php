<?php
// Copyright (c) 2017 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg, GPLv3, see LICENSE


/**
 * Base class for GUIs of the OER plugin
 */
class ilOerBaseGUI
{
	/** @var  ilAccessHandler $access */
	protected $access;

	/** @var ilCtrl $ctrl */
	protected $ctrl;

	/** @var  ilLanguage $lng */
	protected $lng;

	/** @var ilTabsGUI */
	protected $tabs;

	/** @var  ilToolbarGUI $toolbar */
	protected $toolbar;

	/** @var ilTemplate $tpl */
	protected $tpl;

	/** @var ilOERinFormPlugin $plugin */
	protected $plugin;


	/**
	 * ilOerBaseGUI constructor
	 */
	public function __construct()
	{
		global $DIC;

		$this->access = $DIC->access();
		$this->ctrl = $DIC->ctrl();
		$this->lng = $DIC->language();
		$this->tabs = $DIC->tabs();
		$this->toolbar = $DIC->toolbar();
		$this->tpl = $DIC['tpl'];

		$this->plugin = ilPlugin::getPluginObject(IL_COMP_SERVICE, 'UIComponent', 'uihk', 'OERinForm');
	}


	/**
	 * Get the link target for a command using the ui plugin router
	 * @param string $a_cmd
	 * @return string
	 */
	protected function getLinkTarget($a_cmd = '')
	{
		return $this->ctrl->getLinkTargetByClass(array('ilUIPluginRouterGUI', get_class($this)), $a_cmd);
	}
}