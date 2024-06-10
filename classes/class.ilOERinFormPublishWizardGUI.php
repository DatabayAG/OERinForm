<?php

/**
 * Wizard for publishing workflow
 *  @ilCtrl_IsCalledBy ilOERinFormPublishWizardGUI: ilUIPluginRouterGUI
 */
class ilOERinFormPublishWizardGUI extends ilOERinFormBaseGUI
{
    #region basic

    protected ilLocatorGUI $locator;
    protected ilObjUser $user;

    protected ilOERinFormData $data;
    protected ilOERinFormPublishMD $md_obj;
    protected ilOERinFormMimeMailNotification $notification;
    protected ilMDSettings $md_settings;

    /**
     * Index of the current wizard step (set in determineStep)
     */
    protected int $stepindex = 0;

    /**
    * Data of currently visible step (set in determineStep)
    */
    protected array $step = [];

    /**
     * Status checks of steps
     * Key is the step number, starting with 0
     * Status is true if all checks for the step are passed
     * messages contains the translated failute messages for the step
     * @var array{array-key: int, array{status: bool, messages: string[]}
     */
    protected array $checks = [];

    /**
     * Is the object ready for being published?
     */
    protected bool $ready = false;



    /**
    * Definition of the wizard steps
    */
    protected $steps = [
        [
            'cmd' => 'describeMeta',                            // step command
            'save_cmd' => 'saveMeta',                           // command for saving data
            'help_id' => 'declare_meta',                        // parameter name for the help url
            'title_var' => 'describe_meta',                     // lang var for title
            'desc_var' => 'describe_meta_desc',                 // lang var for description
        ],
        [
            'cmd' => 'checkRights',
            'save_cmd' => 'saveRights',
            'help_id' => 'check_rights',
            'title_var' => 'check_rights',
            'desc_var' => 'check_rights_desc',
        ],
        [
            'cmd' => 'selectLicense',
            'save_cmd' => 'saveLicense',
            'help_id' => 'select_license',
            'title_var' => 'select_license',
            'desc_var' => 'select_license_desc',
        ],
        [
            'cmd' => 'checkAttrib',
            'save_cmd' => 'saveAttrib',
            'help_id' => 'check_attrib',
            'title_var' => 'check_attrib',
            'desc_var' => 'check_attrib_desc',
        ],
        [
            'cmd' => 'declarePublish',
            'save_cmd' => 'savePublish',
            'help_id' => 'final_publish',
            'title_var' => 'declare_publish',
            'desc_var' => 'declare_publish_desc',
        ]
    ];

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        global $DIC;
        $this->locator = $DIC['ilLocator'];
        $this->user = $DIC->user();
        $this->data = $this->plugin->getData($this->parent_obj_id);
        $this->md_obj = new ilOERinFormPublishMD($this->parent_obj_id, $this->parent_obj_id, $this->parent_type);
        $this->md_settings = ilMDSettings::_getInstance();
        $this->notification = new ilOERinFormMimeMailNotification();
    }


    /**
     * Execute a command (main entry point)
     */
    public function executeCommand(): void
    {
        if (!$this->access->checkAccess('write', '', $this->parent_ref_id)) {
            $this->tpl->setOnScreenMessage('failure', $this->lng->txt("permission_denied"), true);
            $this->returnToObject();
        }

        $cmd = $this->ctrl->getCmd('describeMeta');
        $this->determineStep($cmd);
        $this->ctrl->saveParameter($this, 'ref_id');
        $this->ctrl->setParameter($this, 'last_step', $this->stepindex);

        switch($cmd) {
            case 'checkRights':
            case 'saveRights':
            case 'selectLicense':
            case 'saveLicense':
            case 'describeMeta':
            case 'saveMeta':
            case 'checkAttrib':
            case 'saveAttrib':
            case 'declarePublish':
            case 'savePublish':
            case 'finalPublish':
            case 'previousStep':
            case 'nextStep':
            case 'keywordAutocomplete':
            case 'cancelPublish':
                $this->$cmd();
                break;

            default:
                $this->showPage('Unknown command ' . $cmd);
        }
    }

    /**
     * Set the current wizard step
     */
    protected function determineStep($a_cmd)
    {
        // take from request
        if (in_array($a_cmd, ['previousStep', 'nextStep', 'finalPublish'])
            && $this->http->wrapper()->query()->has('last_step')
        ) {
            $index = $this->http->wrapper()->query()->retrieve(
                'last_step',
                $this->refinery->kindlyTo()->int()
            );
            if (isset($this->steps[$index])) {
                $this->stepindex = $index;
                $this->step = $this->steps[$index];
                return;
            }
        }

        // take from command
        foreach ($this->steps as $index => $step) {
            if (in_array($a_cmd, [$step['cmd'] ?? '', $step['save_cmd'] ?? ''])) {
                $this->stepindex = $index;
                $this->step = $this->steps[$index];
                return;
            }
        }

        // take first step as default
        if (empty($step)) {
            $this->stepindex = 0;
            $this->step = $this->steps[0];
        }
    }

    /**
     * Save the current form and rediect to the previous step
     */
    protected function previousStep()
    {
        if (!empty($prev_cmd = $this->steps[$this->stepindex - 1]['cmd'])) {
            if (!empty($save_cmd = $this->step['save_cmd'] ?? null)) {
                $this->$save_cmd($prev_cmd);
            } else {
                $this->$prev_cmd();
            }
        } else {
            $this->ctrl->redirect($this);
        }
    }

    /**
     * Save the current form and rediect to the next step
     */
    protected function nextStep()
    {
        if (!empty($next_cmd = $this->steps[$this->stepindex + 1]['cmd'])) {
            if (!empty($save_cmd = $this->step['save_cmd'] ?? null)) {
                $this->$save_cmd($next_cmd);
            } else {
                $this->$next_cmd();
            }
        } else {
            $this->ctrl->redirect($this);
        }
    }


    /**
     * Propare a propery form for the current step
     * Opening and close tags are provided by the toolbar
     */
    protected function prepareStepForm(): ilPropertyFormGUI
    {
        $form = new ilPropertyFormGUI();
        $form->setOpenTag(false);
        $form->setCloseTag(false);
        $form->setFormAction($this->ctrl->getFormAction($this));
        return $form;
    }


    /**
     * Get the selected license with existing license as default
     */
    protected function getSelectedLicense(): string
    {
        $license = $this->data->get('selected_license');
        if (empty($license)) {
            $license = $this->md_obj->getCCLicense();
        }
        return (string) $license;
    }


    /**
     * Show the complete ILIAS page with the wizard content
     */
    protected function showPage(string $a_content = ''): void
    {
        $parent_obj = ilObjectFactory::getInstanceByRefId($this->parent_ref_id);

        $this->locator->addRepositoryItems($this->parent_ref_id);
        $this->locator->addItem($parent_obj->getTitle(), ilLink::_getLink($this->parent_ref_id, $this->parent_type));
        $this->tabs->setBackTarget($this->lng->txt('export'), $this->getExportUrl());

        $this->tpl->loadStandardTemplate();
        $this->tpl->setLocator();
        $this->tpl->setTitle($parent_obj->getPresentationTitle());
        $this->tpl->setDescription($parent_obj->getLongDescription());
        $this->tpl->setTitleIcon(ilObject::_getIcon(0, 'big', $this->parent_type), $this->lng->txt('obj_' . $this->parent_type));

        $this->checkAll();

        // show the step list

        $ui_steps = [];
        foreach ($this->steps as $index => $step) {
            $ui_step = $this->factory->listing()->workflow()->step(
                sprintf($this->plugin->txt("wizard_step"), $index + 1),
                $this->plugin->txt($step['title_var'] ?? ''),
                $this->ctrl->getLinkTarget($this, $step['cmd'] ?? '')
            );
            $ui_steps[] = $ui_step->withStatus((
                $this->checks[$index]['status'] ?? false
            ) ? $ui_step::SUCCESSFULLY : $ui_step::NOT_STARTED);
        }

        $right_components = [
            $this->factory->listing()->workflow()->linear($this->plugin->txt('publish_oer'), $ui_steps)
            ->withActive($this->stepindex)
        ];
        if (!empty($help = $this->getHelpButton($this->step['help_id'] ?? ''))) {
            $right_components[] = $this->factory->legacy('<br>');
            $right_components[] = $help;
        }

        $this->tpl->setRightContent($this->renderer->render($right_components));

        // show the main screen

        $tpl = $this->plugin->getTemplate("tpl.wizard_page.html");
        $tpl->setVariable("FORMACTION", $this->ctrl->getFormAction($this));
        $tpl->setVariable("FORMNAME", get_class($this));
        $tpl->setVariable("CONTENT", $a_content);

        // add step specific info and toolbar
        $info = $this->plugin->txt($this->step['desc_var'] ?? '');
        if (is_array($this->checks[$this->stepindex]['messages'])) {
            foreach ($this->checks[$this->stepindex]['messages'] as $message) {
                $info .= '<br />' . $this->getNotOkImage(16) . " " . $message;
            }
        }
        $this->tpl->setOnScreenMessage('info', $info);

        // add the navigation toolbar
        $tb = new ilToolbarGUI();
        $button = ilSubmitButton::getInstance();
        $button->setCaption($this->plugin->txt('wizard_previous'), false);
        $button->setCommand('previousStep');
        $button->setDisabled($this->stepindex == 0);
        $tb->addButtonInstance($button);

        if ($this->stepindex == count($this->steps) - 1) {
            $button = ilSubmitButton::getInstance();
            $button->setCaption($this->plugin->txt('wizard_finish'), false);
            $button->setCommand('finalPublish');
            $button->setDisabled(!$this->ready);
            $tb->addButtonInstance($button);

        } elseif ($this->stepindex <= count($this->steps)) {
            $button = ilSubmitButton::getInstance();
            $button->setCaption($this->plugin->txt('wizard_next'), false);
            $button->setCommand('nextStep');
            $tb->addButtonInstance($button);
        }

        $tb->addSeparator();
        $button = ilSubmitButton::getInstance();
        $button->setCaption($this->lng->txt('cancel'), false);
        $button->setCommand('cancelPublish');
        $tb->addButtonInstance($button);

        $tpl->setVariable("TOOLBAR", $tb->getHTML());

        $this->tpl->setContent($tpl->get());
        $this->tpl->printToStdout();
    }

    /**
     * Get the html of an icon showing success
     */
    protected function getOkImage(int $size = 32): string
    {
        $src = ilUtil::getImagePath('icon_ok.svg');
        return '<img src="' . $src . '" width="' . $size . '" alt="ok" />';
    }

    /**
     * Get the html of an icon showing a failure
     */
    protected function getNotOkImage(int $size = 32): string
    {
        $src = ilUtil::getImagePath('icon_not_ok.svg');
        return '<img src="' . $src . '" width="' . $size . '" alt="not_ok" />';
    }

    /**
     * Check all wizard data
     * Fills the checks array with results
     */
    protected function checkAll(): void
    {
        /** @see ilOERinFormData::$param_list for value names */
        $d = $this->data->getAllValues();

        // checks must be done in the order of the defined steps
        $this->checks = [];
        $ready = true;

        // step 1 (index 0)
        $ok = true;
        $messages = [];
        if (empty($title = ilObject::_lookupTitle($this->parent_obj_id))
            || empty($this->md_obj->getAuthors())) {
            $ok = false;
            $ready = false;
            $messages[] = $this->plugin->txt('fail_metadata');
        }
        if (!$d['noti_check'] || empty($d['noti_mail'])) {
            $ok = false;
            $ready = false;
            $messages[] = $this->plugin->txt('fail_notification');
        }
        $this->checks[] = ['status' => $ok, 'messages' => $messages];

        // step 2
        $ok = true;
        $messages = [];
        if (!($d['cr_schoepfung'] && (($d['cr_erschaffen'] && $d['cr_zustimmung']) || $d['cr_exklusiv']))) {
            $ok = false;
            $ready = false;
            $messages[] = $this->plugin->txt('fail_cr_berechtigung');
        }
        if (!($d['cr_persoenlichkeit'] && $d['cr_einwilligung'] && $d['cr_musik'] && $d['cr_marken'] && $d['cr_kontext'])) {
            $ok = false;
            $ready = false;
            $messages[] = $this->plugin->txt('fail_cr_sonstige_rechte');
        }
        $this->checks[] = ['status' => $ok, 'messages' => $messages];

        // step 3
        $ok = true;
        $messages = [];
        $license = $this->getSelectedLicense();
        if (empty($license) || !in_array($license, $this->md_obj->ccMixer($this->data->getIncludedLicenses()))) {
            $ok = false;
            $ready = false;
            $messages[] = $this->plugin->txt('fail_select_license');
        }
        $this->checks[] = ['status' => $ok, 'messages' => $messages];

        // step 4
        $ok = true;
        $messages = [];
        if (!($d['ca_lizenz_selbst'] && $d['ca_lizenz_link'] && $d['ca_lizenz_link'])) {
            $ok = false;
            $ready = false;
            $messages[] = $this->plugin->txt('fail_ca_selbst');
        }
        if (!($d['ca_urheber'] && $d['ca_miturheber'] && $d['ca_titel'] && $d['ca_lizenz_andere'] && $d['ca_aenderungen'])) {
            $ok = false;
            $ready = false;
            $messages[] = $this->plugin->txt('fail_ca_tullu');
        }
        if (!($d['ca_fotos'] && $d['ca_nichtoffen'] && $d['ca_zitat'] && $d['ca_nichtkomm'] && $d['ca_quellen_check'])) {
            $ok = false;
            $ready = false;
            $messages[] = $this->plugin->txt('fail_ca_weitere');
        }
        $this->checks[] = ['status' => $ok, 'messages' => $messages];

        // step 5
        $ok = true;
        $messages = [];
        if (!($d['cf_konsequenzen'] && $d['cf_bereit'])) {
            $ok = false;
            $ready = false;
            $messages[] = $this->plugin->txt('fail_check_final');
        }
        $this->checks[] = ['status' => $ok, 'messages' => $messages];
        $this->ready = $ready;
    }

    /**
    * Return to the parent GUI
    */
    protected function cancelPublish(): void
    {
        $this->returnToExport();
    }

    #endregion
    #region check_rights

    protected function checkRights(): void
    {
        $form = $this->initRightsCheckForm();
        $this->showPage($form->getHTML());
    }

    protected function saveRights($redirect_cmd = 'checkRights')
    {
        $form = $this->initRightsCheckForm();
        if ($form->checkInput()) {
            foreach (array_keys($this->data->getParamsBySection('check_rights')) as $name) {
                $this->data->set($name, $form->getInput($name));
            }
            $this->data->write();

            if ($redirect_cmd == 'checkRights') {
                $this->tpl->setOnScreenMessage('success', $this->lng->txt("saved_successfully"), true);
            }
            $this->ctrl->redirect($this, $redirect_cmd);
        } else {
            $form->setValuesByPost();
            $this->showPage($form->getHTML());
        }
    }

    protected function initRightsCheckForm(): ilPropertyFormGUI
    {
        $form = $this->prepareStepForm();
        foreach ($this->data->getParamsBySection('check_rights') as $name => $param) {
            $item = $param->getFormItem();
            $form->addItem($item);
        }
        $form->addCommandButton('saveRights', $this->lng->txt('save'));
        return $form;
    }

    #endregion
    #region select_license

    protected function selectLicense(): void
    {
        $form = $this->initLicenseSelectForm();
        $this->showPage($form->getHTML());
    }

    protected function saveLicense($redirect_cmd = 'selectLicense'): void
    {
        $form = $this->initLicenseSelectForm();
        if ($form->checkInput()) {
            foreach (array_keys($this->data->getParamsBySection('select_license')) as $name) {
                $this->data->set($name, $form->getInput($name));
            }
            $this->data->write();
            if ($redirect_cmd == 'selectLicense') {
                $this->tpl->setOnScreenMessage('success', $this->lng->txt("saved_successfully"), true);
            }
            $this->ctrl->redirect($this, $redirect_cmd);
        } else {
            $form->setValuesByPost();
            $this->showPage($form->getHTML());

        }
    }

    protected function initLicenseSelectForm(): ilPropertyFormGUI
    {
        $form = $this->prepareStepForm();
        foreach ($this->data->getParamsBySection('select_license') as $name => $param) {
            if ($name != 'selected_license') {
                $item = $param->getFormItem();
                $form->addItem($item);
            }
        }

        $radio = new ilRadioGroupInputGUI($this->plugin->txt('selected_license'), 'selected_license');
        $option = new ilRadioOption($this->plugin->txt('no_cc_license'), 'no_cc_license', $this->plugin->txt('no_cc_license_info'));
        $radio->addOption($option);
        $radio->setValue('no_cc_license');

        $allowed_licenses = $this->md_obj->ccMixer($this->data->getIncludedLicenses());
        $selected_license = $this->getSelectedLicense();

        foreach ($this->md_obj->getAvailableCCLicenses() as $cc => $value) {
            switch ($cc) {
                case ilOERinFormPublishMD::CC0:
                    $title = $this->plugin->txt('sl_cc0');
                    $info = $this->plugin->txt('sl_cc0_info');
                    break;
                case ilOERinFormPublishMD::CC_BY:
                    $title = $this->plugin->txt('sl_cc_by');
                    $info = $this->plugin->txt('sl_cc_by_info');
                    break;
                case ilOERinFormPublishMD::CC_BY_SA:
                    $title = $this->plugin->txt('sl_cc_by_sa');
                    $info = $this->plugin->txt('sl_cc_by_sa_info');
                    break;
                case ilOERinFormPublishMD::CC_BY_ND:
                    $title = $this->plugin->txt('sl_cc_by_nd');
                    $info = $this->plugin->txt('sl_cc_by_nd_info');
                    break;
                case ilOERinFormPublishMD::CC_BY_NC:
                    $title = $this->plugin->txt('sl_cc_by_nc');
                    $info = $this->plugin->txt('sl_cc_by_nc_info');
                    break;
                case ilOERinFormPublishMD::CC_BY_NC_SA:
                    $title = $this->plugin->txt('sl_cc_by_nc_sa');
                    $info = $this->plugin->txt('sl_cc_by_nc_sa_info');
                    break;
                case ilOERinFormPublishMD::CC_BY_NC_ND:
                    $title = $this->plugin->txt('sl_cc_by_nc_nd');
                    $info = $this->plugin->txt('sl_cc_by_nc_nd_info');
                    break;
            }
            if (!in_array($cc, $allowed_licenses)) {
                $title .= ' ' . $this->getNotOkImage(12);
                $disabled = true;
            } else {
                $title .= ' ' . $this->getOkImage(12);
                $disabled = false;
            }
            $option = new ilRadioOption($title, $cc, $info);
            $option->setDisabled($disabled);

            $radio->addOption($option);

            if ($selected_license == $cc) {
                $radio->setValue($selected_license);
            }
        }
        $form->addItem($radio);

        $form->addCommandButton('saveLicense', $this->plugin->txt('save_and_check'));
        return $form;
    }

    #endregion
    #region describe_meta

    protected function describeMeta(): void
    {
        $form = $this->initMetaEditForm();
        $this->showPage($form->getHTML());
    }

    /**
     * @see \ilMDEditorGUI::updateQuickEdit
     */
    protected function saveMeta($redirect_cmd = 'describeMeta'): void
    {
        $form = $this->initMetaEditForm();
        if (!$form->checkInput()) {
            $form->setValuesByPost();
            $this->showPage($form->getHTML());
            return;
        }


        // General values, should already be created in the form creation
        $general = $this->md_obj->getGeneral();
        $general->setTitle($form->getInput('gen_title'));
        $general->update();

        // Language
        $has_language = false;
        foreach ($ids = $general->getLanguageIds() as $id) {
            $md_lan = $general->getLanguage($id);
            $md_lan->setLanguage(
                new ilMDLanguageItem(
                    $form->getInput('gen_language_' . $id . '_language')
                )
            );
            $md_lan->update();
            $has_language = true;
        }
        if (!$has_language) {
            $md_lan = $general->addLanguage();
            $md_lan->setLanguage(
                new ilMDLanguageItem(
                    $form->getInput('gen_language_language')
                )
            );
            $md_lan->save();
        }


        // Description
        $first_description = null;
        foreach ($ids = $general->getDescriptionIds() as $id) {
            $md_des = $general->getDescription($id);
            $md_des->setDescription($form->getInput('gen_description_' . $id . '_description'));
            $md_des->update();
            if (!isset($first_description)) {
                $first_description = $form->getInput('gen_description_' . $id . '_description');
            }
        }

        // Copy title and first description to the object data
        $object = ilObjectFactory::getInstanceByObjId($this->parent_obj_id);
        $object->setTitle($form->getInput('gen_title'));
        $object->setDescription((string) $first_description);
        $object->update();

        // Keyword
        $keywords = [];
        if ($this->http->wrapper()->post()->has('keywords')) {
            $keywords = (array) $this->http->wrapper()->post()->retrieve(
                'keywords',
                $this->refinery->identity()
            );
        }
        $keyword_values = $keywords['value'] ?? null;
        if (is_array($keyword_values)) {
            ilMDKeyword::updateKeywords($general, $keyword_values);
        }

        //Educational...
        // Typical Learning Time
        $tlt = $form->getInput('tlt');
        $tlt_set = false;
        $tlt_post_vars = $this->getTltPostVars();
        foreach ($tlt_post_vars as $post_var) {
            $tlt_section = (int) ($tlt[$post_var] ?? 0);
            if ($tlt_section > 0) {
                $tlt_set = true;
                break;
            }
        }
        if ($tlt_set) {
            if (!is_object($educational = $this->md_obj->getEducational())) {
                $educational = $this->md_obj->addEducational();
                $educational->save();
            }
            $educational->setPhysicalTypicalLearningTime(
                (int) ($tlt[$tlt_post_vars['mo']] ?? 0),
                (int) ($tlt[$tlt_post_vars['d']] ?? 0),
                (int) ($tlt[$tlt_post_vars['h']] ?? 0),
                (int) ($tlt[$tlt_post_vars['m']] ?? 0),
                (int) ($tlt[$tlt_post_vars['s']] ?? 0)
            );
            $educational->update();
        } elseif (is_object($educational = $this->md_obj->getEducational())) {
            $educational->setPhysicalTypicalLearningTime(0, 0, 0, 0, 0);
            $educational->update();
        }

        //Lifecycle...
        // Authors
        if ($form->getInput('life_authors') !== '') {
            if (!is_object($lifecycle = $this->md_obj->getLifecycle())) {
                $lifecycle = $this->md_obj->addLifecycle();
                $lifecycle->save();
            }

            // determine all entered authors
            $life_authors = $form->getInput('life_authors');
            $auth_arr = explode($this->md_settings->getDelimiter(), $life_authors);
            for ($i = 0, $iMax = count($auth_arr); $i < $iMax; $i++) {
                $auth_arr[$i] = trim($auth_arr[$i]);
            }

            $md_con_author = "";

            // update existing author entries (delete if not entered)
            foreach (($ids = $lifecycle->getContributeIds()) as $con_id) {
                $md_con = $lifecycle->getContribute($con_id);
                if ($md_con->getRole() === "Author") {
                    foreach ($ent_ids = $md_con->getEntityIds() as $ent_id) {
                        $md_ent = $md_con->getEntity($ent_id);

                        // entered author already exists
                        if (in_array($md_ent->getEntity(), $auth_arr, true)) {
                            unset($auth_arr[array_search($md_ent->getEntity(), $auth_arr, true)]);
                        } else {  // existing author has not been entered again -> delete
                            $md_ent->delete();
                        }
                    }
                    $md_con_author = $md_con;
                }
            }

            // insert enterd, but not existing authors
            if (count($auth_arr) > 0) {
                if (!is_object($md_con_author)) {
                    $md_con_author = $lifecycle->addContribute();
                    $md_con_author->setRole("Author");
                    $md_con_author->save();
                }
                foreach ($auth_arr as $auth) {
                    $md_ent = $md_con_author->addEntity();
                    $md_ent->setEntity(ilUtil::stripSlashes($auth));
                    $md_ent->save();
                }
            }
        } elseif (is_object($lifecycle = $this->md_obj->getLifecycle())) {
            foreach (($ids = $lifecycle->getContributeIds()) as $con_id) {
                $md_con = $lifecycle->getContribute($con_id);
                if ($md_con->getRole() === "Author") {
                    $md_con->delete();
                }
            }
        }

        foreach (array_keys($this->data->getParamsBySection('notification')) as $name) {
            $this->data->set($name, $form->getInput($name));
        }
        $this->data->write();

        if ($redirect_cmd == 'describeMeta') {
            $this->tpl->setOnScreenMessage('success', $this->lng->txt('saved_successfully'), true);
        }
        $this->ctrl->redirect($this, $redirect_cmd);
    }

    /**
     * @see ilMDEditorGUI::initQuickEditForm
     */
    protected function initMetaEditForm(): ilPropertyFormGUI
    {
        $this->lng->loadLanguageModule('meta');
        $general = $this->md_obj->getGeneral();
        if(!isset($general)) {
            $general = $this->md_obj->addGeneral();
            $general->save();
        }
        $form = new ilPropertyFormGUI();
        $form->setOpenTag(false);
        $form->setCloseTag(false);

        // title
        $ti = new ilTextInputGUI($this->lng->txt("title"), "gen_title");
        $ti->setMaxLength(200);
        $ti->setSize(50);
        if($this->md_obj->getObjType() != 'sess') {
            $ti->setRequired(true);
        }
        $ti->setValue(empty($general->getTitle()) ? ilObject::_lookupTitle($this->parent_obj_id) : $general->getTitle());
        $form->addItem($ti);

        // description(s)
        foreach($ids = $general->getDescriptionIds() as $id) {
            $md_des = $general->getDescription($id);

            $ta = new ilTextAreaInputGUI(
                $this->lng->txt("meta_description"),
                'gen_description_' . $id . '_description'
            );
            $ta->setCols(50);
            $ta->setRows(4);
            $ta->setValue($md_des->getDescription());
            if (count($ids) > 1) {
                $ta->setInfo($this->lng->txt("meta_l_" . $md_des->getDescriptionLanguageCode()));
            }

            $form->addItem($ta);
        }

        // language(s)
        $first = true;
        $first_lang = $this->lng->getLangKey();
        $options = ilMDLanguageItem::_getLanguages();
        foreach($ids = $general->getLanguageIds() as $id) {
            $md_lan = $general->getLanguage($id);
            $first_lang = $md_lan->getLanguageCode();
            $si = new ilSelectInputGUI($this->lng->txt("meta_language"), 'gen_language_' . $id . '_language');
            $si->setOptions($options);
            $si->setValue($md_lan->getLanguageCode());
            $form->addItem($si);
            $first = false;
        }
        if ($first) {
            $si = new ilSelectInputGUI($this->lng->txt("meta_language"), "gen_language_language");
            $si->setOptions($options);
            $si->setValue($this->lng->getLangKey());
            $form->addItem($si);
        }

        // keyword(s)
        $first = true;
        $keywords = array();
        foreach($ids = $general->getKeywordIds() as $id) {
            $md_key = $general->getKeyword($id);
            if (trim($md_key->getKeyword()) != "") {
                $keywords[$md_key->getKeywordLanguageCode()][] = $md_key->getKeyword();
            }
        }
        foreach($keywords as $lang => $keyword_set) {
            $kw = new ilTextInputGUI(
                $this->lng->txt("keywords"),
                "keywords[value][" . $lang . "]"
            );
            $kw->setDataSource($this->ctrl->getLinkTarget($this, "keywordAutocomplete", "", true));
            $kw->setMaxLength(200);
            $kw->setSize(50);
            $kw->setMulti(true);
            if (count($keywords) > 1) {
                $kw->setInfo($this->lng->txt("meta_l_" . $lang));
            }
            $form->addItem($kw);
            asort($keyword_set);
            $kw->setValue($keyword_set);
        }
        if ($keywords === []) {
            $kw = new ilTextInputGUI(
                $this->lng->txt("keywords"),
                "keywords[value][" . $first_lang . "]"
            );
            $kw->setDataSource($this->ctrl->getLinkTarget($this, "keywordAutocomplete", "", true));
            $kw->setMaxLength(200);
            $kw->setSize(50);
            $kw->setMulti(true);
            $form->addItem($kw);
        }

        // Lifecycle...
        // Authors
        $ta = new ilTextAreaInputGUI(
            $this->lng->txt('authors') . "<br />" .
            "(" . sprintf($this->lng->txt('md_separated_by'), $this->md_settings->getDelimiter()) . ")",
            "life_authors"
        );
        $ta->setCols(50);
        $ta->setRows(2);
        $ta->setValue($this->md_obj->getAuthors());
        $form->addItem($ta);


        // typical learning time
        $tlt = new ilTypicalLearningTimeInputGUI($this->lng->txt("meta_typical_learning_time"), "tlt");
        $edu = $this->md_obj->getEducational();
        if (is_object($edu)) {
            $tlt->setValueByLOMDuration($edu->getTypicalLearningTime());
        }
        $form->addItem($tlt);

        // notification
        $params = $this->data->getParamsBySection('notification');
        $head = $params['noti_head']->getFormItem();
        $form->addItem($head);
        $check = $params['noti_check']->getFormItem();
        $form->addItem($check);
        $mail = $params['noti_mail']->getFormItem();
        $check->addSubItem($mail);

        $form->setTitle($this->lng->txt("description"));
        $form->setFormAction($this->ctrl->getFormAction($this));
        $form->addCommandButton('saveMeta', $this->lng->txt('save'));

        return $form;
    }

    /**
     * @see ilMDEditorGUI::keywordAutocomplete()
     */
    protected function keywordAutocomplete(): void
    {
        $term = '';
        if ($this->http->wrapper()->query()->has('term')) {
            $term = $this->http->wrapper()->query()->retrieve(
                'term',
                $this->refinery->kindlyTo()->string()
            );
        }

        $res = ilMDKeyword::_getMatchingKeywords(
            ilUtil::stripSlashes($term),
            $this->md_obj->getObjType(),
            $this->md_obj->getRBACId()
        );

        $result = array();
        $cnt = 0;
        foreach ($res as $r) {
            if ($cnt++ > 19) {
                continue;
            }
            $entry = new stdClass();
            $entry->value = $r;
            $entry->label = $r;
            $result[] = $entry;
        }

        echo json_encode($result, JSON_THROW_ON_ERROR);
        exit;
    }


    /**
     * @see \ilMDEditorGUI::getTltPostVars
     */
    protected function getTltPostVars(): array
    {
        return [
            'mo' => ilTypicalLearningTimeInputGUI::POST_NAME_MONTH,
            'd' => ilTypicalLearningTimeInputGUI::POST_NAME_DAY,
            'h' => ilTypicalLearningTimeInputGUI::POST_NAME_HOUR,
            'm' => ilTypicalLearningTimeInputGUI::POST_NAME_MINUTE,
            's' => ilTypicalLearningTimeInputGUI::POST_NAME_SECOND
        ];
    }


    #endregion
    #region check_attrib

    protected function checkAttrib(): void
    {
        $form = $this->initAttribCheckForm();
        $this->showPage($form->getHTML());
    }

    protected function initAttribCheckForm(): ilPropertyFormGUI
    {
        $form = $this->prepareStepForm();
        foreach ($this->data->getParamsBySection('check_attrib') as $name => $param) {
            $item = $param->getFormItem();
            $form->addItem($item);
        }
        $form->addCommandButton('saveAttrib', $this->lng->txt('save'));
        return $form;
    }

    protected function saveAttrib($redirect_cmd = 'checkAttrib'): void
    {
        $form = $this->initAttribCheckForm();
        if ($form->checkInput()) {
            foreach (array_keys($this->data->getParamsBySection('check_attrib')) as $name) {
                $this->data->set($name, $form->getInput($name));
            }
            $this->data->write();

            if ($redirect_cmd == 'checkAttrib') {
                $this->tpl->setOnScreenMessage('success', $this->lng->txt("saved_successfully"), true);
            }
            $this->ctrl->redirect($this, $redirect_cmd);
        }
        $form->setValuesByPost();
        $this->showPage($form->getHTML());
    }

    #endregion
    #region declare_publish

    protected function declarePublish(): void
    {
        $form = $this->initPublishForm();
        $this->showPage($form->getHTML());
    }

    protected function savePublish($redirect_cmd = 'declarePublish'): void
    {
        $form = $this->initPublishForm();
        if ($form->checkInput()) {
            foreach (array_keys($this->data->getParamsBySection('check_final')) as $name) {
                $this->data->set($name, $form->getInput($name));
            }
            $this->data->write();
            if ($redirect_cmd == 'declarePublish') {
                $this->tpl->setOnScreenMessage('success', $this->lng->txt("saved_successfully"), true);
            }
            $this->ctrl->redirect($this, $redirect_cmd);
        }

        $form->setValuesByPost();
        $this->showPage($form->getHTML());
    }


    protected function initPublishForm(): ilPropertyFormGUI
    {
        $form = new ilPropertyFormGUI();
        $form->setOpenTag(false);
        $form->setCloseTag(false);
        $form->setFormAction($this->ctrl->getFormAction($this));
        $form->addCommandButton('savePublish', $this->lng->txt('save'));

        foreach ($this->data->getParamsBySection('check_final') as $name => $param) {
            $item = $param->getFormItem();
            $form->addItem($item);
        }

        $license = $this->getSelectedLicense();
        $available = $this->md_obj->getAvailableCCLicenses();
        if (isset($available[$license])) {
            $item = new ilNonEditableValueGUI($this->plugin->txt('selected_license'), '', true);
            $item->setValue(ilMDUtils::_parseCopyright($available[$license]));
            $form->addItem($item);
        }

        return $form;
    }

    protected function finalPublish(): void
    {
        // finally check if all steps are passed
        $this->checkAll();
        if (!$this->ready) {
            $this->tpl->setOnScreenMessage('failure', $this->plugin->txt('public_ref_not_created'), true);
            $this->ctrl->redirect($this, 'declarePublish');
        }

        // set the new cc license
        $license = $this->getSelectedLicense();
        $available = $this->md_obj->getAvailableCCLicenses();
        if (isset($available[$license])) {
            if(!is_object($rights = $this->md_obj->getRights())) {
                $rights = $this->md_obj->addRights();
                $rights->save();
            }
            $rights->setCopyrightAndOtherRestrictions("Yes");
            $rights->setDescription($available[$license]);
            $rights->update();
        }

        // create the reference in the public category
        $public_ref_id = $this->md_obj->createPublicRefId($this->parent_obj_id);
        if (!isset($public_ref_id)) {
            $this->tpl->setOnScreenMessage('failure', $this->plugin->txt('public_ref_not_created'), true);
            $this->returnToExport();
        }

        if (!$this->md_obj->isPublicRefIdPublic()) {
            $this->tpl->setOnScreenMessage('failure', $this->plugin->txt('public_ref_not_public'), true);
            $this->returnToExport();
        }

        // create the files for publishing by an external oai service
        $this->md_obj->publish();

        // send the notification
        if (!$this->notification->sendPublishNotification($public_ref_id, (string) $this->data->get('noti_mail'), $this->user)) {
            $this->tpl->setOnScreenMessage('failure', $this->plugin->txt('publish_notification_failed'), true);
            $this->returnToExport();
        }

        $this->tpl->setOnScreenMessage('success', $this->plugin->txt('published_and_notified'), true);
        $this->returnToExport();
    }


    #endregion
}
