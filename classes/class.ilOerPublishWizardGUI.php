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
				'prev_cmd' => 'checkLicenses',
				'next_cmd' => 'checkAttrib',
            	'help_id' => 'select_license',
		),
        array (
				'cmd' => 'checkAttrib',
				'title_var' => 'check_attrib',
				'desc_var' => 'check_attrib_desc',
				'prev_cmd' => 'selectLicense',
				'next_cmd' => 'saveAttribAndDescribeMeta',
				'help_id' => 'select_license',
			),
		array (
				'cmd' => 'describeMeta',
				'title_var' => 'describe_meta',
				'desc_var' => 'describe_meta_desc',
				'prev_cmd' => 'selectLicense',
				'next_cmd' => 'declarePublish',
		),
		array (
				'cmd' => 'declarePublish',
				'title_var' => 'declare_publish',
				'desc_var' => 'declare_publish_desc',
				'prev_cmd' => 'describeMeta',
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
        $link = $this->plugin->getImagePath('step2.png');
        $this->output('<img src="'.$link.'" />');
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
		$link = $this->plugin->getImagePath('step3.png');
		$this->output('<img src="'.$link.'" />');
	}

	protected function declarePublish()
	{
		$link = $this->plugin->getImagePath('step4.png');
		$this->output('<img src="'.$link.'" />');
	}
}
?>
