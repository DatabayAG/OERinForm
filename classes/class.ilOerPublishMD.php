<?php
// Copyright (c) 2017 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg, GPLv3, see LICENSE

include_once("./Services/MetaData/classes/class.ilMD.php");
 
/**
 * Extended metadata for publishing OER
 *
 * @author Fred Neumann <fred.neumann@fau.de>
 * @version $Id$
 *
 */
class ilOerPublishMD extends ilMD
{
    const CC0 = 'cc0';
    const CC_BY = 'cc_by';
    const CC_BY_SA = 'cc_by_sa';
    const CC_BY_ND = 'cc_by_nd';
    const CC_BY_NC = 'cc_by_nc';
    const CC_BY_NC_SA = 'cc_by_nc_sa';
    const CC_BY_NC_ND = 'cc_by_nc_nd';

    const STATUS_PRIVATE = 'private';
    const STATUS_READY = 'ready';
    const STATUS_PUBLIC = 'published';
    const STATUS_BROKEN = 'broken';


    /** @var array list of supported publishing formats */
    protected $publishFormats = array('oai_ilias', 'oai_dc', 'oai_lom-eaf');


    /** @var  ilOERinFormPlugin $plugin */
    protected $plugin;


    /**
     * Inject the plugin object
     * (must be called directly after construction)
     * @param ilOERinFormPlugin $a_plugin
     */
    public function setPlugin($a_plugin)
    {
        $this->plugin = $a_plugin;
    }

    public function getAuthors()
    {
        $this->md_settings = ilMDSettings::_getInstance();
        if(is_object($this->md_section = $this->getLifecycle()))
        {
            $sep = $ent_str = "";
            foreach(($ids = $this->md_section->getContributeIds()) as $con_id)
            {
                $md_con = $this->md_section->getContribute($con_id);
                if ($md_con->getRole() == "Author")
                {
                    foreach($ent_ids = $md_con->getEntityIds() as $ent_id)
                    {
                        $md_ent = $md_con->getEntity($ent_id);
                        $ent_str = $ent_str.$sep.$md_ent->getEntity();
                        $sep = $this->md_settings->getDelimiter()." ";
                    }
                }
            }
            return $ent_str;
        }
    }

    /**
     * Get a public red_id for the object
     * @return int|bool
     */
    public function getPublicRefId()
    {
        global $DIC;
        $tree = $DIC->repositoryTree();

        $cat_ref_id = $this->plugin->getConfig()->get('pub_ref_id');
        if (empty($cat_ref_id)) {
            return false;
        }
        $ref_ids = ilObject::_getAllReferences($this->rbac_id);
        foreach ($ref_ids as $ref_id) {
            if ($tree->isGrandChild($cat_ref_id, $ref_id) && !ilObject::_isInTrash($ref_id)) {
                return $ref_id;
            }
        }
        return false;
    }

    /**
     * Check if the public reference is visible for anonymous
     * @return bool
     */
    public function isPublicRefIdPublic()
    {
        global $DIC;
        $ilAccess = $DIC->access();

        if ($ref_id = $this->getPublicRefId()) {
            return (
                $ilAccess->checkAccessOfUser(ANONYMOUS_USER_ID, 'visible', '', $ref_id)
                && $ilAccess->checkAccessOfUser(ANONYMOUS_USER_ID, 'visible', '', $ref_id)
            );
        }
        return false;
    }

    /**
     * Try to create a public reference for the object
     * @param ilObject $object
     * @return int  the public ref_id
     */
    public function createPublicRefId($object)
    {
        if ($ref_id = $this->getPublicRefId()) {
            return $ref_id;
        }

        $cat_ref_id = $this->plugin->getConfig()->get('pub_ref_id');

        if (!empty($cat_ref_id) && ilObject::_lookupType($cat_ref_id, true) == 'cat') {
            $object->createReference();
            $object->putInTree($cat_ref_id);
            $object->setPermissions($cat_ref_id);
            return $this->getPublicRefId();
        }
        return false;
    }

