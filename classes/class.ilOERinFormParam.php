<?php

/**
 * Data structure for a parameter in plugin configuration or in the puplishing of an object
 * Provides form elements and casting of value inputs for parameter types
 */
class ilOERinFormParam
{
    //
    // Defined parameter types
    //
    public const TYPE_HEAD = 'head';
    public const TYPE_TEXT = 'text';
    public const TYPE_LONGTEXT = 'longtext';
    public const TYPE_BOOLEAN = 'bool';
    public const TYPE_CATEGORY = 'category';
    public const TYPE_URL = 'url';
    public const TYPE_MAIL = 'mail';

    //
    // Parameter properties
    //
    public string $name;            // must be unique
    public string $title;
    public string $description;
    public string $type;
    public $value;                  // type depends on parameter type

    public static function _create(
        string $a_name,
        string $a_title,
        string $a_description,
        string $a_type = self::TYPE_TEXT,
        $a_value = null
    ): ilOERinFormParam {
        $param = new self();
        $param->name = $a_name;
        $param->title = $a_title;
        $param->description = $a_description;
        $param->type = $a_type;
        $param->value = $a_value;
        return $param;
    }

    /**
     * Set the value and cast it to the correct type
     */
    public function setValue($value = null): void
    {
        switch($this->type) {
            case self::TYPE_URL:
            case self::TYPE_LONGTEXT:
            case self::TYPE_TEXT:
            case self::TYPE_MAIL:
            $this->value = empty($value) ? null : (string) $value;
                break;
            case self::TYPE_BOOLEAN:
                $this->value = (bool) $value;
                break;
            case self::TYPE_CATEGORY:
                $this->value = empty($value) ? null : (int) $value;
                break;
        }
    }

    /**
     * Get a form item for setting the parameter
     * @return ilFormPropertyGUI|ilFormSectionHeaderGUI
     */
    public function getFormItem()
    {
        $title = $this->title;
        $description = $this->description;
        $postvar = $this->name;

        switch($this->type) {
            case self::TYPE_HEAD:
                $item = new ilFormSectionHeaderGUI();
                $item->setTitle($title);
                break;
            case self::TYPE_CATEGORY:
                $item = new ilRepositorySelector2InputGUI($title, $postvar);
                $item->getExplorerGUI()->setClickableTypes(['cat']);
                $item->getExplorerGUI()->setSelectableTypes(['cat']);
                $item->setValue($this->value);
                break;
            case self::TYPE_BOOLEAN:
                $item = new ilCheckboxInputGUI($title, $postvar);
                $item->setChecked((bool) $this->value);
                break;
            case self::TYPE_URL:
                $item = new ilOERInFormUriInputGUI($title, $postvar);
                $item->setRequired(false);
                $item->setMaxLength(255);
                $item->setValue($this->value);
                break;
            case self::TYPE_MAIL:
                $item = new ilEMailInputGUI($title, $postvar);
                $item->setRequired(false);
                $item->setMaxLength(255);
                $item->setValue((string) $this->value);
                break;
            case self::TYPE_LONGTEXT:
                $item = new ilTextAreaInputGUI($title, $postvar);
                $item->setCols(60);
                $item->setRows(10);
                $item->setMaxNumOfChars(4000);
                $item->setValue((string) $this->value);
                break;
            case self::TYPE_TEXT:
            default:
                $item = new ilTextInputGUI($title, $postvar);
                $item->setMaxLength(255);
                $item->setValue($this->value);
                break;
        }

        if (strpos($description, '-') !== 0) {
            $item->setInfo($description);
        }

        return $item;
    }
}
