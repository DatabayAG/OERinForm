<?php
// Copyright (c) 2018 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg, GPLv3, see LICENSE

require_once('Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/OERinForm/classes/class.ilOERinFormParam.php');

/**
 * OERinForm plugin data class
 *
 * @author Fred Neumann <fred.neumann@ili.fau.de>
 *
 */
class ilOERinFormData
{
    protected $param_list = [
        'check_rights' => [
            'cr_berechtigung' => ilOERinFormParam::TYPE_HEAD,
            'cr_schoepfung' => ilOERinFormParam::TYPE_BOOLEAN,
            'cr_erschaffen' => ilOERinFormParam::TYPE_BOOLEAN,
            'cr_zustimmung' => ilOERinFormParam::TYPE_BOOLEAN,
            'cr_exklusiv' => ilOERinFormParam::TYPE_BOOLEAN,

            'cr_sonstige_rechte' => ilOERinFormParam::TYPE_HEAD,
            'cr_persoenlichkeit' => ilOERinFormParam::TYPE_BOOLEAN,
            'cr_einwilligung' => ilOERinFormParam::TYPE_BOOLEAN,
            'cr_musik' => ilOERinFormParam::TYPE_BOOLEAN,
            'cr_marken' => ilOERinFormParam::TYPE_BOOLEAN,
            'cr_kontext' => ilOERinFormParam::TYPE_BOOLEAN
        ],
        'select_license' => [
            'sl_existing' => ilOERinFormParam::TYPE_HEAD,
            'sl_cc0' => ilOERinFormParam::TYPE_BOOLEAN,
            'sl_cc_by' => ilOERinFormParam::TYPE_BOOLEAN,
            'sl_cc_by_sa' => ilOERinFormParam::TYPE_BOOLEAN,
            'sl_cc_by_nd' => ilOERinFormParam::TYPE_BOOLEAN,
            'sl_cc_by_nc' => ilOERinFormParam::TYPE_BOOLEAN,
            'sl_cc_by_nc_sa' => ilOERinFormParam::TYPE_BOOLEAN,
            'sl_cc_by_nc_nd'=> ilOERinFormParam::TYPE_BOOLEAN,
            'sl_own_choice' => ilOERinFormParam::TYPE_HEAD
        ],
        'check_attrib' => [
            'ca_selbst' => ilOERinFormParam::TYPE_HEAD,
            'ca_lizenz_selbst' => ilOERinFormParam::TYPE_BOOLEAN,
            'ca_lizenz_link' => ilOERinFormParam::TYPE_BOOLEAN,
            'ca_lizenz_kompat' => ilOERinFormParam::TYPE_BOOLEAN,

            'ca_tullu' => ilOERinFormParam::TYPE_HEAD,
            'ca_urheber' => ilOERinFormParam::TYPE_BOOLEAN,
            'ca_miturheber' => ilOERinFormParam::TYPE_BOOLEAN,
            'ca_titel' => ilOERinFormParam::TYPE_BOOLEAN,
            'ca_lizenz_andere' => ilOERinFormParam::TYPE_BOOLEAN,
            'ca_aenderungen' => ilOERinFormParam::TYPE_BOOLEAN,

            'ca_weitere' => ilOERinFormParam::TYPE_HEAD,
            'ca_fotos' => ilOERinFormParam::TYPE_BOOLEAN,
            'ca_nichtoffen' => ilOERinFormParam::TYPE_BOOLEAN,
            'ca_zitat' => ilOERinFormParam::TYPE_BOOLEAN,
            'ca_nichtkomm' => ilOERinFormParam::TYPE_BOOLEAN,
            'ca_quellen_check' => ilOERinFormParam::TYPE_BOOLEAN,
            'ca_quellen_doku' => ilOERinFormParam::TYPE_BOOLEAN,
        ],
        'check_final' => [
            'cf_freigabe' => ilOERinFormParam::TYPE_HEAD,
            'cf_konsequenzen' => ilOERinFormParam::TYPE_BOOLEAN,
            'cf_bereit' => ilOERinFormParam::TYPE_BOOLEAN,
        ]
    ];

    /** @var int obj_id */
    protected $obj_id;
	/**
	 * @var ilOERinFormParam[]	$params		parameters: 	name => ilOERinFormParam
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

                $this->params[$name] = ilOERinFormParam::_create(
                    $name,
                    $this->plugin->txt($name),
                    $info,
                    $type
                );
            }
        }
        $this->read();
	}

	public function getIncludedLicenses()
    {
        $this->plugin->includeClass('class.ilOERinFormPublishMD.php');

        $licenses = array();

        if ($this->get('sl_cc0')) $licenses[] = ilOERinFormPublishMD::CC0;
        if ($this->get('sl_cc_by')) $licenses[] = ilOERinFormPublishMD::CC_BY;
        if ($this->get('sl_cc_by_sa')) $licenses[] = ilOERinFormPublishMD::CC_BY_SA;
        if ($this->get('sl_cc_by_nd')) $licenses[] = ilOERinFormPublishMD::CC_BY_ND;
        if ($this->get('sl_cc_by_nc')) $licenses[] = ilOERinFormPublishMD::CC_BY_NC;
        if ($this->get('sl_cc_by_nc_sa')) $licenses[] = ilOERinFormPublishMD::CC_BY_NC_SA;
        if ($this->get('sl_cc_by_nc_nd')) $licenses[] = ilOERinFormPublishMD::CC_BY_NC_ND;

        return $licenses;
    }

    /**
     * Get the array of all parameters for a section
     * @return ilOERinFormParam[]
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
     * Get all parameters as an array
     * @return array name => value
     */
    public function getAllValues()
    {
        $result = array();
        foreach ($this->params as $name => $param)
        {
            $result[$name] = $param->value;
        }
        return $result;
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