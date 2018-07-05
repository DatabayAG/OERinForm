<?php

/**
* Wizard for publishing workflow
*
* @author Fred Neumann <fred.neumann@fim.uni-erlangen.de>
* @version $Id: $
*
*/
class ilOerPublishWizardGUI extends ilOerBaseGUI
{
	/** @var  int parent object ref_id */
	protected $parent_ref_id;

	/** @var  string parent object type */
	protected $parent_type;

	/** @var  string parent gui class */
	protected $parent_gui_class;

	/** @var  ilObject $parent_obj */
	protected $parent_obj;

	/** @var  ilOerPublishMD $md_obj */
	protected $md_obj;

	/**
	* Currently execured command 
	* (set in executeCommand)
	*/
	protected $cmd = '';

	/**
	* Data of currently visible step
	* (set in executeCommand)
	*/
	protected $step = array();


    /** @var ilOerInFormData */
	protected $data;

	/**
	* Definition of the wizard steps
	*/
	protected $steps = array (                              		// list if all visible steps
		array (
				'cmd' => 'checkRights',             			// step command
				'title_var' => 'check_rights',  				// lang var for title
				'desc_var' => 'check_rights_desc',      		// lang var for description
				'prev_cmd' => '',                       		// command of previous step
				'next_cmd' => 'saveRightsAndSelectLicense',    	// command of next step
				'help_id' => 'check_rights',
		),
		array (
				'cmd' => 'selectLicense',
				'title_var' => 'select_license',
				'desc_var' => 'select_license_desc',
				'prev_cmd' => 'checkRights',
				'next_cmd' => 'describeMeta',
            	'help_id' => 'select_license',
		),
        array (
				'cmd' => 'describeMeta',
				'title_var' => 'describe_meta',
				'desc_var' => 'describe_meta_desc',
				'prev_cmd' => 'selectLicense',
				'next_cmd' => 'saveMetaAndCheckAttrib',
        ),
		array (
				'cmd' => 'checkAttrib',
				'title_var' => 'check_attrib',
				'desc_var' => 'check_attrib_desc',
				'prev_cmd' => 'describeMeta',
				'next_cmd' => 'saveAttribAndDeclarePublish',
				'help_id' => 'select_license',
		),
		array (
				'cmd' => 'declarePublish',
				'title_var' => 'declare_publish',
				'desc_var' => 'declare_publish_desc',
				'prev_cmd' => 'checkAttrib',
				'next_cmd' => 'returnToParent'
		)
 	);


	/**
	* Constructor
	* @access public
	*/
	function __construct()
	{
		parent::__construct();

		$this->ctrl->saveParameter($this, 'ref_id');

		$this->parent_ref_id = $_GET['ref_id'];
		$this->parent_type = ilObject::_lookupType($this->parent_ref_id, true);
		$this->parent_obj = ilObjectFactory::getInstanceByRefId($this->parent_ref_id);
		$this->parent_gui_class = ilObjectFactory::getClassByType($this->parent_type).'GUI';

		$this->plugin->includeClass('class.ilOerPublishMD.php');
		$this->md_obj = new ilOerPublishMD($this->parent_obj->getId(), $this->parent_obj->getId(), $this->parent_type);
		$this->md_obj->setPlugin($this->plugin);

		$this->data = $this->plugin->getData($this->parent_obj->getId());
	}


	/**
	* Execute a command (main entry point)
	* @param 	string      $a_cmd 	specific command to be executed (or empty)
	* @return mixed
	*/
	function executeCommand($a_cmd = '')
	{
		$this->ctrl->setParameter($this, 'return', urlencode($_GET['return']));

		// get the current command
		$cmd = $a_cmd ? $a_cmd : $this->ctrl->getCmd('checkRights');

		// simple command
		$this->cmd = $cmd;
		$this->step = $this->getStepByCommand($cmd);
		return $this->$cmd();
	}


	/**
	* Get a step by command
	*
	* commands without visible steps will return an ampty array
	*
	* @param    string  	command
	* @return   array       step
	*/
	protected function getStepByCommand($a_cmd)
	{
	    foreach ($this->steps as $step)
		{
	        if ($step['cmd'] == $a_cmd)
			{
	            return $step;
	        }
		}
		return array();
	}