    /**
     * Get a public url for the object
     * @return string
     */
    public function getPublicUrl()
    {
        require_once("Services/Link/classes/class.ilLink.php");
        return ilLink::_getStaticLink($this->getPublicRefId());
    }

    /**
     * Get the common path of publishing files for a format
     * @param string $format
     * @return string
     */
    public function getPublishPath($format)
    {
        return CLIENT_DATA_DIR . '/oerinf/publish/' . $format;
    }

    /**
     * get the full path of a publishing file for a format
     * @param string $format
     * @return string
     */
    public function getPublishFile($format)
    {
        return $this->getPublishPath($format) . '/ILIAS-' . sprintf('%09d', $this->getRBACId()) . '-' . $format . '.xml';
    }


    /**
     * Get the publishing date
     * @return bool|int
     */
    public function getPublishDate()
    {
        $format = $this->publishFormats[0];
        $file = $this->getPublishFile($format);
        if (is_file($file)) {
            return filemtime($file);
        }
        return false;
    }

    /**
     * Get the OAI publishing status
     * @return string
     */
    public function getPublishStatus()
    {
        global $lng;
        $date = $this->getPublishDate();
        $public = $this->isPublicRefIdPublic();

        if ($date && $public) {
            return self::STATUS_PUBLIC;
        } elseif ($date > 0 && !$public) {
            return self::STATUS_BROKEN;
        } elseif (!$date && $public) {
            return self::STATUS_READY;
        } else {
            return self::STATUS_PRIVATE;
        }
    }

    /**
     * Get an info string about the publishing status
     * @return string
     */
    public function getPublishInfo()
    {
        switch ($this->getPublishStatus()) {
            case self::STATUS_PRIVATE:
                return $this->plugin->txt("label_private");

            case self::STATUS_READY:
                return $this->plugin->txt("label_ready");

            case self::STATUS_PUBLIC:
                $date = $this->getPublishDate();
                $dateObj = new ilDateTime($date, IL_CAL_UNIX);
                return $this->plugin->txt('label_published') . ' (' . ilDatePresentation::formatDate($dateObj) . ')';

            case self::STATUS_BROKEN:
                return $this->plugin->txt("label_broken");
        }
    }


    /**
     * Publish the object
     * @return bool
     */
    public function publish()
    {
        if (!$this->isPublicRefIdPublic()) {
            return false;
        }

        include_once 'Services/MetaData/classes/class.ilMD2XML.php';
        $md2xml = new ilMD2XML($this->getRBACId(), $this->getObjId(), $this->getObjType());
        $md2xml->setExportMode(true);
        $md2xml->startExport();

        foreach ($this->publishFormats as $format) {
            ilUtil::makeDirParents($this->getPublishPath($format));
            $file = $this->getPublishFile($format);
            file_put_contents($file, $this->createPublishFormat($md2xml->getXML(), $format));
        }
        return true;
    }

    /**
     * Unpublish the object
     * @return bool
     */
    public function unpublish()
    {
        foreach ($this->publishFormats as $format) {
            $file = $this->getPublishFile($format);
            if ((is_file($file))) {
                @unlink($file);
            }
        }
        return true;
    }

    /**
     * Create the xml for a certain publishing format
     *
     * @param string $a_xml the original ilias meta data xml
     * @param string $a_format format identifier (@see $this->publishFormats)
     * @return mixed|string            xml of the target format
     */
    public function createPublishFormat($a_xml, $a_format)
    {
        $xml_doc = new DOMDocument('1.0', 'UTF-8');
        $xml_doc->loadXML($a_xml);

        $xsl_doc = new DOMDocument('1.0', 'UTF-8');
        $xsl_doc->loadXML(file_get_contents($this->plugin->getDirectory() . "/xsl/" . $a_format . ".xsl"));

        $xslt = new XSLTProcessor();
        $xslt->setParameter('', 'url', $this->getPublicUrl());
        $xslt->importStylesheet($xsl_doc);
        $xml = $xslt->transformToXml($xml_doc);

        $xml = str_replace('{ILIAS_URL}',
            str_replace('&', '&#38;', $this->getPublicUrl()), $xml);

        return $xml;
    }

