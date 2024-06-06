<?php

/**
 * Base class for publishing GUIs
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
    protected \ILIAS\HTTP\Services $http;
    protected \ILIAS\Refinery\Factory $refinery;

    protected int $parent_ref_id = 0;
    protected int $parent_obj_id = 0;
    protected string $parent_type = '';
    protected string $parent_gui_class = '';

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
        $this->http = $DIC->http();
        $this->refinery = $DIC->refinery();

        $this->plugin = ilOERinFormPlugin::getInstance();
        $this->config = $this->plugin->getConfig();

        if ($this->http->wrapper()->query()->has('ref_id')) {
            $this->parent_ref_id = $this->http->wrapper()->query()->retrieve(
                'ref_id',
                $this->refinery->kindlyTo()->int()
            );
        }

        $this->parent_obj_id = ilObject::_lookupObjectId($this->parent_ref_id);
        $this->parent_type = ilObject::_lookupType($this->parent_obj_id);
        $this->parent_gui_class = ilObjectFactory::getClassByType($this->parent_type) . 'GUI';
    }


    /**
     * Get the ui component of a help button for a screen
     * The help id is equal to the name of the config parameter with the help url
     */
    public function getHelpButton(string $a_help_id): ?ILIAS\UI\Component\Component
    {
        $url = $this->config->get($a_help_id);
        if (!empty($url)) {
            $link = $this->factory->link()->bulky(
                $this->factory->symbol()->glyph()->help(),
                $this->lng->txt('help'),
                new \ILIAS\Data\URI($url)
            )->withOpenInNewViewport(true);
            return $link;
        }
        return null;
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
}
