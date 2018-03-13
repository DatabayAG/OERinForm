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
	* General wizard mode
	* (defined by setMode)
	*/
	protected $mode = 'general';


	/**
	* Currently execured command 
	* (set in executeCommand)
	*/
	protected $cmd = '';


	/**
	* Data of currently visible step
	* (set in executeCommand, depending from mode and command)
	*/
	protected $step = array();


	/**
	* Data of all steps defined by the mode
	* (defined by setMode)
	*/
	protected $steps = array();


	/**
	* Data of the current wizard mode
	* (defined by setMode)
	*/
	protected $mode_data = array();

	/**
	 * @var ilOerSessionValues|null
	 */
	protected $values = null;

	/**
	* Definition of all wizard modes
	* (pre-defined in an actual wizard)
	*
	* This should be overwritten in the derived classes.
	* The entries are only examples!
	*/
	protected $mode_definitions = array (

		'general' => array (
			'title_var' => 'guided_publish',                     // lang var of main title
			'steps' 	=> array (                              // list if all visible steps
		        array (
						'cmd' => 'checkLicenses',             	// step command
						'title_var' => 'check_licenses',  		// lang var for title
						'desc_var' => 'check_licenses_desc',    // lang var for description
						'prev_cmd' => '',                       // command of previous step
						'next_cmd' => 'selectLicense'        	// command of next step
				),
		        array (
						'cmd' => 'selectLicense',
						'title_var' => 'select_license',
						'desc_var' => 'select_license_desc',
						'prev_cmd' => 'checkLicenses',
						'next_cmd' => 'describeMeta'
				),
		        array (
						'cmd' => 'describeMeta',
						'title_var' => 'describe_meta',
						'desc_var' => 'describe_meta_desc',
						'prev_cmd' => 'selectLicense',
						'next_cmd' => 'declarePublish'
				),
				array (
					'cmd' => 'declarePublish',
					'title_var' => 'declare_publish',
					'desc_var' => 'declare_publish_desc',
					'prev_cmd' => 'describeMeta',
					'next_cmd' => 'returnToParent'
				)
			)
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

		// class values stored in user session
		$this->plugin->includeClass('class.ilOerSessionValues.php');
		$this->values = new ilOerSessionValues(get_class($this));

		// init mode as saved in session
		$this->setMode('general');
	}


	/**
	* Set the wizard mode and depending steps
	*
	* The mode should once be set in a start function of the wizard
	* Afterwards it is read from the session in the class constructor
	*
	* @param    string  	$a_mode
	*/
	protected function setMode($a_mode = '')
	{
	    // determine the current mode (new or saved)
	    if ($a_mode)
  		{
			$this->mode = $a_mode;
			$this->values->setSessionValue('common','mode',$a_mode);
		}
		else
		{
			$this->mode = $this->values->getSessionValue('common','mode');
		}


		// set data and steps defined by the mode
		$this->mode_data = $this->mode_definitions[$this->mode];

		if (is_array($this->mode_data['steps']))
		{
			$this->steps = $this->mode_data['steps'];
		}
		else
		{
	        $this->steps = array();
	    }
	}


	/**
	* Execute a command (main entry point)
	* @param 	string      $a_cmd 	specific command to be executed (or empty)
	* @access 	public
	*/
	function executeCommand($a_cmd = '')
	{
	    global $ilCtrl;

		$this->ctrl->setParameter($this, 'return', urlencode($_GET['return']));

		// get the current command
		$cmd = $a_cmd ? $a_cmd : $ilCtrl->getCmd('checkLicenses');

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
	*/
	protected function output($a_html = '')
	{
	    global $ilCtrl;

		// show step list and determine the current step number
		if (count($this->steps) > 1)
		{
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
		        }
		  		$tpl->setVariable("TITLE", $this->plugin->txt($this->steps[$i]['title_var']));
				$tpl->setVariable("STEP", sprintf($this->plugin->txt("wizard_step"),$i + 1));
				$tpl->parseCurrentBlock();
				$tpl->setCurrentBlock("stepline");
				$tpl->parseCurrentBlock();
			}
	        $this->tpl->setRightContent($tpl->get());
		}
		else
		{
	        $this->tpl->setRightContent('&nbsp;');
	        $stepnum = 1;
	    }

		// show the main screen
		$tpl = $this->plugin->getTemplate("tpl.wizard_page.html");
		$tpl->setVariable("MAIN_TITLE", $this->plugin->txt($this->mode_data['title_var']));
		$tpl->setVariable("FORMACTION", $ilCtrl->getFormAction($this));
		$tpl->setVariable("FORMNAME", $this->getFormName());
  		$tpl->setVariable("CONTENT", $a_html);

		// add step specific info and toolbar
		if ($this->step['cmd'])
		{
			$tpl->setVariable("STEP", sprintf($this->plugin->txt("wizard_step"),$stepnum));
			$tpl->setVariable("DESCRIPTION", $this->plugin->txt($this->step['desc_var']));

			require_once("./Services/UIComponent/Toolbar/classes/class.ilToolbarGUI.php");
			$tb = new ilToolbarGUI();

			if ($this->step['prev_cmd'])
			{
				$tb->addFormButton(sprintf($this->plugin->txt('wizard_previous'),$stepnum - 1), $this->step['prev_cmd']);
			}
			if ($this->step['next_cmd'] and $stepnum == count($this->steps))
			{
				$tb->addFormButton($this->plugin->txt('wizard_finish'), $this->step['next_cmd']);
			}
			elseif ($this->step['next_cmd'])
			{
				$tb->addFormButton(sprintf($this->plugin->txt('wizard_next'),$stepnum + 1), $this->step['next_cmd']);
	        }

			$tb->addSeparator();
			$tb->addFormButton($this->lng->txt('cancel'), 'returnToParent');

			$tpl->setVariable("TOOLBAR",$tb->getHTML());
		}
		$tpl->parse();

		$this->tpl->setContent($tpl->get());
		$this->tpl->show();
	}


	/**
	* Return to the parent GUI
	*/
	protected function returnToParent()
	{
		ilUtil::redirect($_GET['return']);
		//$this->ctrl->returnToParent($this);
	}


	protected function checkLicenses()
	{
		$link = $this->plugin->getImagePath('step1.png');
		$this->output('<img src="'.$link.'" />');
	}

	protected function selectLicense()
	{
		$link = $this->plugin->getImagePath('step2.png');
		$this->output('<img src="'.$link.'" />');
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
