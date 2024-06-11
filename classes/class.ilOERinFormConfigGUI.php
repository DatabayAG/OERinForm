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
        $this->tabs->addTab('mail', $this->plugin->txt('mail_notification'), $this->ctrl->getLinkTarget($this, 'configureMail'));

        switch ($this->ctrl->getNextClass()) {
            case strtolower(ilPropertyFormGUI::class):
                // needed for repository picker
                $this->ctrl->forwardCommand($this->initBasicConfigurationForm());
                break;

            default:
                switch ($cmd) {
                    case 'configure':
                    case 'saveBasicSettings':
                    case 'configureMail':
                    case 'saveMailSettings':

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
        $this->tabs->activateTab('basic');
        $form = $this->initBasicConfigurationForm();
        if ($form->checkInput()) {
            foreach (['base', 'help'] as $section) {
                foreach ($this->config->getParamsBySection($section) as $name => $param) {
                    $this->config->set($name, $form->getInput($name));
                }
            }
            $this->config->write();

            $this->tpl->setOnScreenMessage(ilGlobalTemplateInterface::MESSAGE_TYPE_SUCCESS, $this->lng->txt('settings_saved'), true);
            $this->ctrl->redirect($this, 'configure');
        } else {
            $this->tabs->activateTab('basic');
            $form->setValuesByPost();
            $this->tpl->setContent($form->getHtml());
        }
    }

    protected function configureMail(): void
    {
        $form = $this->initMailConfigurationForm();

        $this->tabs->activateTab('mail');
        $this->tpl->setOnScreenMessage(ilGlobalTemplateInterface::MESSAGE_TYPE_INFO, $this->plugin->txt('mail_notification_info'));
        $this->tpl->setContent($form->getHTML());
    }

    /**
     * @see ilMailTemplateGUI::getTemplateForm
     */
    protected function initMailConfigurationForm(): ilPropertyFormGUI
    {
        $params = $this->config->getParamsBySection('mail');

        $form = new ilPropertyFormGUI();
        $form->setFormAction($this->ctrl->getFormAction($this));

        foreach ($this->config->getParamsBySection('mail') as $name => $param) {
            $form->addItem($param->getFormItem());
        }
        $form->getItemByPostVar('noti_subject')->setValue($this->config->getNotificationSubject());
        $form->getItemByPostVar('noti_message')->setValue($this->config->getNotificationMessage());

        $this->lng->loadLanguageModule('mail');

        $placeholders = new ilManualPlaceholderInputGUI(
            $this->lng->txt('mail_form_placeholders_label'),
            'noti_message'
        );
        $placeholders->addPlaceholder('CONTENT_LINK', $this->plugin->txt('mail_content_link'));
        $placeholders->addPlaceholder('PUBLISHER_NAME', $this->plugin->txt('mail_publisher_name'));
        $placeholders->addPlaceholder('PUBLISHER_EMAIL', $this->plugin->txt('mail_publisher_email'));
        $form->addItem($placeholders);

        $form->addCommandButton('saveMailSettings', $this->lng->txt('save'));
        return $form;
    }

    protected function SaveMailSettings(): void
    {
        $form = $this->initMailConfigurationForm();
        if ($form->checkInput()) {
            foreach ($this->config->getParamsBySection('mail') as $name => $param) {
                $this->config->set($name, $form->getInput($name));
            }
            $this->config->write();

            $this->tpl->setOnScreenMessage(ilGlobalTemplateInterface::MESSAGE_TYPE_SUCCESS, $this->lng->txt('settings_saved'), true);
            $this->ctrl->redirect($this, 'configureMail');
        } else {
            $this->tabs->activateTab('mail');
            $form->setValuesByPost();
            $this->tpl->setContent($form->getHtml());
        }

    }
}
