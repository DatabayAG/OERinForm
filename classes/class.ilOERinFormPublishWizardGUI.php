<?php

/**
* Wizard for publishing workflow
*/
class ilOERinFormPublishWizardGUI extends ilOERinFormBaseGUI
{
    #region basic

    protected ilOERinFormPublishGUI $publish_gui;
    protected ilOERinFormPublishMD $md_obj;
    protected ilObject $parent_obj;

    /**
    * Currently executed command (set in executeCommand)
    */
    protected string $cmd = '';

    /**
    * Data of currently visible step (set in executeCommand)
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
     * Publishing data that is saved for the object
     */
    protected ilOERinFormData $data;

    /**
    * Definition of the wizard steps
    */
    protected $steps = [
        [
                'cmd' => 'checkRights',             			// step command
                'help_id' => 'check_rights',
                'title_var' => 'check_rights',  				// lang var for title
                'desc_var' => 'check_rights_desc',      		// lang var for description
                'next_cmd' => 'saveRightsAndSelectLicense',    	// command of next step
        ],
        [
                'cmd' => 'selectLicense',
                'help_id' => 'select_license',
                'title_var' => 'select_license',
                'desc_var' => 'select_license_desc',
                'next_cmd' => 'saveLicenseAndDescribeMeta',
        ],
        [
                'cmd' => 'describeMeta',
                'help_id' => 'declare_meta',
                'title_var' => 'describe_meta',
                'desc_var' => 'describe_meta_desc',
                'next_cmd' => 'saveMetaAndCheckAttrib',
        ],
        [
                'cmd' => 'checkAttrib',
                'help_id' => 'check_attrib',
                'title_var' => 'check_attrib',
                'desc_var' => 'check_attrib_desc',
                'next_cmd' => 'saveAttribAndDeclarePublish',
        ],
        [
                'cmd' => 'declarePublish',
                'help_id' => 'final_publish',
                'title_var' => 'declare_publish',
                'desc_var' => 'declare_publish_desc',
                'next_cmd' => 'saveAndPublish',
        ]
    ];


    /**
    * Constructor
    * @access public
    */
    public function __construct(ilOERinFormPublishGUI $publish_gui, ilObject $parent_obj)
    {
        parent::__construct();

        $this->publish_gui = $publish_gui;
        $this->parent_obj = $parent_obj;

        $this->md_obj = new ilOERinFormPublishMD($this->parent_obj->getId(), $this->parent_obj->getId(), $this->parent_obj->getType());
        $this->data = $this->plugin->getData($this->parent_obj->getId());
    }


    /**
     * Execute a command (main entry point)
     * todo: remove GET access, find better solution for return
    */
    public function executeCommand(string $a_cmd = ''): void
    {
        $this->ctrl->setParameter($this, 'return', urlencode($_GET['return']));

        // get the current command
        $cmd = $a_cmd ? $a_cmd : $this->ctrl->getCmd('checkRights');

        // simple command
        $this->cmd = $cmd;
        $this->step = $this->getStepByCommand($cmd);
        $this->$cmd();
    }


    /**
    * Get a step by command
    * Commands without visible steps will return an empty array
    */
    protected function getStepByCommand(string $a_cmd): array
    {
        foreach ($this->steps as $step) {
            if ($step['cmd'] == $a_cmd) {
                return $step;
            }
        }
        return [];
    }

    /**
    * Get the form name used for the wizard
    */
    protected function getFormName(): string
    {
        return get_class($this);
    }


