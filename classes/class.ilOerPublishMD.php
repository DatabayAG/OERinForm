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
	protected $publishFormats = array('oai_ilias','oai_dc','oai_lom-eaf');


	/** @var  ilOERinFormPlugin $plugin */
	protected $plugin;


	/**
	 * Inject the plugin object
	 * (must be called directly after construction)
	 * @param ilOERinFormPlugin	$a_plugin
	 */
	public function setPlugin($a_plugin)
	{
		$this->plugin = $a_plugin;
	}


	/**
	 * Get a public red_id for the object
	 * @return int|bool
	 */
	public function getPublicRefId()
	{
		/** @var ilAccessHandler $ilAccess */
		global $ilAccess;

		$ref_ids = ilObject::_getAllReferences($this->rbac_id);
		foreach ($ref_ids as $ref_id)
		{
			if ($ilAccess->checkAccessOfUser(ANONYMOUS_USER_ID, 'visible', '', $ref_id))
			{
				return $ref_id;
			}
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
		return CLIENT_DATA_DIR .'/oerinf/publish/'.$format;
	}

	/**
	 * get the full path of a publishing file for a format
	 * @param string $format
	 * @return string
	 */
	public function getPublishFile($format)
	{
		return $this->getPublishPath($format).'/ILIAS-'.sprintf('%09d', $this->getRBACId()).'-'.$format.'.xml';
	}


	/**
	 * Get the publishing date
	 * @return bool|int
	 */
	public function getPublishDate()
	{
		$format = $this->publishFormats[0];
		$file = $this->getPublishFile($format);
		if (is_file($file))
		{
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
		$ref_id = $this->getPublicRefId();

		if ($date  && $ref_id )
		{
			return self::STATUS_PUBLIC;
		}
		elseif ($date > 0 && !$ref_id)
		{
			return self::STATUS_BROKEN;
		}
		elseif (!$date && $ref_id)
		{
			return self::STATUS_READY;
		}
		else
		{
			return self::STATUS_PRIVATE;
		}
	}

	/**
	 * Get an info string about the publishing status
	 * @return string
	 */
	public function getPublishInfo()
	{
		switch ($this->getPublishStatus())
		{
			case self::STATUS_PRIVATE:
				return $this->plugin->txt("label_private");

			case self::STATUS_READY:
				return $this->plugin->txt("label_ready");

			case self::STATUS_PUBLIC:
				$date = $this->getPublishDate();
				$dateObj = new ilDateTime($date,IL_CAL_UNIX);
				return $this->plugin->txt('label_published').' ('.ilDatePresentation::formatDate($dateObj).')';

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
		$ref_id = $this->getPublicRefId();
		if (empty($ref_id))
		{
			return false;
		}

		include_once 'Services/MetaData/classes/class.ilMD2XML.php';
		$md2xml = new ilMD2XML($this->getRBACId(),$this->getObjId(),$this->getObjType());
		$md2xml->setExportMode(true);
		$md2xml->startExport();

		foreach($this->publishFormats as $format)
		{
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
		foreach($this->publishFormats as $format)
		{
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
	 * @param string 	$a_xml		the original ilias meta data xml
	 * @param string	$a_format	format identifier (@see $this->publishFormats)
	 * @return mixed|string			xml of the target format
	 */
	public function createPublishFormat($a_xml, $a_format)
	{
		$xml_doc = new DOMDocument('1.0', 'UTF-8');
		$xml_doc->loadXML($a_xml);

		$xsl_doc = new DOMDocument('1.0', 'UTF-8');
		$xsl_doc->loadXML(file_get_contents($this->plugin->getDirectory()."/xsl/".$a_format.".xsl"));

		$xslt = new XSLTProcessor();
		$xslt->setParameter('','url', $this->getPublicUrl());
		$xslt->importStylesheet($xsl_doc);
		$xml = $xslt->transformToXml($xml_doc);

		$xml = str_replace('{ILIAS_URL}',
			str_replace('&','&#38;',$this->getPublicUrl()), $xml);

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
        foreach (ilMDCopyrightSelectionEntry::_getEntries() as $entry)
        {
            $description = $entry->getCopyright();
            $id = 'il_copyright_entry__'.IL_INST_ID.'__'.(int) $entry->getEntryId();

            if (strpos($description, 'creativecommons.org/licenses/zero/'))
            {
                $map[self::CC0] = $id;
            }
            elseif (strpos($description, 'creativecommons.org/licenses/by/'))
            {
                $map[self::CC_BY] = $id;
            }
            elseif (strpos($description, 'creativecommons.org/licenses/by-sa/'))
            {
                $map[self::CC_BY_SA] = $id;
            }
            elseif (strpos($description, 'creativecommons.org/licenses/by-nd/'))
            {
                $map[self::CC_BY_ND] = $id;
            }
            elseif (strpos($description, 'creativecommons.org/licenses/by-nc/'))
            {
                $map[self::CC_BY_NC] = $id;
            }
            elseif (strpos($description, 'creativecommons.org/licenses/by-nc-nd/'))
            {
                $map[self::CC_BY_NC_ND] = $id;
            }
            elseif (strpos($description, 'creativecommons.org/licenses/by-nc-sa/'))
            {
                $map[self::CC_BY_NC_SA] = $id;
            }
        }

        $available = [];
        foreach ($map as $cc => $value)
        {
            if (!empty($value))
            {
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
	    foreach ($this->getAvailableCCLicenses() as $cc => $value)
        {
            if ($license == $value)
            {
                return $cc;
            }
        }
        return '';
    }
}

?>