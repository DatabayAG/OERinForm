<?php

/**
 * Extended metadata for publishing OER
 */
class ilOERinFormPublishMD extends ilMD
{
    public const CC0 = 'cc0';
    public const CC_BY = 'cc_by';
    public const CC_BY_SA = 'cc_by_sa';
    public const CC_BY_ND = 'cc_by_nd';
    public const CC_BY_NC = 'cc_by_nc';
    public const CC_BY_NC_SA = 'cc_by_nc_sa';
    public const CC_BY_NC_ND = 'cc_by_nc_nd';

    public const STATUS_PRIVATE = 'private';
    public const STATUS_READY = 'ready';
    public const STATUS_PUBLIC = 'published';
    public const STATUS_BROKEN = 'broken';


    /** list of supported publishing formats */
    protected array $publishFormats = ['oai_ilias', 'oai_dc', 'oai_lom-eaf'];


    protected ilTree $tree;
    protected ilAccessHandler $access;
    protected ilOERinFormPlugin $plugin;

    protected ilMDGeneral $secGeneral;
    protected ilMDLifecycle $secLifecycle;

    public function __construct(
        int $a_rbac_id = 0,
        int $a_obj_id = 0,
        string $a_type = ''
    ) {
        parent::__construct($a_rbac_id, $a_obj_id, $a_type);

        global $DIC;
        $this->tree = $DIC->repositoryTree();
        $this->access = $DIC->access();

        $this->plugin = ilOERinFormPlugin::getInstance();
    }


    /**
     * Get the ref_id of the published the object
     */
    protected function getPublicRefId(): ?int
    {
        $cat_ref_id = $this->plugin->getConfig()->get('pub_ref_id');
        if (empty($cat_ref_id)) {
            return null;
        }
        $ref_ids = ilObject::_getAllReferences($this->getRBACId());
        foreach ($ref_ids as $ref_id) {
            if ($this->tree->isGrandChild($cat_ref_id, $ref_id) && !ilObject::_isInTrash($ref_id)) {
                return $ref_id;
            }
        }
        return null;
    }

    /**
     * Check if the public reference is visible for anonymous
     */
    public function isPublicRefIdPublic(): bool
    {
        if (!empty($ref_id = $this->getPublicRefId())) {
            return (
                $this->access->checkAccessOfUser(ANONYMOUS_USER_ID, 'visible', '', $ref_id)
                && $this->access->checkAccessOfUser(ANONYMOUS_USER_ID, 'visible', '', $ref_id)
            );
        }
        return false;
    }

    /**
     * Try to create a public reference for the object
     */
    public function createPublicRefId(int $obj_id): ?int
    {
        if (!empty($ref_id = $this->getPublicRefId())) {
            return $ref_id;
        }

        $cat_ref_id = $this->plugin->getConfig()->get('pub_ref_id');

        if (!empty($cat_ref_id) && ilObject::_lookupType($cat_ref_id, true) == 'cat') {
            $object = ilObjectFactory::getInstanceByObjId($obj_id);
            $object->createReference();
            $object->putInTree($cat_ref_id);
            $object->setPermissions($cat_ref_id);
            return $this->getPublicRefId();
        }
        return null;
    }

    /**
     * Get a public url for the object
     * @return string
     */
    public function getPublicUrl(): string
    {
        if (!empty($ref_id = $this->getPublicRefId())) {
            return ilLink::_getStaticLink($this->getPublicRefId());
        }
        return '';
    }

    /**
     * Get the common path of publishing files for a format
     */
    public function getPublishPath(string $format): string
    {
        return CLIENT_DATA_DIR . '/oerinf/publish/' . $format;
    }

    /**
     * Get the full path of a publishing file for a format
     */
    public function getPublishFile(string $format): string
    {
        return $this->getPublishPath($format) . '/ILIAS-'
            . sprintf('%09d', IL_INST_ID) . '-'
            . sprintf('%09d', $this->getRBACId()) . '-' . $format . '.xml';
    }