	/**
	* Get the form name used for the wizard
	*/
	protected function getFormName()
	{
		return get_class($this);
	}


	/**
	 * Show the wizard content
	 * @param string $a_html
	 */
	protected function output($a_html = '')
	{
	    global $DIC;
	    $ilCtrl = $DIC->ctrl();
	    $ilTabs = $DIC->tabs();
	    $lng = $DIC->language();

		// show step list and determine the current step number
		$tpl = $this->plugin->getTemplate("tpl.wizard_steps.html");
		for ($i = 0; $i < count($this->steps); $i++)
		{
			if ($this->steps[$i]['cmd'] == $this->cmd)
			{
				$tpl->setCurrentBlock('strong');
				$stepnum = $i + 1;
			}
			else
			{
				$tpl->setCurrentBlock('normal');
				$tpl->setVariable('LINK', $this->ctrl->getLinkTarget($this,$this->steps[$i]['cmd']));
			}
			$tpl->setVariable("TITLE", $this->plugin->txt($this->steps[$i]['title_var']));
			$tpl->setVariable("STEP", sprintf($this->plugin->txt("wizard_step"),$i + 1));
			$tpl->parseCurrentBlock();
			$tpl->setCurrentBlock("stepline");
			$tpl->parseCurrentBlock();
		}
		$tpl->setVariable("HEADER", $this->plugin->txt('publish_oer'));
		if ($this->step['help_id'])
		{
			if ($this->plugin->getHelp()->isPageAvailable($this->step['help_id']))
			{
                $tpl->setVariable('HELP', $this->plugin->getHelpGUI()->getHelpButton($this->step['help_id']));
			}
		}
		$this->tpl->setRightContent( $tpl->get());


		// show the main screen
		$tpl = $this->plugin->getTemplate("tpl.wizard_page.html");
		$tpl->setVariable("FORMACTION", $ilCtrl->getFormAction($this));
		$tpl->setVariable("FORMNAME", $this->getFormName());
  		$tpl->setVariable("CONTENT", $a_html);

		// add step specific info and toolbar
		if ($this->step['cmd'])
		{
			$tpl->setVariable("STEP", sprintf($this->plugin->txt("wizard_step"),$stepnum));

			ilUtil::sendInfo($this->plugin->txt($this->step['desc_var']));
			//$tpl->setVariable("DESCRIPTION", $this->plugin->txt($this->step['desc_var']));

			require_once("./Services/UIComponent/Toolbar/classes/class.ilToolbarGUI.php");
			$tb = new ilToolbarGUI();

//			if ($this->step['prev_cmd'])
//			{
//				$button = ilSubmitButton::getInstance();
//				$button->setCaption(sprintf($this->plugin->txt('wizard_previous'),$stepnum -1 ), false);
//				$button->setCommand($this->step['prev_cmd']);
//				$tb->addButtonInstance($button);
//			}
			if ($this->step['next_cmd'] and $stepnum == count($this->steps))
			{
                $button = ilSubmitButton::getInstance();
                $button->setCaption(sprintf($this->plugin->txt('wizard_finish'),$stepnum + 1), false);
                $button->setCommand($this->step['next_cmd']);
                $button->setPrimary(true);
                $tb->addButtonInstance($button);
			}
			elseif ($this->step['next_cmd'])
			{
                $button = ilSubmitButton::getInstance();
                $button->setCaption(sprintf($this->plugin->txt('wizard_next'),$stepnum + 1), false);
                $button->setCommand($this->step['next_cmd']);
                $button->setPrimary(true);
                $tb->addButtonInstance($button);
	        }

			$tb->addSeparator();
            $button = ilSubmitButton::getInstance();
            $button->setCaption($this->lng->txt('cancel'),false);
            $button->setCommand('returnToParent');
            $tb->addButtonInstance($button);

			$tpl->setVariable("TOOLBAR",$tb->getHTML());
		}

		$ilTabs->setBackTarget($lng->txt('export'), $_GET['return']);

		$this->tpl->setContent($tpl->get());
		$this->tpl->show();
	}


