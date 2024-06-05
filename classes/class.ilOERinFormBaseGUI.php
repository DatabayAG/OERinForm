<?php

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

    protected int $parent_ref_id;
    protected string $parent_type;
    protected string $parent_gui_class;
    protected ?ilObject $parent_obj;
    protected ilOERinFormPublishMD $md_obj;

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

        $this->parent_ref_id = (int) $_GET['ref_id'];
        $this->parent_type = ilObject::_lookupType($this->parent_ref_id, true);
        $this->parent_obj = ilObjectFactory::getInstanceByRefId($this->parent_ref_id);
        $this->parent_gui_class = ilObjectFactory::getClassByType($this->parent_type) . 'GUI';

        $this->md_obj = new ilOERinFormPublishMD($this->parent_obj->getId(), $this->parent_obj->getId(), $this->parent_type);
    }


    /**
     * Get the HTML code of a help button for a screen
     * The help id correspods to the config parameter with the help url
     */
    public function getHelpButton(string $a_help_id): string
    {
        $url = $this->config->get($a_help_id);
        if (!empty($url)) {
            $link = $this->factory->link()->bulky(
                $this->factory->symbol()->glyph()->help("#"),
                $this->lng->txt('help'),
                new \ILIAS\Data\URI($url)
            )->withOpenInNewViewport(true);
            return $this->renderer->render($link);
        }
        return '';
    }

    /**
     * Get the URL of the export tab of the current object
     */
    protected function getExportUrl(): string
    {
        $this->ctrl->saveParameterByClass('ilexportgui', 'ref_id');
        return $this->ctrl->getLinkTargetByClass(['ilrepositorygui', $this->parent_gui_class, 'ilexportgui']);
    }

    /**
     * Return to the export tab of the parent object
     */
    protected function returnToExport(): void
    {
        $this->ctrl->redirectToURL($this->getExportUrl());
    }

    /**
     * Return to the standard entry point of the object
     */
    protected function returnToObject(): void
    {
        $this->ctrl->redirectToURL(ilLink::_getLink($this->parent_ref_id));
    }


    /**
     * Get the link target for a command using the ui plugin router
     */
    protected function getLinkTarget(string $a_cmd = '', string $a_anchor = '', bool $a_async = false): string
    {
        return $this->ctrl->getLinkTargetByClass(['ilUIPluginRouterGUI', get_class($this)], $a_cmd, $a_anchor, $a_async);
    }
}
