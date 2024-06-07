<#1>
<?php
    /**
     * OERinForm plugin: database update script
     */

    /** @var ilDBInterface $ilDB */
    global $ilDB;
?>
<#2>
<?php
if (!$ilDB->tableExists('oerinf_config'))
{
    $fields = [
        'param_name' => [
            'type' => 'text',
            'length' => 255,
            'notnull' => true,
        ],
        'param_value' => [
            'type' => 'text',
            'length' => 255,
            'notnull' => false,
            'default' => null
        ]
    ];
    $ilDB->createTable('oerinf_config', $fields);
    $ilDB->addPrimaryKey("oerinf_config", ['param_name']);
}
?>
<#3>
<?php
if (!$ilDB->tableExists('oerinf_data'))
{
    $fields = [
        'obj_id' => [
            'type' => 'integer',
            'length' => 4,
            'notnull' => true,
        ],
        'param_name' => [
            'type' => 'text',
            'length' => 255,
            'notnull' => true,
        ],
        'param_value' => [
            'type' => 'text',
            'length' => 255,
            'notnull' => false,
            'default' => null
        ]
    ];
    $ilDB->createTable('oerinf_data', $fields);
    $ilDB->addPrimaryKey("oerinf_data", ['obj_id', 'param_name']);
}
?>
<#4>
<?php
$ilDB->modifyTableColumn('oerinf_config', 'param_value', [
    'type' => 'text',
    'length' => 4000,
    'notnull' => false,
    'default' => null
]);
?>