	/**
	* Return to the parent GUI
	*/
	protected function returnToParent()
	{
		$this->ctrl->redirectToURL($_GET['return']);
	}


	protected function checkRights()
	{
		$form = $this->initRightsCheckForm();
		$this->output($form->getHTML());
	}
	protected function initRightsCheckForm()
	{
        $form = new ilPropertyFormGUI();
        $form->setOpenTag(false);
        $form->setCloseTag(false);
        $form->setFormAction($this->ctrl->getFormAction($this));

        foreach ($this->data->getParamsBySection('check_rights') as $name => $param)
        {
            $item = $param->getFormItem();
            $form->addItem($item);
        }

        return $form;
	}
	protected function saveRightsAndSelectLicense()
	{
		$form = $this->initRightsCheckForm();
        if ($form->checkInput())
        {
            foreach (array_keys($this->data->getParamsBySection('check_rights')) as $name)
            {
                $this->data->set($name, $form->getInput($name));
            }
            $this->data->write();
            $this->ctrl->redirect($this, 'selectLicense');
        }
        else
        {
            $this->cmd = 'checkRights';
            $this->step = $this->getStepByCommand($this->cmd);
            $form->setValuesByPost();
            $this->output($form->getHTML());
        }
	}

    protected function selectLicense()
    {
        $form = $this->initLicenseSelectForm();
        $this->output($form->getHTML());
    }

    protected function initLicenseSelectForm()
    {
        $form = new ilPropertyFormGUI();
        $form->setOpenTag(false);
        $form->setCloseTag(false);
        $form->setFormAction($this->ctrl->getFormAction($this));

        foreach ($this->data->getParamsBySection('select_license') as $name => $param)
        {
            $item = $param->getFormItem();
            $form->addItem($item);
        }

        $license = $this->md_obj->getCCLicense();
        $radio = new ilRadioGroupInputGUI($this->plugin->txt('selected_license'), 'selected_license');
        $option = new ilRadioOption($this->plugin->txt('no_cc_license'), 'no_cc_license', $this->plugin->txt('no_cc_license_info'));
        $radio->addOption($option);
        $radio->setValue('no_cc_license');
        foreach ($this->md_obj->getAvailableCCLicenses() as $cc => $value)
        {
            switch ($cc)
            {
                case ilOerPublishMD::CC0:
                    $title = $this->plugin->txt('sl_cc0');
                    $info = $this->plugin->txt('sl_cc0_info');
                    break;
                case ilOerPublishMD::CC_BY:
                    $title = $this->plugin->txt('sl_cc_by');
                    $info = $this->plugin->txt('sl_cc_by_info');
                    break;
                case ilOerPublishMD::CC_BY_SA:
                    $title = $this->plugin->txt('sl_cc_by_sa');
                    $info = $this->plugin->txt('sl_cc_by_sa_info');
                    break;
                case ilOerPublishMD::CC_BY_ND:
                    $title = $this->plugin->txt('sl_cc_by_nd');
                    $info = $this->plugin->txt('sl_cc_by_nd_info');
                    break;
                case ilOerPublishMD::CC_BY_NC:
                    $title = $this->plugin->txt('sl_cc_by_nc');
                    $info = $this->plugin->txt('sl_cc_by_nc_info');
                    break;
                case ilOerPublishMD::CC_BY_NC_SA:
                    $title = $this->plugin->txt('sl_cc_by_nc_sa');
                    $info = $this->plugin->txt('sl_cc_by_nc_sa_info');
                    break;
                case ilOerPublishMD::CC_BY_NC_ND:
                    $title = $this->plugin->txt('sl_cc_by_nc_nd');
                    $info = $this->plugin->txt('sl_cc_by_nc_nd_info');
                    break;
            }

            $option = new ilRadioOption($title, $cc, $info);
            $radio->addOption($option);

            if ($license == $cc)
            {
                $radio->setValue($license);
            }
        }
        $form->addItem($radio);

        $form->addCommandButton('saveLicense', $this->plugin->txt('save_and_check'));
        return $form;
    }