    /**
     * Get the CC license identifiers for the configured CC licenses
     * @return array
     */
    public function getAvailableCCLicenses()
    {
        $map = [
            self::CC0 => '',
            self::CC_BY => '',
            self::CC_BY_SA => '',
            self::CC_BY_ND => '',
            self::CC_BY_NC => '',
            self::CC_BY_NC_ND => '',
            self::CC_BY_NC_SA => ''
        ];

        /** @var ilMDCopyrightSelectionEntry $entry */
        foreach (ilMDCopyrightSelectionEntry::_getEntries() as $entry) {
            $description = $entry->getCopyright();
            $id = 'il_copyright_entry__' . IL_INST_ID . '__' . (int)$entry->getEntryId();

            if (strpos($description, 'creativecommons.org/licenses/zero/')) {
                $map[self::CC0] = $id;
            } elseif (strpos($description, 'creativecommons.org/licenses/by/')) {
                $map[self::CC_BY] = $id;
            } elseif (strpos($description, 'creativecommons.org/licenses/by-sa/')) {
                $map[self::CC_BY_SA] = $id;
            } elseif (strpos($description, 'creativecommons.org/licenses/by-nd/')) {
                $map[self::CC_BY_ND] = $id;
            } elseif (strpos($description, 'creativecommons.org/licenses/by-nc/')) {
                $map[self::CC_BY_NC] = $id;
            } elseif (strpos($description, 'creativecommons.org/licenses/by-nc-nd/')) {
                $map[self::CC_BY_NC_ND] = $id;
            } elseif (strpos($description, 'creativecommons.org/licenses/by-nc-sa/')) {
                $map[self::CC_BY_NC_SA] = $id;
            }
        }

        $available = [];
        foreach ($map as $cc => $value) {
            if (!empty($value)) {
                $available[$cc] = $value;
            }
        }

        return $available;
    }

    /**
     * Get the CC license of the current object
     * @return string
     */
    public function getCCLicense()
    {
        $license = ilMDRights::_lookupDescription($this->getRBACId(), $this->getObjId());
        foreach ($this->getAvailableCCLicenses() as $cc => $value) {
            if ($license == $value) {
                return $cc;
            }
        }
        return '';
    }