    /**
     * Show the complete ILIAS page with the wizard content
     */
    protected function output(string $a_html = ''): void
    {
        $this->checkAll();

        // show step list and determine the current step number
        $tpl = $this->plugin->getTemplate("tpl.wizard_steps.html");
        for ($i = 0; $i < count($this->steps); $i++) {
            if ($this->steps[$i]['cmd'] == $this->cmd) {
                $tpl->setCurrentBlock('strong');
                $stepindex = $i;
                $stepnum = $i + 1;
            } else {
                $tpl->setCurrentBlock('normal');
                $tpl->setVariable('LINK', $this->ctrl->getLinkTarget($this, $this->steps[$i]['cmd']));
            }
            $title = $this->plugin->txt($this->steps[$i]['title_var']);
            $step = sprintf($this->plugin->txt("wizard_step"), $i + 1);
            if ($this->checks[$i]['status']) {
                $step = $this->getOkImage(16) . " " . $step;
            } else {
                $step = $this->getNotOkImage(16) . " " . $step;
            }

            $tpl->setVariable("TITLE", $title);
            $tpl->setVariable("STEP", $step);
            $tpl->parseCurrentBlock();
            $tpl->setCurrentBlock("stepline");
            $tpl->parseCurrentBlock();
        }
        $tpl->setVariable("HEADER", $this->plugin->txt('publish_oer'));
        $tpl->setVariable('HELP', $this->publish_gui->getHelpButton($this->step['help_id']));
        $this->tpl->setRightContent($tpl->get());


        // show the main screen
        $tpl = $this->plugin->getTemplate("tpl.wizard_page.html");
        $tpl->setVariable("FORMACTION", $this->ctrl->getFormAction($this));
        $tpl->setVariable("FORMNAME", $this->getFormName());
        $tpl->setVariable("CONTENT", $a_html);

        // add step specific info and toolbar
        if ($this->step['cmd']) {
            $tpl->setVariable("STEP", sprintf($this->plugin->txt("wizard_step"), $stepnum));

            $info = $this->plugin->txt($this->step['desc_var']);
            if (is_array($this->checks[$stepindex]['messages'])) {
                foreach ($this->checks[$stepindex]['messages'] as $message) {
                    $info .= '<br />' . $this->getNotOkImage(16) . " " . $message;
                }
            }
            $this->tpl->setOnScreenMessage('info', $info);

            $tb = new ilToolbarGUI();
            if ($this->step['next_cmd'] and $stepnum == count($this->steps)) {
                $button = ilSubmitButton::getInstance();
                $button->setCaption(sprintf($this->plugin->txt('wizard_finish'), $stepnum + 1), false);
                $button->setCommand($this->step['next_cmd']);
                if ($this->ready) {
                    $button->setPrimary(true);
                } else {
                    $button->setDisabled(true);
                }

                $tb->addButtonInstance($button);
            } elseif ($this->step['next_cmd']) {
                $button = ilSubmitButton::getInstance();
                $button->setCaption(sprintf($this->plugin->txt('wizard_next'), $stepnum + 1), false);
                $button->setCommand($this->step['next_cmd']);
                $button->setPrimary(true);
                $tb->addButtonInstance($button);
            }

            $tb->addSeparator();
            $button = ilSubmitButton::getInstance();
            $button->setCaption($this->lng->txt('cancel'), false);
            $button->setCommand('returnToParent');
            $tb->addButtonInstance($button);

            $tpl->setVariable("TOOLBAR", $tb->getHTML());
        }

        $this->tabs->setBackTarget($this->lng->txt('export'), $_GET['return']);

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
     * Check all wizarrd data
     * Fills the checks array with results
     */
    protected function checkAll(): void
    {
        $d = $this->data->getAllValues();
        $this->checks = [];
        $ready = true;

        // step 1
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

        // step 2
        $ok = true;
        $messages = [];
        $license = $this->md_obj->getCCLicense();
        if (empty($license) || !in_array($license, $this->md_obj->ccMixer($this->data->getIncludedLicenses()))) {
            $ok = false;
            $ready = false;
            $messages[] = $this->plugin->txt('fail_select_license');
        }
        $this->checks[] = ['status' => $ok, 'messages' => $messages];

        // step 3
        $ok = true;
        $messages = [];
        if (empty($this->parent_obj->getTitle())
            || empty($this->md_obj->getAuthors())) {
            $ok = false;
            $ready = false;
            $messages[] = $this->plugin->txt('fail_metadata');
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
        if (!($d['ca_fotos'] && $d['ca_nichtoffen'] && $d['ca_zitat'] && $d['ca_nichtkomm'] && $d['ca_quellen_check'] && $d['ca_quellen_doku'])) {
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
    protected function returnToParent(): void
    {
        $this->ctrl->redirectToURL($_GET['return']);
    }

    #endregion
    #region check_rights

    protected function checkRights(): void
    {
        $form = $this->initRightsCheckForm();
        $this->output($form->getHTML());
    }

    protected function saveRights()
    {
        if ($this->updateRights()) {
            $this->tpl->setOnScreenMessage('success', $this->lng->txt("saved_successfully"), true);
            $this->ctrl->redirect($this, 'checkRights');
        }
    }

    protected function saveRightsAndSelectLicense()
    {
        if ($this->updateRights()) {
            $this->ctrl->redirect($this, 'selectLicense');
        }
    }

    protected function initRightsCheckForm(): ilPropertyFormGUI
    {
        $form = new ilPropertyFormGUI();
        $form->setOpenTag(false);
        $form->setCloseTag(false);
        $form->setFormAction($this->ctrl->getFormAction($this));
        $form->addCommandButton('saveRights', $this->lng->txt('save'));

        foreach ($this->data->getParamsBySection('check_rights') as $name => $param) {
            $item = $param->getFormItem();
            $form->addItem($item);
        }
        return $form;
    }

    protected function updateRights(): bool
    {
        $form = $this->initRightsCheckForm();
        if ($form->checkInput()) {
            foreach (array_keys($this->data->getParamsBySection('check_rights')) as $name) {
                $this->data->set($name, $form->getInput($name));
            }
            $this->data->write();
            return true;
        }

        $this->cmd = 'checkRights';
        $this->step = $this->getStepByCommand($this->cmd);
        $form->setValuesByPost();
        $this->output($form->getHTML());
        return false;
    }

    #endregion
    #region select_license

    protected function selectLicense(): void
    {
        $form = $this->initLicenseSelectForm();
        $this->output($form->getHTML());
    }

    protected function saveLicense(): void
    {
        if ($this->updateLicense()) {
            $this->tpl->setOnScreenMessage('success', $this->lng->txt("saved_successfully"), true);
            $this->ctrl->redirect($this, 'selectLicense');
        }
    }

    protected function saveLicenseAndDescribeMeta(): void
    {
        if ($this->updateLicense()) {
            $this->ctrl->redirect($this, 'describeMeta');
        }
    }

    protected function initLicenseSelectForm(): ilPropertyFormGUI
    {
        $form = new ilPropertyFormGUI();
        $form->setOpenTag(false);
        $form->setCloseTag(false);
        $form->setFormAction($this->ctrl->getFormAction($this));

        foreach ($this->data->getParamsBySection('select_license') as $name => $param) {
            $item = $param->getFormItem();
            $form->addItem($item);
        }

        $license = $this->md_obj->getCCLicense();
        $radio = new ilRadioGroupInputGUI($this->plugin->txt('selected_license'), 'selected_license');
        $option = new ilRadioOption($this->plugin->txt('no_cc_license'), 'no_cc_license', $this->plugin->txt('no_cc_license_info'));
        $radio->addOption($option);
        $radio->setValue('no_cc_license');

        $allowed_licenses = $this->md_obj->ccMixer($this->data->getIncludedLicenses());

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

            if ($license == $cc) {
                $radio->setValue($license);
            }
        }
        $form->addItem($radio);

        $form->addCommandButton('saveLicense', $this->plugin->txt('save_and_check'));
        return $form;
    }


    protected function updateLicense(): bool
    {
        $form = $this->initLicenseSelectForm();
        if ($form->checkInput()) {
            foreach (array_keys($this->data->getParamsBySection('select_license')) as $name) {
                $this->data->set($name, $form->getInput($name));
            }
            $this->data->write();

            if(!is_object($this->md_section = $this->md_obj->getRights())) {
                $this->md_section = $this->md_obj->addRights();
                $this->md_section->save();
            }

            $license = $form->getInput('selected_license');
            $available = $this->md_obj->getAvailableCCLicenses();
            // set available new cc license
            if (isset($available[$license])) {
                $this->md_section->setCopyrightAndOtherRestrictions("Yes");
                $this->md_section->setDescription($available[$license]);
                $this->md_section->update();
            }
            // remove old cc license
            elseif (!empty($this->md_obj->getCCLicense())) {
                $this->md_section->setDescription('');
                $this->md_section->update();

            }

            return true;
        }

        $this->cmd = 'selectLicense';
        $this->step = $this->getStepByCommand($this->cmd);
        $form->setValuesByPost();
        $this->output($form->getHTML());
        return false;
    }

    #endregion
    #region describe_meta

    protected function describeMeta(): void
    {
        $form = $this->initMetaEditForm();
        $this->output($form->getHTML());
    }

    protected function saveMetaAndCheckAttrib(): void
    {
        if ($this->updateMeta()) {
            $this->ctrl->redirect($this, 'checkAttrib');
        }
    }

    protected function saveMeta(): void
    {
        if ($this->updateMeta()) {
            $this->tpl->setOnScreenMessage('success', $this->lng->txt("saved_successfully"), true);
            $this->ctrl->redirect($this, 'describeMeta');
        }
    }

    /**
     * todo: compare with standard meta data form
     */
    protected function initMetaEditForm(): ilPropertyFormGUI
    {
        $this->lng->loadLanguageModule('meta');
        $this->md_settings = ilMDSettings::_getInstance();
        if(!is_object($this->md_section = $this->md_obj->getGeneral())) {
            $this->md_section = $this->md_obj->addGeneral();
            $this->md_section->save();
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
        $ti->setValue($this->md_section->getTitle());
        $form->addItem($ti);

        // description(s)
        foreach($ids = $this->md_section->getDescriptionIds() as $id) {
            $md_des = $this->md_section->getDescription($id);

            $ta = new ilTextAreaInputGUI($this->lng->txt("meta_description"), "gen_description[" . $id . "][description]");
            $ta->setCols(50);
            $ta->setRows(4);
            $ta->setValue($md_des->getDescription());
            if (count($ids) > 1) {
                $ta->setInfo($this->lng->txt("meta_l_" . $md_des->getDescriptionLanguageCode()));
            }

            $form->addItem($ta);
        }

        // language(s)
        $first = "";
        $options = ilMDLanguageItem::_getLanguages();
        foreach($ids = $this->md_section->getLanguageIds() as $id) {
            $md_lan = $this->md_section->getLanguage($id);
            $first_lang = $md_lan->getLanguageCode();
            $si = new ilSelectInputGUI($this->lng->txt("meta_language"), "gen_language[" . $id . "][language]");
            $si->setOptions($options);
            $si->setValue($md_lan->getLanguageCode());
            $form->addItem($si);
            $first = false;
        }
        if ($first) {
            $si = new ilSelectInputGUI($this->lng->txt("meta_language"), "gen_language[][language]");
            $si->setOptions($options);
            $form->addItem($si);
        }

        // keyword(s)
        $first = true;
        $keywords = array();
        foreach($ids = $this->md_section->getKeywordIds() as $id) {
            $md_key = $this->md_section->getKeyword($id);
            if (trim($md_key->getKeyword()) != "") {
                $keywords[$md_key->getKeywordLanguageCode()][]
                    = $md_key->getKeyword();
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
        if (count($keywords) == 0) {
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


        $form->setTitle($this->lng->txt("description"));
        $form->setFormAction($this->ctrl->getFormAction($this));
        $form->addCommandButton('saveMeta', $this->lng->txt('save'));

        return $form;
    }

    protected function keywordAutocomplete(): void
    {
        $res = ilMDKeyword::_getMatchingKeywords(
            ilUtil::stripSlashes($_GET["term"]),
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

        echo json_encode($result);
        exit;
    }

    /**
     * todo: compare with standard meta data form
     */
    protected function updateMeta(): bool
    {
        $this->md_settings = ilMDSettings::_getInstance();

        // General values
        $this->md_section = $this->md_obj->getGeneral();
        $this->md_section->setTitle(ilUtil::stripSlashes($_POST['gen_title']));
        //		$this->md_section->setTitleLanguage(new ilMDLanguageItem($_POST['gen_title_language']));
        $this->md_section->update();

        // Language
        if(is_array($_POST['gen_language'])) {
            foreach($_POST['gen_language'] as $id => $data) {
                if ($id > 0) {
                    $md_lan = $this->md_section->getLanguage($id);
                    $md_lan->setLanguage(new ilMDLanguageItem($data['language']));
                    $md_lan->update();
                } else {
                    $md_lan = $this->md_section->addLanguage();
                    $md_lan->setLanguage(new ilMDLanguageItem($data['language']));
                    $md_lan->save();
                }
            }
        }
        // Description
        if(is_array($_POST['gen_description'])) {
            foreach($_POST['gen_description'] as $id => $data) {
                $md_des = $this->md_section->getDescription($id);
                $md_des->setDescription(ilUtil::stripSlashes($data['description']));
                //				$md_des->setDescriptionLanguage(new ilMDLanguageItem($data['language']));
                $md_des->update();
            }
        }

        // Keyword
        if(is_array($_POST["keywords"]["value"])) {
            ilMDKeyword::updateKeywords($this->md_section, $_POST["keywords"]["value"]);
        }

        //Educational...
        // Typical Learning Time
        if($_POST['tlt']['mo'] or $_POST['tlt']['d'] or
            $_POST["tlt"]['h'] or $_POST['tlt']['m'] or $_POST['tlt']['s']) {
            if(!is_object($this->md_section = $this->md_obj->getEducational())) {
                $this->md_section = $this->md_obj->addEducational();
                $this->md_section->save();
            }
            $this->md_section->setPhysicalTypicalLearningTime(
                (int) $_POST['tlt']['mo'],
                (int) $_POST['tlt']['d'],
                (int) $_POST['tlt']['h'],
                (int) $_POST['tlt']['m'],
                (int) $_POST['tlt']['s']
            );
            $this->md_section->update();
        } else {
            if(is_object($this->md_section = $this->md_obj->getEducational())) {
                $this->md_section->setPhysicalTypicalLearningTime(0, 0, 0, 0, 0);
                $this->md_section->update();
            }
        }
        //Lifecycle...
        // Authors
        if ($_POST["life_authors"] != "") {
            if(!is_object($this->md_section = $this->md_obj->getLifecycle())) {
                $this->md_section = $this->md_obj->addLifecycle();
                $this->md_section->save();
            }

            // determine all entered authors
            $auth_arr = explode($this->md_settings->getDelimiter(), $_POST["life_authors"]);
            for($i = 0; $i < count($auth_arr); $i++) {
                $auth_arr[$i] = trim($auth_arr[$i]);
            }

            $md_con_author = "";

            // update existing author entries (delete if not entered)
            foreach(($ids = $this->md_section->getContributeIds()) as $con_id) {
                $md_con = $this->md_section->getContribute($con_id);
                if ($md_con->getRole() == "Author") {
                    foreach($ent_ids = $md_con->getEntityIds() as $ent_id) {
                        $md_ent = $md_con->getEntity($ent_id);

                        // entered author already exists
                        if (in_array($md_ent->getEntity(), $auth_arr)) {
                            unset($auth_arr[array_search($md_ent->getEntity(), $auth_arr)]);
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
                    $md_con_author = $this->md_section->addContribute();
                    $md_con_author->setRole("Author");
                    $md_con_author->save();
                }
                foreach ($auth_arr as $auth) {
                    $md_ent = $md_con_author->addEntity();
                    $md_ent->setEntity(ilUtil::stripSlashes($auth));
                    $md_ent->save();
                }
            }
        } else {	// nothing has been entered: delete all author contribs
            if(is_object($this->md_section = $this->md_obj->getLifecycle())) {
                foreach(($ids = $this->md_section->getContributeIds()) as $con_id) {
                    $md_con = $this->md_section->getContribute($con_id);
                    if ($md_con->getRole() == "Author") {
                        $md_con->delete();
                    }
                }
            }
        }

        return true;
    }


    #endregion
    #region check_attrib

    protected function checkAttrib(): void
    {
        $form = $this->initAttribCheckForm();
        $this->output($form->getHTML());
    }

    protected function initAttribCheckForm(): ilPropertyFormGUI
    {
        $form = new ilPropertyFormGUI();
        $form->setOpenTag(false);
        $form->setCloseTag(false);
        $form->setFormAction($this->ctrl->getFormAction($this));
        $form->addCommandButton('saveAttrib', $this->lng->txt('save'));

        foreach ($this->data->getParamsBySection('check_attrib') as $name => $param) {
            $item = $param->getFormItem();
            $form->addItem($item);
        }
        return $form;
    }

    protected function saveAttrib(): void
    {
        if ($this->updateAttrib()) {
            $this->tpl->setOnScreenMessage('success', $this->lng->txt("saved_successfully"), true);
            $this->ctrl->redirect($this, 'checkAttrib');
        }
    }

    protected function saveAttribAndDeclarePublish(): void
    {
        if ($this->updateAttrib()) {
            $this->ctrl->redirect($this, 'declarePublish');
        }
    }

    protected function updateAttrib(): bool
    {
        $form = $this->initAttribCheckForm();
        if ($form->checkInput()) {
            foreach (array_keys($this->data->getParamsBySection('check_attrib')) as $name) {
                $this->data->set($name, $form->getInput($name));
            }
            $this->data->write();
            return true;
        }

        $this->cmd = 'checkAttrib';
        $this->step = $this->getStepByCommand($this->cmd);
        $form->setValuesByPost();
        $this->output($form->getHTML());
        return false;

    }

    #endregion
    #region declare_publish

    protected function declarePublish(): void
    {
        $form = $this->initPublishForm();
        $this->output($form->getHTML());
    }

    protected function savePublish(): void
    {
        if ($this->updatePublish()) {
            $this->tpl->setOnScreenMessage('success', $this->lng->txt("saved_successfully"), true);
            $this->ctrl->redirect($this, 'declarePublish');
        }
    }

    protected function saveAndPublish(): void
    {
        if ($this->updatePublish()) {
            if (empty($this->md_obj->createPublicRefId($this->parent_obj))) {
                $this->tpl->setOnScreenMessage('failure', $this->plugin->txt('public_ref_not_created'), true);
            } elseif (!$this->md_obj->isPublicRefIdPublic()) {
                $this->tpl->setOnScreenMessage('failure', $this->plugin->txt('public_ref_not_public'), true);
            } else {
                $this->md_obj->publish();
            }

            $this->returnToParent();
        }
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
        return $form;
    }

    protected function updatePublish(): bool
    {
        $form = $this->initPublishForm();

        if ($form->checkInput()) {
            foreach (array_keys($this->data->getParamsBySection('check_final')) as $name) {
                $this->data->set($name, $form->getInput($name));
            }
            $this->data->write();
            return true;
        }

        $this->cmd = 'declarePublish';
        $this->step = $this->getStepByCommand($this->cmd);
        $form->setValuesByPost();
        $this->output($form->getHTML());
        return false;
    }

    #endregion
}
