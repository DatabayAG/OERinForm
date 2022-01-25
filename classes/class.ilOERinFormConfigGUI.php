<?php
// Copyright (c) 2018 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg, GPLv3, see LICENSE

/**
 * vhb Shibboleth Authentication configuration user interface class
 *
 * @ilCtrl_Calls ilOERinFormConfigGUI: ilPropertyFormGUI
 *
 * @author Fred Neumann <fred.neumann@fau.de>
 */
class ilOERinFormConfigGUI extends ilPluginConfigGUI
{
	/** @var ilOERinFormPlugin $plugin */
	protected $plugin;

	/** @var ilOERinFormConfig $config */
	protected $config;

	/** @var ilTabsGUI $tabs */
    protected $tabs;

    /** @var ilCtrl $ctrl */
    protected $ctrl;

    /** @var ilLanguage $lng */
	protected $lng;

    /** @var ilTemplate $lng */
	protected $tpl;

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
        $this->tabs = $DIC->tabs();
        $this->ctrl = $DIC->ctrl();
        $this->tpl = $DIC['tpl'];

        $this->tabs->addTab('basic', $this->plugin->txt('basic_configuration'), $this->ctrl->getLinkTarget($this, 'configure'));
        $this->tabs->addTab('help', $this->plugin->txt('help_configuration'), $this->ctrl->getLinkTarget($this, 'configureHelp'));

        switch ($DIC->ctrl()->getNextClass())
        {
            case 'ilpropertyformgui':
                switch ($_GET['config'])
                {
                    case 'basic':
                        $DIC->ctrl()->forwardCommand($this->initBasicConfigurationForm());
                        break;
                    case 'help':
                        $DIC->ctrl()->forwardCommand($this->initHelpConfigurationForm());
                        break;
                }

                break;

            default:
                switch ($cmd)
                {
                    case "configure":
                    case "saveBasicSettings":
                        $this->tabs->activateTab('basic');
                        $this->$cmd();
                        break;

                    case "configureHelp":
                    case "saveHelpSettings":
                        $this->tabs->activateTab('help');
                        $this->$cmd();
                        break;
                }
        }
	}

	/**
	 * Show base configuration screen
	 */
	protected function configure()
	{
		$form = $this->initBasicConfigurationForm();
		$this->tpl->setContent($form->getHTML());
	}

    /**
     * Show help configuration screen
     */
    protected function configureHelp()
    {
        $form = $this->initHelpConfigurationForm();
        $content = $form->getHTML();

        if ($this->plugin->getHelp()->getWikiRefId())
        {
            $this->plugin->includeClass('class.ilOERinFormHelpTableGUI.php');
            $table = new ilOERinFormHelpTableGUI($this,'configureHelp');
            $content .= $table->getHTML();
        }

        $this->tpl->setContent($content);
    }


    /**
	 * Initialize the configuration form
	 * @return ilPropertyFormGUI form object
	 */
	protected function initBasicConfigurationForm()
	{
		$form = new ilPropertyFormGUI();
        $form->setTitle($this->plugin->txt('basic_configuration'));
		$form->setFormAction($this->ctrl->getFormAction($this));
		$this->ctrl->setParameterByClass('ilTextInputGUI', 'config', 'basic');

        $item = new ilTextInputGUI($this->plugin->txt('pub_ref_id'), 'pub_ref_id');
        $item->setValue($this->config->get('pub_ref_id'));

        $form->addItem($item);
		$form->addCommandButton("saveBasicSettings", $this->lng->txt("save"));
		return $form;
	}

    /**
     * Initialize the configuration form
     * @return ilPropertyFormGUI form object
     */
    protected function initHelpConfigurationForm()
    {
        $form = new ilPropertyFormGUI();
        $form->setTitle($this->plugin->txt('help_configuration'));
        $form->setFormAction( $this->ctrl->getFormAction($this));
        $this->ctrl->setParameterByClass('ilOERinFormRepositorySelectInputGUI', 'config', 'help');

        $item = new ilOERinFormRepositorySelectInputGUI($this->plugin->txt('wiki_ref_id'), 'wiki_ref_id');
        $item->setSelectableTypes(['wiki']);
        $item->setInfo($this->plugin->txt('wiki_ref_id_info'));
        $item->setValue($this->config->get('wiki_ref_id'));
        $form->addItem($item);

        $form->addCommandButton("saveHelpSettings", $this->lng->txt("save"));
        return $form;
    }

	/**
	 * Save the basic settings
	 */
	protected function saveBasicSettings()
	{
		$form = $this->initBasicConfigurationForm();
		if ($form->checkInput())
		{
            $this->config->set('pub_ref_id', $form->getInput('pub_ref_id'));
            $this->config->write();

			ilUtil::sendSuccess($this->lng->txt("settings_saved"), true);
			$this->ctrl->redirect($this, 'configure');
		}
		else
		{
			$form->setValuesByPost();
			$this->tpl->setContent($form->getHtml());
		}
	}


    /**
     * Save the help settings
     */
    protected function saveHelpSettings()
    {
        $form = $this->initHelpConfigurationForm();
        if ($form->checkInput())
        {
            $this->config->set('wiki_ref_id', $form->getInput('wiki_ref_id'));
            $this->config->write();

            ilUtil::sendSuccess($this->lng->txt("settings_saved"), true);
            $this->ctrl->redirect($this, 'configureHelp');
        }
        else
        {
            $form->setValuesByPost();
            $this->tpl->setContent($form->getHtml());
        }
    }
}

?>