    /**
     * Mix cc licenses
     * @param $inArray
     * @return array
     */
    public function ccMixer($inArray)
    {
        $cczero = true;
        $ccby = true;
        $ccbysa = true;
        $ccbync = true;
        $ccbynd = true;
        $ccbyncsa = true;
        $ccbyncnd = true;

        //CREATE THREE DIMENSIONAL ARRAY!
        $combination = array();

        // CC-0
        $combination[0][0] = [true, true, true, true, true, true, true];
        $combination[0][1] = [false, true, true, true, true, true, true];
        $combination[0][2] = [false, false, true, false, false, false, false];
        $combination[0][3] = [false, false, false, true, false, true, true];
        $combination[0][4] = [false, false, false, false, false, false, false];
        $combination[0][5] = [false, false, false, false, false, true, false];
        $combination[0][6] = [false, false, false, false, false, false, false];

        // CC-BY
        $combination[1][0] = [false, true, true, true, true, true, true];
        $combination[1][1] = [false, true, true, true, true, true, true];
        $combination[1][2] = [false, false, true, false, false, false, false];
        $combination[1][3] = [false, false, false, true, false, true, true];
        $combination[1][4] = [false, false, false, false, false, false, false];
        $combination[1][5] = [false, false, false, false, false, true, false];
        $combination[1][6] = [false, false, false, false, false, false, false];

        // CC-BY-SA
        $combination[2][0] = [false, false, true, false, false, false, false];
        $combination[2][1] = [false, false, true, false, false, false, false];
        $combination[2][2] = [false, false, true, false, false, false, false];
        $combination[2][3] = [false, false, false, false, false, false, false];
        $combination[2][4] = [false, false, false, false, false, false, false];
        $combination[2][5] = [false, false, false, false, false, false, false];
        $combination[2][6] = [false, false, false, false, false, false, false];

        // CC-BY-NC
        $combination[3][0] = [false, false, false, true, false, true, true];
        $combination[3][1] = [false, false, false, true, false, true, true];
        $combination[3][2] = [false, false, false, false, false, false, false];
        $combination[3][3] = [false, false, false, true, false, true, true];
        $combination[3][4] = [false, false, false, false, false, false, false];
        $combination[3][5] = [false, false, false, false, false, true, false];
        $combination[3][6] = [false, false, false, false, false, false, false];

        // CC-BY-ND
        $combination[4][0] = [false, false, false, false, false, false, false];
        $combination[4][1] = [false, false, false, false, false, false, false];
        $combination[4][2] = [false, false, false, false, false, false, false];
        $combination[4][3] = [false, false, false, false, false, false, false];
        $combination[4][4] = [false, false, false, false, false, false, false];
        $combination[4][5] = [false, false, false, false, false, false, false];
        $combination[4][6] = [false, false, false, false, false, false, false];

        // CC-BY-NC-SA
        $combination[5][0] = [false, false, false, false, false, true, false];
        $combination[5][1] = [false, false, false, false, false, true, false];
        $combination[5][2] = [false, false, false, false, false, true, false];
        $combination[5][3] = [false, false, false, false, false, true, false];
        $combination[5][4] = [false, false, false, false, false, false, false];
        $combination[5][5] = [false, false, false, false, false, true, false];
        $combination[5][6] = [false, false, false, false, false, false, false];

        // CC-BY-NC-ND
        $combination[6][0] = [false, false, false, false, false, false, false];
        $combination[6][1] = [false, false, false, false, false, false, false];
        $combination[6][2] = [false, false, false, false, false, false, false];
        $combination[6][3] = [false, false, false, false, false, false, false];
        $combination[6][4] = [false, false, false, false, false, false, false];
        $combination[6][5] = [false, false, false, false, false, false, false];
        $combination[6][6] = [false, false, false, false, false, false, false];

        $mixedLicenses = array();
        $map = array(
            self::CC0 => 0,
            self::CC_BY => 1,
            self::CC_BY_SA => 2,
            self::CC_BY_NC => 3,
            self::CC_BY_ND => 4,
            self::CC_BY_NC_SA => 5,
            self::CC_BY_NC_ND => 6,
        );
        foreach ($inArray as $license) {
            if (isset($map[$license])) {
                $mixedLicenses[] = $map[$license];
            }
        }


        for ($i = 0; $i < count($mixedLicenses); $i++) {
            for ($n = 0; $n < 7; $n++) {
                $a = $mixedLicenses[0];
                $b = $mixedLicenses[$i];

                $check = $combination[$a][$b][$n];

                if (($n == 0) & (!$check)) {
                    $cczero = false;
                }
                if (($n == 1) & (!$check)) {
                    $ccby = false;
                }
                if (($n == 2) & (!$check)) {
                    $ccbysa = false;
                }
                if (($n == 3) & (!$check)) {
                    $ccbync = false;
                }
                if (($n == 4) & (!$check)) {
                    $ccbynd = false;
                }
                if (($n == 5) & (!$check)) {
                    $ccbyncsa = false;
                }
                if (($n == 6) & (!$check)) {
                    $ccbyncnd = false;
                }
            }
        }

        $result = array();
        if ($cczero) $result[] = self::CC0;
        if ($ccby) $result[] = self::CC_BY;
        if ($ccbysa) $result[] = self::CC_BY_SA;
        if ($ccbync) $result[] = self::CC_BY_NC;
        if ($ccbynd) $result[] = self::CC_BY_ND;
        if ($ccbyncsa) $result[] = self::CC_BY_NC_SA;
        if ($ccbyncnd) $result[] = self::CC_BY_NC_ND;

        return $result;
    }
}

?>