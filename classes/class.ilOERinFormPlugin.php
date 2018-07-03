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
    /** @var ilOerInFormConfig */
    protected $config;

    /** @var ilOerInFormHelp */
    protected $help;

	public function getPluginName()
	{
		return "OERinForm";
	}


    /**
     * Get the plugin configuration
     * @return ilOerInFormConfig
     */
    public function getConfig()
    {
        if (!isset($this->config))
        {
            $this->includeClass('class.ilOerInFormConfig.php');
            $this->config = new ilOerInformConfig($this);
        }
        return $this->config;
    }

    /**
     * Get the plugin configuration
     * @return ilOerInFormHelp
     */
    public function getHelp()
    {
        if (!isset($this->help))
        {
            $this->includeClass('class.ilOerInFormHelp.php');
            $this->help = new ilOerInFormHelp($this);
        }
        return $this->help;
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
}

?>