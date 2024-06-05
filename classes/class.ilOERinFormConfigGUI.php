<?php

/**
 * GUI for the plugin configuration
 *
 * @ilCtrl_IsCalledBy ilOERinFormConfigGUI: ilObjComponentSettingsGUI
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

    public function performCommand(string $cmd): void
    {
        $this->tabs->addTab('basic', $this->plugin->txt('basic_configuration'), $this->ctrl->getLinkTarget($this, 'configure'));

        switch ($this->ctrl->getNextClass()) {
            case 'ilpropertyformgui':
                $this->ctrl->forwardCommand($this->initBasicConfigurationForm());
                break;

            default:
                switch ($cmd) {
                    case 'configure':
                    case 'saveBasicSettings':
                        $this->tabs->activateTab('basic');
                        $this->$cmd();
                        break;
                    default:
                        $this->tpl->setContent('unknown command');
                }
        }
    }

    protected function configure(): void
    {
        $form = $this->initBasicConfigurationForm();
        $this->tpl->setContent($form->getHTML());
    }

    protected function initBasicConfigurationForm(): ilPropertyFormGUI
    {
        $form = new ilPropertyFormGUI();
        //$form->setTitle($this->plugin->txt('basic_configuration'));
        $form->setFormAction($this->ctrl->getFormAction($this));

        foreach (['base', 'help'] as $section) {
            foreach ($this->config->getParamsBySection($section) as $param) {
                $form->addItem($param->getFormItem());
            }
        }

        $form->addCommandButton('saveBasicSettings', $this->lng->txt('save'));
        return $form;
    }

    protected function saveBasicSettings(): void
    {
        $form = $this->initBasicConfigurationForm();
        if ($form->checkInput()) {
            foreach (['base', 'help'] as $section) {
                foreach ($this->config->getParamsBySection($section) as $name => $param) {
                    $this->config->set($name, $form->getInput($name));
                }
            }
            $this->config->write();

            $this->tpl->setOnScreenMessage('success', $this->lng->txt('settings_saved'), true);
            $this->ctrl->redirect($this, 'configure');
        } else {
            $form->setValuesByPost();
            $this->tpl->setContent($form->getHtml());
        }
    }
}
