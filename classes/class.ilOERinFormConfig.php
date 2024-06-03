<?php

// Copyright (c) 2018 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg, GPLv3, see LICENSE

/**
 * Data handling of parameters for the plugin configuration
 */
class ilOERinFormConfig
{
    protected ilDBInterface $db;
    protected ilOERinFormPlugin $plugin;

    /** @var ilOERinFormParam[]	*/
    protected array $params = [];

    /** @var string[] */
    public array $help_ids = [
        'oer_publishing', 'check_rights', 'select_license', 'describe_meta', 'check_attrib', 'declare_publish'
    ];


    /**
     * Constructor.
     */
    public function __construct(ilOERinFormPlugin $a_plugin_object)
    {
        global $DIC;
        $this->db = $DIC->database();
        $this->plugin = $a_plugin_object;

        /** @var ilOERinFormParam[] $params */
        $params = [];

        $params[] = ilOERinFormParam::_create(
            'config_base',
            $this->plugin->txt('config_base'),
            $this->plugin->txt('config_base_info'),
            ilOERinFormParam::TYPE_HEAD
        );
        $params[] = ilOERinFormParam::_create(
            'wiki_ref_id',
            $this->plugin->txt('wiki_ref_id'),
            $this->plugin->txt('wiki_ref_id_info'),
            ilOERinFormParam::TYPE_REF_ID
        );
        $params[] = ilOERinFormParam::_create(
            'pub_ref_id',
            $this->plugin->txt('pub_ref_id'),
            $this->plugin->txt('pub_ref_id_info'),
            ilOERinFormParam::TYPE_REF_ID
        );

        $params[] = ilOERinFormParam::_create(
            'config_base',
            $this->plugin->txt('config_help'),
            $this->plugin->txt('config_help_info'),
            ilOERinFormParam::TYPE_HEAD
        );

        foreach ($this->help_ids as $help_id) {
            $params[] = ilOERinFormParam::_create(
                $help_id,
                $this->plugin->txt($help_id),
                $this->plugin->txt($help_id . '_info'),
                ilOERinFormParam::TYPE_REF_ID
            );
        };

        foreach ($params as $param) {
            $this->params[$param->name] = $param;
        }
        $this->read();
    }

    /**
     * Get the array of all parameters
     * @return ilOERinFormParam[]
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * Get the IDs of the parameters for help urls
     * @return string[]
     */
    public function getHelpIds(): array
    {
        return $this->help_ids;
    }

    /**
     * Get the value of a named parameter
     * @return  mixed
     */
    public function get(string $name)
    {
        if (isset($this->params[$name])) {
            return $this->params[$name]->value;
        }
        return null;
    }

    /**
     * Set the value of the named parameter
     * @param mixed $value
     */
    public function set(string $name, $value = null): void
    {
        if (isset($this->params[$name])) {
            $this->params[$name]->setValue($value);
        }
    }


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
