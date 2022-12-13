<?php
// Copyright (c) 2017 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg, GPLv3, see LICENSE

require_once('Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/OERinForm/classes/class.ilOERinFormBaseGUI.php');

/**
 * Help GUI functions for OerInform
 *
 * @author Fred Neumann <fred.neumann@fau.de>
 *
 *  @ilCtrl_isCalledBy ilOERinFormHelpGUI: ilUIPluginRouterGUI
 */
class ilOERinFormHelpGUI extends ilOERinFormBaseGUI
{

    /** @var ilOERinFormHelp */
    protected $help;

    /**
     * ilOERinFormHelp constructor.
     * @param $plugin
     */
    public function __construct()
    {
        parent::__construct();
        $this->help = $this->plugin->getHelp();
    }

    /**
     * Handles all commands
     */
    public function executeCommand()
    {
        $cmd = $this->ctrl->getCmd('showHelpPage');
        switch ($cmd)
        {
            case "showHelp":
                $this->$cmd();
                break;
        }
    }

    /**
     * Get the help button for a help id
     * @return string
     */
    public function getHelpButton($help_id)
    {
        global $DIC;
        $factory = $DIC->ui()->factory();
        $renderer = $DIC->ui()->renderer();

        if ($ref_id = $this->help->getWikiRefId()) {

            $button = ilLinkButton::getInstance();
            $button->setCaption($this->lng->txt('help'), false);
            $button->setUrl(ilLink::_getStaticLink($ref_id));
            $button->setTarget('_blank');

            return $button->getToolbarHTML();
        }

        if ($this->help->isPageAvailable($help_id))
        {
            $modal = $this->getHelpModal($help_id);
            $button = $factory->button()->standard($this->lng->txt('help'), '')
                ->withOnClick($modal->getShowSignal());

            return $renderer->render(array($button, $modal));
        }

        return '';
    }


    /**
     * Get the help modal for a help id
     */
    protected function getHelpModal($help_id)
    {
        global $DIC;
        $factory = $DIC->ui()->factory();

        $this->ctrl->setParameterByClass(get_class($this), 'help_id', $help_id);

        $modal = $modal = $factory->modal()->roundtrip($this->lng->txt('help'), $factory->legacy(''))
        ->withAsyncRenderUrl($this->getLinkTarget('showHelp', '', true));

        return $modal;
    }


    /**
     * Show the actual help modal asynchronously
     */
    protected function showHelp()
    {
        global $DIC;
        $factory = $DIC->ui()->factory();
        $renderer = $DIC->ui()->renderer();

        $page_id = $this->help->getPageId($_GET['help_id']);

        $body = $this->plugin->txt('help_page_not_found');
        if  (!empty($page_id))
        {
            $tpl = $this->plugin->getTemplate('tpl.help_page.html');

            //
            // Prepare the controller for links on the presented wiki page (tricky)
            //
            $this->ctrl->initBaseClass('ilwikihandlergui');
            $this->ctrl->getCallStructure('ilwikihandlergui');
            $this->ctrl->setParameterByClass('ilobjwikigui', 'ref_id', $this->help->getWikiRefId());
            $array = $this->ctrl->getParameterArrayByClass(['ilwikihandlergui','ilobjwikigui']);
            $this->ctrl->current_node = $array['cmdNode'];

            $page_gui = new ilWikiPageGUI($page_id);
            if (isset($page_gui))
            {
                $page_gui->setTemplateOutput(false);
                $page_gui->setOutputMode(IL_PAGE_PRINT);
                $page_gui->setEnabledTabs(false);

                $this->tpl->addCss('Services/COPage/css/content.css"');
                $tpl->setVariable('CONTENT', $page_gui->getHTML());
            } else {
                $tpl->setVariable('CONTENT', 'not found');
            }

            $body = $tpl->get();
        }

        $modal = $factory->modal()->roundtrip($this->lng->txt('help'), $factory->legacy($body))->withCancelButtonLabel('close');

        if ($this->help->isWikiReadable())
        {
            $button = $factory->button()->standard($this->plugin->txt('more_help'), $this->help->getDetailsUrl($_GET['help_id']));
            $modal = $modal->withActionButtons(array($button));
        }

        echo $renderer->render($modal);
        exit;
    }
}