    protected function saveLicense()
    {
        $form = $this->initRightsCheckForm();
        if ($form->checkInput())
        {
            foreach (array_keys($this->data->getParamsBySection('select_license')) as $name)
            {
                $this->data->set($name, $form->getInput($name));
            }
            $this->data->write();

            if(!is_object($this->md_section = $this->md_obj->getRights()))
            {
                $this->md_section = $this->md_obj->addRights();
                $this->md_section->save();
            }

            $license = $form->getInput('selected_license');
            $available = $this->md_obj->getAvailableCCLicenses();
            // set available new cc license
            if (isset($available[$license]))
            {
                $this->md_section->setCopyrightAndOtherRestrictions("Yes");
                $this->md_section->setDescription($available[$license]);
                $this->md_section->update();
            }
            // remove old cc license
            elseif (!empty($this->md_obj->getCCLicense()))
            {
                $this->md_section->setDescription('');
                $this->md_section->update();

            }

            $this->ctrl->redirect($this, 'selectLicense');
        }
        else
        {
            $this->cmd = 'selectLicense';
            $this->step = $this->getStepByCommand($this->cmd);
            $form->setValuesByPost();
            $this->output($form->getHTML());
        }

    }

    protected function checkAttrib()
    {
        $form = $this->initAttribCheckForm();
        $this->output($form->getHTML());
    }
    protected function initAttribCheckForm()
    {
        $form = new ilPropertyFormGUI();
        $form->setOpenTag(false);
        $form->setCloseTag(false);
        $form->setFormAction($this->ctrl->getFormAction($this));
        foreach ($this->data->getParamsBySection('check_attrib') as $name => $param)
        {
            $item = $param->getFormItem();
            $form->addItem($item);
        }
        return $form;
    }
    protected function saveAttribAndDescribeMeta()
    {
        $form = $this->initAttribCheckForm();
        if ($form->checkInput())
        {
            foreach (array_keys($this->data->getParamsBySection('check_attrib')) as $name)
            {
                $this->data->set($name, $form->getInput($name));
            }
            $this->data->write();
            $this->ctrl->redirect($this, 'describeMeta');
        }
        else
        {
            $this->cmd = 'checkAttrib';
            $this->step = $this->getStepByCommand($this->cmd);
            $form->setValuesByPost();
            $this->output($form->getHTML());
        }
    }


	protected function describeMeta()
	{
        $form = $this->initMetaEditForm();
        $this->output($form->getHTML());
	}


