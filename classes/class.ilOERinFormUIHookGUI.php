<?php
// Copyright (c) 2017 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg, GPLv3, see LICENSE


include_once("./Services/UIComponent/classes/class.ilUIHookPluginGUI.php");

/**
 * User interface hook class
 *
 * @author Fred Neumann <fred.neumann@fau.de>
 */
class ilOERinFormUIHookGUI extends ilUIHookPluginGUI
{
	/**
	 * Modify GUI objects, before they generate ouput
	 *
	 * @param string $a_comp component
	 * @param string $a_part string that identifies the part of the UI that is handled
	 * @param string $a_par array of parameters (depend on $a_comp and $a_part)
	 */
	function modifyGUI($a_comp, $a_part, $a_par = array())
	{
		/** @var ilCtrl $ilCtrl */
		/** @var ilTabsGUI $ilTabs */
		global $ilCtrl, $ilTabs;


		switch ($a_part)
		{
			//case 'tabs':
			case 'sub_tabs':

				/**
				 * Standard meta data editor is shown
				 */
				if ($ilCtrl->getCmdClass() == 'ilmdeditorgui')
				{
					$this->modifyMetaDataToolbar();

					// save the tabs for reuse on the plugin pages
					// (these do not have the test gui as parent)
					// not nice, but effective
					$_SESSION['OERinForm']['TabTarget'] = $ilTabs->target;
					$_SESSION['OERinForm']['TabSubTarget'] = $ilTabs->sub_target;
				}

				/**
				 * OERinForm page is shown
				 */
				if ($ilCtrl->getCmdClass()  == 'iloerinformgui')
				{
					// reuse the tabs that were saved from the parent gui
					if (isset($_SESSION['OERinForm']['TabTarget']))
					{
						$ilTabs->target = $_SESSION['OERinForm']['TabTarget'];
					}
					if (isset($_SESSION['OERinForm']['TabSubTarget']))
					{
						$ilTabs->sub_target = $_SESSION['OERinForm']['TabSubTarget'];
					}

					foreach ($ilTabs->target as $td)
					{
						if (strpos(strtolower($td['link']),'ilmdeditorgui') !== false)
						{
							// this works because the tabs are rendered after the sub tabs
							$ilTabs->activateTab($td['id']);
						}
					}
				}
				break;

			default:
				break;
		}
	}

	/**
	 * Modify the toolbar of the meta data editor
	 */
	public function modifyMetaDataToolbar()
	{
		$this->plugin_object->includeClass('class.ilOERinFormGUI.php');
		$gui = new ilOERinFormGUI();
		$gui->modifyMetaDataToolbar();
	}
}
?>