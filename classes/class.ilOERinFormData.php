<?php

/**
 * Data handling of parameters for the puplishing of an object
 */
class ilOERinFormData extends ilOERinFormParamList
{
    protected array $param_list = [
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
            'sl_cc_by_nc_nd' => ilOERinFormParam::TYPE_BOOLEAN,
            'sl_own_choice' => ilOERinFormParam::TYPE_HEAD,
            'selected_license' => ilOERinFormParam::TYPE_TEXT
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
        ],
        'check_final' => [
            'cf_freigabe' => ilOERinFormParam::TYPE_HEAD,
            'cf_konsequenzen' => ilOERinFormParam::TYPE_BOOLEAN,
            'cf_bereit' => ilOERinFormParam::TYPE_BOOLEAN,
        ]
    ];

    /**
     * ID of the content (not reference) that should be published
     */
    protected int $obj_id;

    public function __construct(ilOERinFormPlugin $plugin, int $a_obj_id)
    {
        // set before parent constructor to be available in read()
        $this->obj_id = $a_obj_id;

        parent::__construct($plugin);
    }

    /**
     * Get the licenses thet are selected for parts of the content
     */
    public function getIncludedLicenses()
    {
        $licenses = [];

        if ($this->get('sl_cc0')) {
            $licenses[] = ilOERinFormPublishMD::CC0;
        }
        if ($this->get('sl_cc_by')) {
            $licenses[] = ilOERinFormPublishMD::CC_BY;
        }
        if ($this->get('sl_cc_by_sa')) {
            $licenses[] = ilOERinFormPublishMD::CC_BY_SA;
        }
        if ($this->get('sl_cc_by_nd')) {
            $licenses[] = ilOERinFormPublishMD::CC_BY_ND;
        }
        if ($this->get('sl_cc_by_nc')) {
            $licenses[] = ilOERinFormPublishMD::CC_BY_NC;
        }
        if ($this->get('sl_cc_by_nc_sa')) {
            $licenses[] = ilOERinFormPublishMD::CC_BY_NC_SA;
        }
        if ($this->get('sl_cc_by_nc_nd')) {
            $licenses[] = ilOERinFormPublishMD::CC_BY_NC_ND;
        }

        return $licenses;
    }


    /**
     * Read the data from the database
     */
    public function read(): void
    {
        $query = "SELECT param_name, param_value FROM oerinf_data WHERE obj_id = "
            . $this->db->quote($this->obj_id, 'integer');
        $res = $this->db->query($query);
        while($row = $this->db->fetchAssoc($res)) {
            $this->set($row['param_name'], $row['param_value']);
        }
    }

    /**
     * Write the data to the database
     */
    public function write(): void
    {
        foreach ($this->params as $param) {
            $this->db->replace(
                'oerinf_data',
                [
                    'obj_id' =>  ['integer', $this->obj_id],
                    'param_name' => ['text', $param->name]
                ],
                ['param_value' => ['text', (string) $param->value]]
            );
        }
    }
}
