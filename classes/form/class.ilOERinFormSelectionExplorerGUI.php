<?php

/**
 * Class ilOERinFormSelectionExplorerGUI
 */
class ilOERinFormSelectionExplorerGUI extends ilRepositoryExplorerGUI
{
    /**
     * Set the types that can be selected
     * @var array
     */
    protected $selectableTypes = [];

    /**
     * {@inheritdoc}
     */
    public function __construct($a_parent_obj, $a_parent_cmd)
    {
        parent::__construct($a_parent_obj, $a_parent_cmd);
        $this->setTypeWhiteList(array('root', 'cat', 'crs', 'grp', 'fold'));
    }

    /**
     * Set the types that can be selected
     * @param array $a_types
     */
    public function setSelectableTypes($a_types)
    {
        $this->selectableTypes = $a_types;
        $this->setTypeWhiteList(array_merge(array('root', 'cat', 'crs', 'grp', 'fold'), $a_types));
    }

    /**
     * {@inheritdoc}
     */
    protected function isNodeSelectable($a_node)
    {
        if (!empty($this->selectableTypes))
        {
            return in_array($a_node['type'], $this->selectableTypes);
        }
        return true;
    }

    public function getNodeHref($a_node)
	{
		return '';
	}
}