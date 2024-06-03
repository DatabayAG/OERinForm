<?php

// Copyright (c) 2018 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg, GPLv3, see LICENSE

/**
 * GUI for the plugin configuration
 * @ilCtrl_Calls ilOERinFormConfigGUI: ilPropertyFormGUI
 */
class ilOERinFormConfigGUI extends ilPluginConfigGUI
{
    protected ilTabsGUI $tabs;
    protected ilCtrl $ctrl;
    protected ilLanguage $lng;
    protected ilGlobalTemplateInterface $tpl;

    protected ilOERinFormPlugin $plugin;
    protected ilOERinFormConfig $config;

    public function __construct()
    {
        global $DIC;
        $this->lng = $DIC->language();
        $this->tabs = $DIC->tabs();
        $this->ctrl = $DIC->ctrl();
        $this->tpl = $DIC->ui()->mainTemplate();

        $this->plugin = ilOERinFormPlugin::getInstance();
        $this->config = $this->plugin->getConfig();
    }

    /**
     * Handles all commands, default is "configure"
     * todo: remove $_GET access
     */
    public function performCommand(string $cmd): void
    {
        $this->tabs->addTab('basic', $this->plugin->txt('basic_configuration'), $this->ctrl->getLinkTarget($this, 'configure'));

        switch ($this->ctrl->getNextClass()) {
            case 'ilpropertyformgui':
                switch ($_GET['config']) {
                    case 'basic':
                        $this->ctrl->forwardCommand($this->initBasicConfigurationForm());
                        break;
                }
                break;

            default:
                switch ($cmd) {
                    case "configure":
                    case "saveBasicSettings":
                        $this->tabs->activateTab('basic');
                        $this->$cmd();
                        break;
                }
        }
    }

    /**
     * Show base configuration screen
     */
    protected function configure(): void
    {
        $form = $this->initBasicConfigurationForm();
        $this->tpl->setContent($form->getHTML());
    }



    /**
     * Initialize the main configuration form
     * todo: try repository picker from Flashcards
     */
    protected function initBasicConfigurationForm(): ilPropertyFormGUI
    {
        $form = new ilPropertyFormGUI();
        $form->setTitle($this->plugin->txt('basic_configuration'));
        $form->setFormAction($this->ctrl->getFormAction($this));
        $this->ctrl->setParameterByClass('ilTextInputGUI', 'config', 'basic');

        $item = new ilTextInputGUI($this->plugin->txt('pub_ref_id'), 'pub_ref_id');
        $item->setValue($this->config->get('pub_ref_id'));
        $item->setInfo($this->plugin->txt('pub_ref_id_info'));
        $form->addItem($item);

        $params = $this->config->getParams();
        foreach ($this->config->getHelpIds() as $help_id) {
            if (isset($params[$help_id])) {
               $form->addItem($params[$help_id]->getFormItem());
            }
        }

        $form->addCommandButton("saveBasicSettings", $this->lng->txt("save"));
        return $form;
    }

    /**
     * Save the basic settings
     */
    protected function saveBasicSettings(): void
    {
        $form = $this->initBasicConfigurationForm();
        if ($form->checkInput()) {
            $this->config->set('pub_ref_id', $form->getInput('pub_ref_id'));
            foreach ($this->config->getHelpIds() as $help_id) {
                $this->config->set('help_id', $form->getInput('help_id'));
            }
            $this->config->write();

            $this->tpl->setOnScreenMessage('success', $this->lng->txt("settings_saved"), true);
            $this->ctrl->redirect($this, 'configure');
        } else {
            $form->setValuesByPost();
            $this->tpl->setContent($form->getHtml());
        }
    }
}
