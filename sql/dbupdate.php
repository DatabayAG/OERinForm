<#1>
<?php
    /**
     * Copyright (c) 2017 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg
     * GPLv3, see docs/LICENSE
     */

    /**
     * OERinForm plugin: database update script
     */
?>
<#2>
<?php
if (!$ilDB->tableExists('oerinf_config'))
{
    $fields = array(
        'param_name' => array(
            'type' => 'text',
            'length' => 255,
            'notnull' => true,
        ),
        'param_value' => array(
            'type' => 'text',
            'length' => 255,
            'notnull' => false,
            'default' => null
        )
    );
    $ilDB->createTable("oerinf_config", $fields);
    $ilDB->addPrimaryKey("oerinf_config", array("param_name"));
}
?>
<#3>
<?php
if (!$ilDB->tableExists('oerinf_data'))
{
    $fields = array(
        'obj_id' => array(
            'type' => 'integer',
            'length' => 4,
            'notnull' => true,
        ),
        'param_name' => array(
            'type' => 'text',
            'length' => 255,
            'notnull' => true,
        ),
        'param_value' => array(
            'type' => 'text',
            'length' => 255,
            'notnull' => false,
            'default' => null
        )
    );
    $ilDB->createTable("oerinf_data", $fields);
    $ilDB->addPrimaryKey("oerinf_data", array("obj_id", "param_name"));
}
?>
