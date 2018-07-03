<?php
// Copyright (c) 2018 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg, GPLv3, see LICENSE

/**
 * vhb Shibboleth Authentication configuration user interface class
 *
 * @ilCtrl_Calls: ilOerInFormConfigGUI: ilPropertyFormGUI
 *
 * @author Fred Neumann <fred.neumann@fau.de>
 */
class ilOerInFormConfigGUI extends ilPluginConfigGUI
{
	/** @var ilOerInFormPlugin $plugin */
	protected $plugin;

	/** @var ilOerInFormConfig $config */
	protected $config;

	protected $lng;


    /**
	 * Handles all commands, default is "configure"
	 */
	public function performCommand($cmd)
	{
        global $DIC;

        // this can't be in constructor
        $this->plugin = $this->getPluginObject();
        $this->config = $this->plugin->getConfig();
        $this->lng = $DIC->language();


		switch ($cmd)
		{
			case "configure":
            case "saveSettings":
				$this->$cmd();
				break;
		}
	}

	/**
	 * Show configuration screen screen
	 */
	protected function configure()
	{
	    $this->plugin->getHelp();
		global $tpl;
		$form = $this->initConfigurationForm();
		$tpl->setContent($form->getHTML());
	}


	/**
	 * Initialize the configuration form
	 * @return ilPropertyFormGUI form object
	 */
	protected function initConfigurationForm()
	{
		global $ilCtrl, $lng;

		require_once("./Services/Form/classes/class.ilPropertyFormGUI.php");
		$form = new ilPropertyFormGUI();
		$form->setFormAction($ilCtrl->getFormAction($this));

        foreach ($this->config->getParams() as $name => $param)
        {
            $title = $param->title;
            $description = $param->description;
            $postvar = $name;

            switch($param->type)
            {
                case ilOerInFormParam::TYPE_HEAD:
                    $input = new ilFormSectionHeaderGUI();
                    $input->setTitle($title);
                    break;
                case ilOerInFormParam::TYPE_TEXT:
                    $input = new ilTextInputGUI($title, $postvar);
                    $input->setValue($param->value);
                    break;
                case ilOerInFormParam::TYPE_INT:
                    $input = new ilNumberInputGUI($title, $postvar);
                    $input->allowDecimals(false);
                    $input->setSize(10);
                    $input->setValue($param->value);
                    break;
                case ilOerInFormParam::TYPE_BOOLEAN:
                    $input = new ilCheckboxInputGUI($title, $postvar);
                    $input->setChecked($param->value);
                    break;
                case ilOerInFormParam::TYPE_FLOAT:
                    $input = new ilNumberInputGUI($title, $postvar);
                    $input->allowDecimals(true);
                    $input->setSize(10);
                    $input->setValue($param->value);
                    break;
                case ilOerInFormParam::TYPE_REF_ID:
                    $input = new ilRepositorySelector2InputGUI($title, $postvar);
                    $input->setValue($param->value);
                    break;
            }
            $input->setInfo($description);
            $form->addItem($input);
        }


		$form->addCommandButton("saveSettings", $lng->txt("save"));
		return $form;
	}

	/**
	 * Save the settings
	 */
	protected function saveSettings()
	{
		global $tpl, $ilCtrl;

		$form = $this->initConfigurationForm();
		if ($form->checkInput())
		{
		    foreach (array_keys($this->config->getParams()) as $name)
            {
                $this->config->set($name, $form->getInput($name));
            }
            $this->config->write();

			ilUtil::sendSuccess($this->lng->txt("settings_saved"), true);
			$ilCtrl->redirect($this, 'configure');
		}
		else
		{
			$form->setValuesByPost();
			$tpl->setContent($form->getHtml());
		}
	}
}

?>