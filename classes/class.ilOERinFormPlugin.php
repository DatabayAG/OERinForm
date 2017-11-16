<?php
// Copyright (c) 2017 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg, GPLv3, see LICENSE

include_once("./Services/UIComponent/classes/class.ilUserInterfaceHookPlugin.php");
 
/**
 * Basic plugin file
 *
 * @author Fred Neumann <fred.neumann@fau.de>
 * @version $Id$
 *
 */
class ilOERinFormPlugin extends ilUserInterfaceHookPlugin
{


	public function getPluginName()
	{
		return "OERinForm";
	}


	/**
	 * Get a user preference
	 * @param string	$name
	 * @param mixed		$default
	 * @return mixed
	 */
	public function getUserPreference($name, $default = false)
	{
		global $ilUser;
		$value = $ilUser->getPref($this->getId().'_'.$name);
		if ($value !== false)
		{
			return $value;
		}
		else
		{
			return $default;
		}
	}


	/**
	 * Set a user preference
	 * @param string	$name
	 * @param mixed		$value
	 */
	public function setUserPreference($name, $value)
	{
		global $ilUser;
		$ilUser->writePref($this->getId().'_'.$name, $value);
	}


	/**
	 * Get the id of a wiki page that can be directly shown as help
	 * @todo: ths has to be configured
	 *
	 * @param string $a_help_id
	 * @return int
	 */
	public function getWikiHelpPageId($a_help_id)
	{
		switch ($a_help_id)
		{
			case 'publish_oai':
				return 4;
		}
		return 0;
	}

	/**
	 * Get the url of a wiki page that can be linked for details
	 * @todo: ths has to be configured
	 *
	 * @param string $a_help_id
	 * @return int
	 */
	public function getWikiHelpDetailsUrl($a_help_id)
	{
		switch ($a_help_id)
		{
			case 'publish_oai':
				return 'goto.php?target=wiki_wpage_3_71&client_id=OERinForm';
		}
		return 0;
	}
}

?>