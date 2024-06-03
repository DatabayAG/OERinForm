<?php

// Copyright (c) 2017 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg, GPLv3, see LICENSE


/**
 * Base class for GUIs of the plugin
 */
class ilOERinFormBaseGUI
{
    protected ilAccessHandler $access;
    protected ilCtrl $ctrl;
    protected ilLanguage $lng;
    protected ilTabsGUI $tabs;
    protected ilToolbarGUI $toolbar;
    protected ilGlobalTemplateInterface $tpl;
    protected ilOERinFormPlugin $plugin;
    protected ilOERinFormConfig $config;
    protected \ILIAS\UI\Factory $factory;
    protected \ILIAS\UI\Renderer $renderer;

    public function __construct()
    {
        global $DIC;

        $this->access = $DIC->access();
        $this->ctrl = $DIC->ctrl();
        $this->lng = $DIC->language();
        $this->tabs = $DIC->tabs();
        $this->toolbar = $DIC->toolbar();
        $this->tpl = $DIC->ui()->mainTemplate();
        $this->factory = $DIC->ui()->factory();
        $this->renderer = $DIC->ui()->renderer();

        $this->plugin = ilOERinFormPlugin::getInstance();
        $this->config = $this->plugin->getConfig();
    }


    /**
     * Get the link target for a command using the ui plugin router
     */
    protected function getLinkTarget(string $a_cmd = '', string $a_anchor = '', bool $a_async = false) : string
    {
        return $this->ctrl->getLinkTargetByClass(['ilUIPluginRouterGUI', get_class($this)], $a_cmd, $a_anchor, $a_async);
    }
}
