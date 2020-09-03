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
    /** @var ilOERinFormConfig */
    protected $config;

    /** @var ilOERinFormHelp */
    protected $help;

	public function getPluginName()
	{
		return "OERinForm";
	}


    /**
     * Get the data set for an object
     * @param $obj_id
     * @return ilOERinFormData
     */
	public function getData($obj_id)
    {
        $this->includeClass('class.ilOERinFormData.php');
        return new ilOERinFormData($this, $obj_id);
    }


    /**
     * Get the plugin configuration
     * @return ilOERinFormConfig
     */
    public function getConfig()
    {
        if (!isset($this->config))
        {
            $this->includeClass('class.ilOERinFormConfig.php');
            $this->config = new ilOERinFormConfig($this);
        }
        return $this->config;
    }

    /**
     * Get the plugin configuration
     * @return ilOERinFormHelp
     */
    public function getHelp()
    {
        if (!isset($this->help))
        {
            $this->includeClass('class.ilOERinFormHelp.php');
            $this->help = new ilOERinFormHelp($this);
        }
        return $this->help;
    }

    /**
     * Get the plugin configuration
     * @return ilOERinFormHelpGUI
     */
    public function getHelpGUI()
    {
        $this->includeClass('class.ilOERinFormHelpGUI.php');
        return new ilOERinFormHelpGUI();
    }

    /**
     * Check if the object type is allowed
     */
    public function isAllowedType($type)
    {
        return in_array($type, array('file','lm','htlm','sahs','glo','wiki', 'tst', 'qpl'));
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