    /**
     * Init quick edit form.
     */
    public function initMetaEditForm()
    {
        global $lng, $ilCtrl, $tree;

		$this->lng->loadLanguageModule('meta');
        $this->md_settings = ilMDSettings::_getInstance();
        if(!is_object($this->md_section = $this->md_obj->getGeneral()))
        {
            $this->md_section = $this->md_obj->addGeneral();
            $this->md_section->save();
        }
        $this->form = new ilPropertyFormGUI();

        // title
        $ti = new ilTextInputGUI($this->lng->txt("title"), "gen_title");
        $ti->setMaxLength(200);
        $ti->setSize(50);
        if($this->md_obj->getObjType() != 'sess')
        {
            $ti->setRequired(true);
        }
        $ti->setValue($this->md_section->getTitle());
        $this->form->addItem($ti);

        // description(s)
        foreach($ids = $this->md_section->getDescriptionIds() as $id)
        {
            $md_des = $this->md_section->getDescription($id);

            $ta = new ilTextAreaInputGUI($this->lng->txt("meta_description"), "gen_description[".$id."][description]");
            $ta->setCols(50);
            $ta->setRows(4);
            $ta->setValue($md_des->getDescription());
            if (count($ids) > 1)
            {
                $ta->setInfo($this->lng->txt("meta_l_".$md_des->getDescriptionLanguageCode()));
            }

            $this->form->addItem($ta);
        }

        // language(s)
        $first = "";
        $options = ilMDLanguageItem::_getLanguages();
        foreach($ids = $this->md_section->getLanguageIds() as $id)
        {
            $md_lan = $this->md_section->getLanguage($id);
            $first_lang = $md_lan->getLanguageCode();
            $si = new ilSelectInputGUI($this->lng->txt("meta_language"), "gen_language[".$id."][language]");
            $si->setOptions($options);
            $si->setValue($md_lan->getLanguageCode());
            $this->form->addItem($si);
            $first = false;
        }
        if ($first)
        {
            $si = new ilSelectInputGUI($this->lng->txt("meta_language"), "gen_language[][language]");
            $si->setOptions($options);
            $this->form->addItem($si);
        }

        // keyword(s)
        $first = true;
        $keywords = array();
        foreach($ids = $this->md_section->getKeywordIds() as $id)
        {
            $md_key = $this->md_section->getKeyword($id);
            if (trim($md_key->getKeyword()) != "")
            {
                $keywords[$md_key->getKeywordLanguageCode()][]
                    = $md_key->getKeyword();
            }
        }
        foreach($keywords as $lang => $keyword_set)
        {
            $kw = new ilTextInputGUI($this->lng->txt("keywords"),
                "keywords[value][".$lang."]");
            $kw->setDataSource($this->ctrl->getLinkTarget($this, "keywordAutocomplete", "", true));
            $kw->setMaxLength(200);
            $kw->setSize(50);
            $kw->setMulti(true);
            if (count($keywords) > 1)
            {
                $kw->setInfo($this->lng->txt("meta_l_".$lang));
            }
            $this->form->addItem($kw);
            asort($keyword_set);
            $kw->setValue($keyword_set);
        }
        if (count($keywords) == 0)
        {
            $kw = new ilTextInputGUI($this->lng->txt("keywords"),
                "keywords[value][".$first_lang."]");
            $kw->setDataSource($this->ctrl->getLinkTarget($this, "keywordAutocomplete", "", true));
            $kw->setMaxLength(200);
            $kw->setSize(50);
            $kw->setMulti(true);
            $this->form->addItem($kw);
        }

        // Lifecycle...
        // Authors
        $ta = new ilTextAreaInputGUI($this->lng->txt('authors')."<br />".
            "(".sprintf($this->lng->txt('md_separated_by'), $this->md_settings->getDelimiter()).")",
            "life_authors");
        $ta->setCols(50);
        $ta->setRows(2);
        if(is_object($this->md_section = $this->md_obj->getLifecycle()))
        {
            $sep = $ent_str = "";
            foreach(($ids = $this->md_section->getContributeIds()) as $con_id)
            {
                $md_con = $this->md_section->getContribute($con_id);
                if ($md_con->getRole() == "Author")
                {
                    foreach($ent_ids = $md_con->getEntityIds() as $ent_id)
                    {
                        $md_ent = $md_con->getEntity($ent_id);
                        $ent_str = $ent_str.$sep.$md_ent->getEntity();
                        $sep = $this->md_settings->getDelimiter()." ";
                    }
                }
            }
            $ta->setValue($ent_str);
        }
        $this->form->addItem($ta);

        // copyright
//        include_once("./Services/MetaData/classes/class.ilCopyrightInputGUI.php");
//        $cp = new ilCopyrightInputGUI($this->lng->txt("meta_copyright"), "copyright");
//        $cp->setCols(50);
//        $cp->setRows(3);
//        $desc = ilMDRights::_lookupDescription($this->md_obj->getRBACId(),
//            $this->md_obj->getObjId());
//        $val["ta"] = $desc;
//        $cp->setValue($val);
//        $this->form->addItem($cp);

        // typical learning time
        include_once("./Services/MetaData/classes/class.ilTypicalLearningTimeInputGUI.php");
        $tlt = new ilTypicalLearningTimeInputGUI($this->lng->txt("meta_typical_learning_time"), "tlt");
        $edu = $this->md_obj->getEducational();
        if (is_object($edu))
        {
            $tlt->setValueByLOMDuration($edu->getTypicalLearningTime());
        }
        $this->form->addItem($tlt);


        $this->form->setTitle($this->lng->txt("description"));
        $this->form->setFormAction($ilCtrl->getFormAction($this));
        $this->form->addCommandButton('saveMeta', $this->lng->txt('save'));

        return $this->form;
    }

