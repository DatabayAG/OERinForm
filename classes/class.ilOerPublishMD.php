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
}

?>