    /**
     * Get the publishing date
     */
    protected function getPublishDate(): ?int
    {
        $format = $this->publishFormats[0];
        $file = $this->getPublishFile($format);
        if (is_file($file)) {
            return filemtime($file);
        }
        return null;
    }

    /**
     * Get the OAI publishing status
     */
    public function getPublishStatus(): string
    {
        $date = $this->getPublishDate();
        $public = $this->isPublicRefIdPublic();

        if (isset($date) && $public) {
            return self::STATUS_PUBLIC;
        } elseif (isset($date) && !$public) {
            return self::STATUS_BROKEN;
        } elseif (!isset($date) && $public) {
            return self::STATUS_READY;
        } else {
            return self::STATUS_PRIVATE;
        }
    }

    /**
     * Get an info string about the publishing status
     * @return string
     */
    public function getPublishInfo(): string
    {
        switch ($this->getPublishStatus()) {
            case self::STATUS_PRIVATE:
                return $this->plugin->txt('label_private');

            case self::STATUS_READY:
                return $this->plugin->txt('label_ready');

            case self::STATUS_PUBLIC:
                $date = $this->getPublishDate();
                $dateObj = new ilDateTime($date, IL_CAL_UNIX);
                return $this->plugin->txt('label_published') . ' (' . ilDatePresentation::formatDate($dateObj) . ')';

            case self::STATUS_BROKEN:
            default:
                return $this->plugin->txt("label_broken");
        }
    }

    /**
     * Get an instanciated general section
     */
    protected function getSectionGeneral(): ilMDGeneral
    {
        if (!isset($this->secGeneral)) {
            $this->secGeneral = $this->getGeneral() ?? $this->addGeneral();
        }
        return $this->secGeneral;
    }

    /**
     * Get an instanciated lifecycle section
     */
    protected function getSectionLifecycle(): ilMDLifecycle
    {
        if (!isset($this->secLifecycle)) {
            $this->secLifecycle = $this->getLifecycle() ?? $this->addLifecycle();
        }
        return $this->secLifecycle;
    }

    /**
     * Get a comma separated list of keywords
     */
    public function getKeywords(): string
    {
        /** @var ilMDSettings $settings */
        $settings = ilMDSettings::_getInstance();
        if (is_object($general = $this->getGeneral())) {
            $keywords = [];
            foreach($ids = $general->getKeywordIds() as $id) {
                $md_key = $general->getKeyword($id);
                $keywords[] = $md_key->getKeyword();
            }
            return implode($settings->getDelimiter() . ' ', $keywords);
        }
        return '';
    }

    /**
     * Get a comma separated list of authors
     */
    public function getAuthors(): string
    {
        $settings = ilMDSettings::_getInstance();
        if (is_object($lifecycle = $this->getLifecycle())) {
            $sep = $author = '';
            foreach(($ids = $lifecycle->getContributeIds()) as $con_id) {
                $md_con = $lifecycle->getContribute($con_id);
                if ($md_con->getRole() == 'Author') {
                    foreach($ent_ids = $md_con->getEntityIds() as $ent_id) {
                        $md_ent = $md_con->getEntity($ent_id);
                        $author = $author . $sep . $md_ent->getEntity();
                        $sep = $settings->getDelimiter() . ' ';
                    }
                }
            }
            return $author;
        }
        return '';
    }

    /**
     * Get the Copyright description
     */
    public function getCopyrightDescription(): string
    {
        $copyright = '';
        if(is_object($rights = $this->getRights())) {
            $copyright = ilMDUtils::_parseCopyright($rights->getDescription());
        }
        return $copyright;
    }

    /**
     * Create the oai files for publishing the object
     */
    public function publish(): bool
    {
        if (!$this->isPublicRefIdPublic()) {
            return false;
        }

        $md2xml = new ilMD2XML($this->getRBACId(), $this->getObjId(), $this->getObjType());
        $md2xml->setExportMode(true);
        $md2xml->startExport();

        foreach ($this->publishFormats as $format) {
            ilFileUtils::makeDirParents($this->getPublishPath($format));
            $file = $this->getPublishFile($format);
            file_put_contents($file, $this->createPublishFormat($md2xml->getXML(), $format));
        }
        return true;
    }

