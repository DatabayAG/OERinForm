<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
*   Storing of class dependent values in session
*/
class ilOerSessionValues
{

	/**
	* Constructor
	*
	* @param    string      identifier for the session values (e.g. gui class name)
	*/
	function __construct($a_identifier)
	{
		// initialize the SESSION array to store session variables
		if (!is_array($_SESSION[$a_identifier]))
		{
			$_SESSION[$a_identifier] = array();
		}
		$this->values =& $_SESSION[$a_identifier];
		
		//echo $a_identifier;
		//var_dump($this->values);
	}


	/**
	* Save a request value in sesion and return it
	* Slashes are stripped from the request value
	*
	* @param    string      name of the section in session values
	* @param    string      name of the GET or POST or variable
	* @return   mixed       value
	*/
	function saveRequestValue($a_section, $a_name)
	{
	    $value = $this->getRequestValue($a_name);
		if (!isset($value))
		{
	        // treat a non-existing request value as empty string
	        $value = '';
	    }
	    $this->setSessionValue($a_section, $a_name, $value);
		return $value;
	}


	/**
	* Get any value that is set (request or session or default)
	* Slashes are stripped from the request value
	*
	* @param    string      name of the section in session values
	* @param    string      name of the GET or POST or variable
	* @param    mixed       default value
	* @return   mixed       value
	*/
	function getAnyValue($a_section, $a_name, $a_default_value = NULL)
	{
		$value = $this->getRequestValue($a_name);

	    if (isset($value))
		{
	        return $value;
		}
		else
		{
	        return $this->getSessionValue($a_section, $a_name, $a_default_value);
		}
	}


	/**
	* Read a value that is either coming from GET, POST
	* Slashes are stripped from request value
	*
	* @param    string      name of the GET or POST or variable
	* @return   mixed       value or null if not found;
	*/
	function getRequestValue($a_name)
	{
		if (isset($_GET[$a_name]))
		{
			return ilUtil::stripSlashesRecursive($_GET[$a_name]);
		}
		elseif (isset($_POST[$a_name]))
		{
			return ilUtil::stripSlashesRecursive($_POST[$a_name]);
		}
		else
		{
			return NULL;
	    }
	}


	/**
	* Get a value from the session variables
	*
	* @param    string      name of the section in session values
	* @param    string      name of the variable
	* @param    mixed       default value
	* @return   mixed       value
	*/
	function getSessionValue($a_section, $a_name, $a_default_value = NULL)
	{
		if (isset($this->values[$a_section][$a_name]))
		{
			return $this->values[$a_section][$a_name];
		}
		else
		{
			return $a_default_value;
		}
	}


	/**
	* Get a  date object from the session variables
	* The session value is an array as saved from a DateTimeInput field
	*
	* @param    string      name of the section in session values
	* @param    string      name of the variable
	* @param    integer     default date (unix timestamp)
	* @return
	*/
	function getSessionDateValue($a_section, $a_name, $a_default_timestamp = 0)
	{
		global $ilUser;

		include_once('./Services/Calendar/classes/class.ilDateTime.php');

		$value = $this->getSessionValue($a_section, $a_name);

		if (!is_array($value))
		{
			return new ilDateTime($a_default_timestamp, IL_CAL_UNIX);
	    }
		else
		{
			$dt['year'] = (int) $value['date']['y'];
			$dt['mon'] = (int) $value['date']['m'];
			$dt['mday'] = (int) $value['date']['d'];
			$dt['hours'] = (int) $value['time']['h'];
			$dt['minutes'] = (int) $value['time']['m'];
			$dt['seconds'] = (int) $value['time']['s'];

			return new ilDateTime($dt,IL_CAL_FKT_GETDATE,$ilUser->getTimeZone());
		}
	}

	/**
	 * Get a start date object from the session variables
	 * The session value is an array as saved from a DurationInput field
	 *
	 * @param    string      name of the section in session values
	 * @param    string      name of the variable
	 * @param    integer     default date (unix timestamp)
	 * @return
	 */
	function getSessionDurationStart($a_section, $a_name, $a_default_timestamp = 0)
	{
		global $ilUser;

		include_once('./Services/Calendar/classes/class.ilDateTime.php');

		$value = $this->getSessionValue($a_section, $a_name);

		if (!is_array($value))
		{
			return new ilDateTime($a_default_timestamp, IL_CAL_UNIX);
		}
		else
		{
			$dt['year'] = (int) $value['start']['date']['y'];
			$dt['mon'] = (int) $value['start']['date']['m'];
			$dt['mday'] = (int) $value['start']['date']['d'];
			$dt['hours'] = (int) $value['start']['time']['h'];
			$dt['minutes'] = (int) $value['start']['time']['m'];
			$dt['seconds'] = (int) $value['start']['time']['s'];

			return new ilDateTime($dt,IL_CAL_FKT_GETDATE,$ilUser->getTimeZone());
		}
	}

	/**
	 * Get an end date object from the session variables
	 * The session value is an array as saved from a DurationInput field
	 *
	 * @param    string      name of the section in session values
	 * @param    string      name of the variable
	 * @param    integer     default date (unix timestamp)
	 * @return
	 */
	function getSessionDurationEnd($a_section, $a_name, $a_default_timestamp = 0)
	{
		global $ilUser;

		include_once('./Services/Calendar/classes/class.ilDateTime.php');

		$value = $this->getSessionValue($a_section, $a_name);

		if (!is_array($value))
		{
			return new ilDateTime($a_default_timestamp, IL_CAL_UNIX);
		}
		else
		{
			$dt['year'] = (int) $value['end']['date']['y'];
			$dt['mon'] = (int) $value['end']['date']['m'];
			$dt['mday'] = (int) $value['end']['date']['d'];
			$dt['hours'] = (int) $value['end']['time']['h'];
			$dt['minutes'] = (int) $value['end']['time']['m'];
			$dt['seconds'] = (int) $value['end']['time']['s'];

			return new ilDateTime($dt,IL_CAL_FKT_GETDATE,$ilUser->getTimeZone());
		}
	}


	/**
	* Get all session values from a section
	*
	* @param    string      name of the section in session values
	* @return   array       section (key => value)
	*/
	function getSessionValues($a_section)
	{
	    if (is_array($this->values[$a_section]))
		{
	        return $this->values[$a_section];
		}
		else
		{
	        return array();
	    }
	}


	/**
	* Set a value in the session variables
	*
	* @param    string      name of the section in session values
	* @param    string      name of the variable
	* @param    mixed       value
	*/
	function setSessionValue($a_section, $a_name, $a_value)
	{
		if (!isset($this->values[$a_section]))
		{
			$this->values[$a_section] = array();
		}

		$this->values[$a_section][$a_name] = $a_value;
	}


	/**
	* Delete all session values of a specific section
	*
	* @param    string      name of the section in session values
	*/
	function deleteSessionValues($a_section)
	{
		unset($this->values[$a_section]);
	}


	/**
	* Delete all session values
	*/
	function deleteAllSessionValues()
	{
		$this->values = array();
	}

}
?>
