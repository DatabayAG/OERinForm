<?php

/**
 * Data handling of parameters for the plugin configuration
 */
class ilOERinFormConfig extends ilOERinFormParamList
{
    protected array $param_list = [
        'base' => [
            'config_base' => ilOERinFormParam::TYPE_HEAD,
            'pub_ref_id' => ilOERinFormParam::TYPE_CATEGORY,
        ],
        'help' => [
            'config_help' => ilOERinFormParam::TYPE_HEAD,
            'oer_publishing' => ilOERinFormParam::TYPE_URL,
            'check_rights' => ilOERinFormParam::TYPE_URL,
            'select_license' => ilOERinFormParam::TYPE_URL,
            'describe_meta' => ilOERinFormParam::TYPE_URL,
            'check_attrib' => ilOERinFormParam::TYPE_URL,
            'declare_publish' => ilOERinFormParam::TYPE_URL,
        ],
        'mail' => [
            'config_mail' => ilOERinFormParam::TYPE_HEAD,
        ]
    ];


    /**
     * Read the configuration from the database
     */
    public function read(): void
    {
        $query = "SELECT * FROM oerinf_config";
        $res = $this->db->query($query);
        while($row = $this->db->fetchAssoc($res)) {
            $this->set((string) $row['param_name'], $row['param_value']);
        }
    }

    /**
     * Write the configuration to the database
     */
    public function write(): void
    {
        foreach ($this->params as $param) {
            $this->db->replace(
                'oerinf_config',
                array('param_name' => array('text', $param->name)),
                array('param_value' => array('text', (string) $param->value))
            );
        }
    }
}