    /**
     * Delete the oai files for unpublishing the object
     * @return bool
     */
    public function unpublish(): bool
    {
        foreach ($this->publishFormats as $format) {
            $file = $this->getPublishFile($format);
            if ((is_file($file))) {
                try {
                    unlink($file);
                } catch(Exception $e) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Create the xml for a certain publishing format
     *
     * @param string $a_xml the original ilias meta data xml
     * @param string $a_format format identifier
     * @return string xml of the target format
     * @see self::$publishFormats
     */
    public function createPublishFormat(string $a_xml, string $a_format): string
    {
        $xml_doc = new DOMDocument('1.0', 'UTF-8');
        $xml_doc->loadXML($a_xml);

        $xsl_doc = new DOMDocument('1.0', 'UTF-8');
        $xsl_doc->loadXML(file_get_contents($this->plugin->getDirectory() . "/xsl/" . $a_format . '.xsl'));

        $xslt = new XSLTProcessor();
        $xslt->setParameter('', 'url', $this->getPublicUrl());
        $xslt->importStylesheet($xsl_doc);
        $xml = $xslt->transformToXml($xml_doc);

        $xml = str_replace(
            '{ILIAS_URL}',
            str_replace('&', '&#38;', $this->getPublicUrl()),
            $xml
        );

        return $xml;
    }

    /**
     * Get the CC license identifiers for the configured CC licenses
     * @return string[] lsit of license constants
     */
    public function getAvailableCCLicenses(): array
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
            $link = $entry->getCopyrightData()->link();
            $id = 'il_copyright_entry__' . IL_INST_ID . '__' . $entry->getEntryId();

            if (strpos($link, 'creativecommons.org/licenses/zero/')) {
                $map[self::CC0] = $id;
            } elseif (strpos($link, 'creativecommons.org/licenses/by/4.0')) {
                $map[self::CC_BY] = $id;
            } elseif (strpos($link, 'creativecommons.org/licenses/by-sa/4.0')) {
                $map[self::CC_BY_SA] = $id;
            } elseif (strpos($link, 'creativecommons.org/licenses/by-nd/4.0')) {
                $map[self::CC_BY_ND] = $id;
            } elseif (strpos($link, 'creativecommons.org/licenses/by-nc/4.0')) {
                $map[self::CC_BY_NC] = $id;
            } elseif (strpos($link, 'creativecommons.org/licenses/by-nc-nd/4.0')) {
                $map[self::CC_BY_NC_ND] = $id;
            } elseif (strpos($link, 'creativecommons.org/licenses/by-nc-sa/4.0')) {
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
     * @string license constant
     */
    public function getCCLicense(): string
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
     * ported from https://github.com/rootzoll/ccmixer
     *
     * @param string[] $inArray list of license constants for the parts
     * @return string[] list of possible license constants for the whole
     */
    public function ccMixer(array $inArray): array
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

        $mixedLicenses = [];
        $map = [
            self::CC0 => 0,
            self::CC_BY => 1,
            self::CC_BY_SA => 2,
            self::CC_BY_NC => 3,
            self::CC_BY_ND => 4,
            self::CC_BY_NC_SA => 5,
            self::CC_BY_NC_ND => 6,
        ];
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

        $result = [];
        if ($cczero) {
            $result[] = self::CC0;
        }
        if ($ccby) {
            $result[] = self::CC_BY;
        }
        if ($ccbysa) {
            $result[] = self::CC_BY_SA;
        }
        if ($ccbync) {
            $result[] = self::CC_BY_NC;
        }
        if ($ccbynd) {
            $result[] = self::CC_BY_ND;
        }
        if ($ccbyncsa) {
            $result[] = self::CC_BY_NC_SA;
        }
        if ($ccbyncnd) {
            $result[] = self::CC_BY_NC_ND;
        }

        return $result;
    }
}