    /**
     * Keyword list for autocomplete
     *
     * @param
     * @return
     */
    function keywordAutocomplete()
    {

        include_once("./Services/MetaData/classes/class.ilMDKeyword.php");
        $res = ilMDKeyword::_getMatchingKeywords(ilUtil::stripSlashes($_GET["term"]),
            $this->md_obj->getObjType(), $this->md_obj->getRBACId());

        $result = array();
        $cnt = 0;
        foreach ($res as $r)
        {
            if ($cnt++ > 19)
            {
                continue;
            }
            $entry = new stdClass();
            $entry->value = $r;
            $entry->label = $r;
            $result[] = $entry;
        }

        include_once './Services/JSON/classes/class.ilJsonUtil.php';
        echo ilJsonUtil::encode($result);
        exit;
    }


    function saveMetaAndCheckAttrib()
	{
		if ($this->updateMeta())
		{
            $this->ctrl->redirect($this, 'checkAttrib');
		}
	}

	function saveMeta()
	{
		if ($this->updateMeta())
		{
            ilUtil::sendSuccess($this->lng->txt("saved_successfully"), true);
            $this->ctrl->redirect($this, 'describeMeta');
		}
	}

    /**
     * update quick edit properties
     */
    function updateMeta()
    {
        $this->md_settings = ilMDSettings::_getInstance();

        if(!trim($_POST['gen_title']))
        {
            if($this->md_obj->getObjType() != 'sess')
            {
                ilUtil::sendFailure($this->lng->txt('title_required'));
                $this->cmd = 'describeMeta';
                $this->step = $this->getStepByCommand($this->cmd);
                $form = $this->initAttribCheckForm();
                $form->checkInput();
                $form->setValuesByPost();
                $this->output($form->getHTML());
                return false;
            }
        }

        // General values
        $this->md_section = $this->md_obj->getGeneral();
        $this->md_section->setTitle(ilUtil::stripSlashes($_POST['gen_title']));
//		$this->md_section->setTitleLanguage(new ilMDLanguageItem($_POST['gen_title_language']));
        $this->md_section->update();

        // Language
        if(is_array($_POST['gen_language']))
        {
            foreach($_POST['gen_language'] as $id => $data)
            {
                if ($id > 0)
                {
                    $md_lan = $this->md_section->getLanguage($id);
                    $md_lan->setLanguage(new ilMDLanguageItem($data['language']));
                    $md_lan->update();
                }
                else
                {
                    $md_lan = $this->md_section->addLanguage();
                    $md_lan->setLanguage(new ilMDLanguageItem($data['language']));
                    $md_lan->save();
                }
            }
        }
        // Description
        if(is_array($_POST['gen_description']))
        {
            foreach($_POST['gen_description'] as $id => $data)
            {
                $md_des = $this->md_section->getDescription($id);
                $md_des->setDescription(ilUtil::stripSlashes($data['description']));
//				$md_des->setDescriptionLanguage(new ilMDLanguageItem($data['language']));
                $md_des->update();
            }
        }

        // Keyword
        if(is_array($_POST["keywords"]["value"]))
        {
            include_once("./Services/MetaData/classes/class.ilMDKeyword.php");
            ilMDKeyword::updateKeywords($this->md_section, $_POST["keywords"]["value"]);
        }
        $this->callListeners('General');

        // Copyright
        //if($_POST['copyright_id'] or $_POST['rights_copyright'])
        if($_POST['copyright']['sel'] || $_POST['copyright']['ta'])
        {
            if(!is_object($this->md_section = $this->md_obj->getRights()))
            {
                $this->md_section = $this->md_obj->addRights();
                $this->md_section->save();
            }
            if($_POST['copyright']['sel'])
            {
                $this->md_section->setCopyrightAndOtherRestrictions("Yes");
                $this->md_section->setDescription('il_copyright_entry__'.IL_INST_ID.'__'.(int) $_POST['copyright']['sel']);
            }
            else
            {
                $this->md_section->setCopyrightAndOtherRestrictions("Yes");
                $this->md_section->setDescription(ilUtil::stripSlashes($_POST['copyright']['ta']));
            }
            $this->md_section->update();
        }
        else
        {
            if(is_object($this->md_section = $this->md_obj->getRights()))
            {
                $this->md_section->setCopyrightAndOtherRestrictions("No");
                $this->md_section->setDescription("");
                $this->md_section->update();
            }
        }
        $this->callListeners('Rights');

        //Educational...
        // Typical Learning Time
        if($_POST['tlt']['mo'] or $_POST['tlt']['d'] or
            $_POST["tlt"]['h'] or $_POST['tlt']['m'] or $_POST['tlt']['s'])
        {
            if(!is_object($this->md_section = $this->md_obj->getEducational()))
            {
                $this->md_section = $this->md_obj->addEducational();
                $this->md_section->save();
            }
            $this->md_section->setPhysicalTypicalLearningTime((int)$_POST['tlt']['mo'],(int)$_POST['tlt']['d'],
                (int)$_POST['tlt']['h'],(int)$_POST['tlt']['m'],(int)$_POST['tlt']['s']);
            $this->md_section->update();
        }
        else
        {
            if(is_object($this->md_section = $this->md_obj->getEducational()))
            {
                $this->md_section->setPhysicalTypicalLearningTime(0,0,0,0,0);
                $this->md_section->update();
            }
        }
        $this->callListeners('Educational');
        //Lifecycle...
        // Authors
        if ($_POST["life_authors"] != "")
        {
            if(!is_object($this->md_section = $this->md_obj->getLifecycle()))
            {
                $this->md_section = $this->md_obj->addLifecycle();
                $this->md_section->save();
            }

            // determine all entered authors
            $auth_arr = explode($this->md_settings->getDelimiter(), $_POST["life_authors"]);
            for($i = 0; $i < count($auth_arr); $i++)
            {
                $auth_arr[$i] = trim($auth_arr[$i]);
            }

            $md_con_author = "";

            // update existing author entries (delete if not entered)
            foreach(($ids = $this->md_section->getContributeIds()) as $con_id)
            {
                $md_con = $this->md_section->getContribute($con_id);
                if ($md_con->getRole() == "Author")
                {
                    foreach($ent_ids = $md_con->getEntityIds() as $ent_id)
                    {
                        $md_ent = $md_con->getEntity($ent_id);

                        // entered author already exists
                        if (in_array($md_ent->getEntity(), $auth_arr))
                        {
                            unset($auth_arr[array_search($md_ent->getEntity(), $auth_arr)]);
                        }
                        else  // existing author has not been entered again -> delete
                        {
                            $md_ent->delete();
                        }
                    }
                    $md_con_author = $md_con;
                }
            }

            // insert enterd, but not existing authors
            if (count($auth_arr) > 0)
            {
                if (!is_object($md_con_author))
                {
                    $md_con_author = $this->md_section->addContribute();
                    $md_con_author->setRole("Author");
                    $md_con_author->save();
                }
                foreach ($auth_arr as $auth)
                {
                    $md_ent = $md_con_author->addEntity();
                    $md_ent->setEntity(ilUtil::stripSlashes($auth));
                    $md_ent->save();
                }
            }
        }
        else	// nothing has been entered: delete all author contribs
        {
            if(is_object($this->md_section = $this->md_obj->getLifecycle()))
            {
                foreach(($ids = $this->md_section->getContributeIds()) as $con_id)
                {
                    $md_con = $this->md_section->getContribute($con_id);
                    if ($md_con->getRole() == "Author")
                    {
                        $md_con->delete();
                    }
                }
            }
        }
        $this->callListeners('Lifecycle');

        return true;
    }

    // Observer methods
    function addObserver(&$a_class,$a_method,$a_element)
    {
        $this->observers[$a_element]['class'] =& $a_class;
        $this->observers[$a_element]['method'] =& $a_method;

        return true;
    }
    function callListeners($a_element)
    {
        if(isset($this->observers[$a_element]))
        {
            $class =& $this->observers[$a_element]['class'];
            $method = $this->observers[$a_element]['method'];

            return $class->$method($a_element);
        }
        return false;
    }

    protected function declarePublish()
	{
		$link = $this->plugin->getImagePath('step4.png');
		$this->output('<img src="'.$link.'" />');
	}
}
?>
