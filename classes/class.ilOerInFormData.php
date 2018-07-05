<?php
// Copyright (c) 2018 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg, GPLv3, see LICENSE

require_once('Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/OERinForm/classes/class.ilOerInFormParam.php');

/**
 * OerInForm plugin data class
 *
 * @author Fred Neumann <fred.neumann@ili.fau.de>
 *
 */
class ilOerInFormData
{
    protected $param_list = [
        'check_rights' => [
            'cr_berechtigung' => ilOerInFormParam::TYPE_HEAD,
            'cr_schoepfung' => ilOerInFormParam::TYPE_BOOLEAN,
            'cr_erschaffen' => ilOerInFormParam::TYPE_BOOLEAN,
            'cr_zustimmung' => ilOerInFormParam::TYPE_BOOLEAN,
            'cr_exklusiv' => ilOerInFormParam::TYPE_BOOLEAN,

            'cr_sonstige_rechte' => ilOerInFormParam::TYPE_HEAD,
            'cr_persoenlichkeit' => ilOerInFormParam::TYPE_BOOLEAN,
            'cr_einwilligung' => ilOerInFormParam::TYPE_BOOLEAN,
            'cr_musik' => ilOerInFormParam::TYPE_BOOLEAN,
            'cr_marken' => ilOerInFormParam::TYPE_BOOLEAN,
            'cr_kontext' => ilOerInFormParam::TYPE_BOOLEAN
        ],
        'select_license' => [
            'sl_existing' => ilOerInFormParam::TYPE_HEAD,
            'sl_cc0' => ilOerInFormParam::TYPE_BOOLEAN,
            'sl_cc_by' => ilOerInFormParam::TYPE_BOOLEAN,
            'sl_cc_by_sa' => ilOerInFormParam::TYPE_BOOLEAN,
            'sl_cc_by_nd' => ilOerInFormParam::TYPE_BOOLEAN,
            'sl_cc_by_nc' => ilOerInFormParam::TYPE_BOOLEAN,
            'sl_cc_by_nc_sa' => ilOerInFormParam::TYPE_BOOLEAN,
            'sl_cc_by_nc_nd'=> ilOerInFormParam::TYPE_BOOLEAN,
            'sl_own_choice' => ilOerInFormParam::TYPE_HEAD
        ],
        'check_attrib' => [
            'ca_selbst' => ilOerInFormParam::TYPE_HEAD,
            'ca_lizenz_selbst' => ilOerInFormParam::TYPE_BOOLEAN,
            'ca_lizenz_link' => ilOerInFormParam::TYPE_BOOLEAN,
            'ca_lizenz_kompat' => ilOerInFormParam::TYPE_BOOLEAN,

            'ca_tullu' => ilOerInFormParam::TYPE_HEAD,
            'ca_urheber' => ilOerInFormParam::TYPE_BOOLEAN,
            'ca_miturheber' => ilOerInFormParam::TYPE_BOOLEAN,
            'ca_titel' => ilOerInFormParam::TYPE_BOOLEAN,
            'ca_lizenz_andere' => ilOerInFormParam::TYPE_BOOLEAN,
            'ca_aenderungen' => ilOerInFormParam::TYPE_BOOLEAN,

            'ca_weitere' => ilOerInFormParam::TYPE_HEAD,
            'ca_fotos' => ilOerInFormParam::TYPE_BOOLEAN,
            'ca_nichtoffen' => ilOerInFormParam::TYPE_BOOLEAN,
            'ca_zitat' => ilOerInFormParam::TYPE_BOOLEAN,
            'ca_nichtkomm' => ilOerInFormParam::TYPE_BOOLEAN,
            'ca_quellen_check' => ilOerInFormParam::TYPE_BOOLEAN,
            'ca_quellen_doku' => ilOerInFormParam::TYPE_BOOLEAN,
        ],
        'checklist_final' => [
            'cf_veroeffentlichung' => ilOerInFormParam::TYPE_HEAD,
            'cf_konsequenzen' => ilOerInFormParam::TYPE_BOOLEAN,
            'cf_bereit' => ilOerInFormParam::TYPE_BOOLEAN,
        ]
    ];

    /** @var int obj_id */
    protected $obj_id;
	/**
	 * @var ilOerInFormParam[]	$params		parameters: 	name => ilOerInFormParam
	 */
	protected $params = array();

    /**
     * @var ilOERinFormPlugin
     */
	protected $plugin;


	/**
	 * Constructor.
	 * @param ilPlugin
     * @param int obj_id;
	 */
	public function __construct($a_plugin_object, $a_obj_id)
	{
		$this->plugin = $a_plugin_object;
		$this->obj_id = $a_obj_id;

        foreach($this->param_list as $section => $definitions)
        {
            foreach ($definitions as $name => $type)
            {
                $info = $this->plugin->txt($name. '_info');

                $this->params[$name] = ilOerInFormParam::_create(
                    $name,
                    $this->plugin->txt($name),
                    $info,
                    $type
                );
            }
        }
        $this->read();
	}


    /**
     * Get the array of all parameters for a section
     * @return ilOerInFormParam[]
     */
	public function getParamsBySection($section)
    {
        $params = [];
        foreach ($this->param_list[$section] as $name => $type)
        {
            $params[$name] = $this->params[$name];
        }
        return $params;
    }

    /**
     * Get the value of a named parameter
     * @param $name
     * @return  mixed
     */
	public function get($name)
    {
        if (!isset($this->params[$name]))
        {
            return null;
        }
        else
        {
            return $this->params[$name]->value;
        }
    }

    /**
     * Set the value of the named parameter
     * @param string $name
     * @param mixed $value
     *
     */
    public function set($name, $value = null)
    {
       $param = $this->params[$name];

       if (isset($param))
       {
           $param->setValue($value);
       }
    }


    /**
     * Read the configuration from the database
     */
	public function read()
    {
        global $DIC;
        $ilDB = $DIC->database();

        $query = "SELECT * FROM oerinf_data WHERE obj_id = ". $ilDB->quote($this->obj_id, 'integer');
        $res = $ilDB->query($query);
        while($row = $ilDB->fetchAssoc($res))
        {
            $this->set($row['param_name'], $row['param_value']);
        }
    }

    /**
     * Write the configuration to the database
     */
    public function write()
    {
        global $DIC;
        $ilDB = $DIC->database();

        foreach ($this->params as $param)
        {
            $ilDB->replace('oerinf_data',
                array(
                    'obj_id' =>  array('integer', $this->obj_id),
                    'param_name' => array('text', $param->name)
                ),
                array('param_value' => array('text', (string) $param->value))
            );
        }
    }
}