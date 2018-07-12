<?php
// Copyright (c) 2017 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg, GPLv3, see LICENSE

/**
 * Help functions for OerInform
 *
 * @author Fred Neumann <fred.neumann@fau.de>
 *
 */
class ilOERinFormHelp
{
    protected $meta_rec_title = 'OERinForm';
    protected $meta_field_title = "ID";


    /** @var ilOERinFormPlugin */
    protected $plugin;

    /** @var ilOERinFormConfig */
    protected $config;

    /** @var array help_id => page_id */
    protected $page_map = array();

    /**
     * ilOERinFormHelp constructor.
     * @param $plugin
     */
    public function __construct($plugin)
    {
        $this->plugin = $plugin;
        $this->config = $this->plugin->getConfig();
        $this->readPageMap();
    }

    /**
     * Get a list pf all defined help ids
     * @return array
     */
    public function getAllHelpIds()
    {
        return array();
    }

    /**
     * Check if a help page with given id is available
     * @param $a_help_id
     * @return bool
     */
    public function isPageAvailable($a_help_id)
    {
        return isset($this->page_map[$a_help_id]);
    }


    /**
     * Get the id of a wiki page that can be directly shown as help
     *
     * @param string $a_help_id
     * @return int
     */
    public function getPageId($a_help_id)
    {
        if (!isset($this->page_map[$a_help_id]))
        {
            return false;
        }

        return $this->page_map[$a_help_id];
    }

    /**
     * Get the url of a wiki page that can be linked for details
     *
     * @param string $a_help_id
     * @return string
     */
    public function getDetailsUrl($a_help_id)
    {
        $ref_id = $this->config->get('wiki_ref_id');

        if ($this->isPageAvailable($a_help_id))
        {
            return 'goto.php?target=wiki_wpage_'. $this->getPageId($a_help_id) .'_' . $ref_id;
        }
        elseif(!empty($ref_id))
        {
            return 'goto.php?target=wiki_' . $ref_id;
        }

        return '';
    }


    /**
     * read the map of page_ids
     */
    protected function readPageMap()
    {
        global $DIC;
        $ilAccess = $DIC->access();
        $ref_id = $this->plugin->getConfig()->get('wiki_ref_id');
        $obj_id = ilObject::_lookupObjectId($ref_id);

        if (empty($obj_id))
        {
            return;
        }

        if (!$ilAccess->checkAccess('read', '', $ref_id))
        {
            return;
        }

        $recs = ilAdvancedMDRecord::_getSelectedRecordsByObject("wiki", $this->config->get('wiki_ref_id'), "wpg");
        /** @var ilAdvancedMDRecord $record */
        foreach($recs as $record)
        {
            if ($record->getTitle() == $this->meta_rec_title)
            {
                /** @var  ilAdvancedMDFieldDefinition $field */
                foreach(ilAdvancedMDFieldDefinition::getInstancesByRecordId($record->getRecordId(), false) as $field)
                {
                    //
                    if ($field->getTitle() == $this->meta_field_title)
                    {
                        $field_form = ilADTFactory::getInstance()->getSearchBridgeForDefinitionInstance($field->getADTDefinition(), true, false);

                        foreach ($this->getAllHelpIds() as $help_id)
                        {
                            $field->setSearchValueSerialized($field_form, serialize(array($help_id)));
                            $found_pages = $field->searchSubObjects($field_form, $obj_id, "wpg");
                            if (is_array($found_pages))
                            {
                                $this->page_map[$help_id] = $found_pages[0];
                            }
                        }
                    }
                }
            }
        }
    }
}