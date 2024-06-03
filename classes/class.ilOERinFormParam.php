<?php

// Copyright (c) 2018 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg, GPLv3, see LICENSE

/**
 * Data structure for a parameter in plugin configuration or in the puplishing of an object
 * Provides form elements and casting of value inputs fpr parameter types
 */
class ilOERinFormParam
{
    //
    // Defined parameter types
    //
    public const TYPE_HEAD = 'head';
    public const TYPE_TEXT = 'text';
    public const TYPE_BOOLEAN = 'bool';
    public const TYPE_INT = 'int';
    public const TYPE_FLOAT = 'float';
    public const TYPE_REF_ID = 'ref_id';

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
            case self::TYPE_TEXT:
                $this->value = (string) $value;
                break;
            case self::TYPE_BOOLEAN:
                $this->value = (bool) $value;
                break;
            case self::TYPE_INT:
                $this->value = (int) $value;
                break;
            case self::TYPE_FLOAT:
                $this->value = (float) $value;
                break;
            case self::TYPE_REF_ID:
                $this->value = (int) $value;
                break;
        }
    }

    /**
     * Get a form item for setting the parameter
     */
    public function getFormItem(): ilFormPropertyGUI
    {
        $title = $this->title;
        $description = $this->description;
        $postvar = $this->name;

        switch($this->type) {
            case self::TYPE_HEAD:
                $item = new ilFormSectionHeaderGUI();
                $item->setTitle($title);
                break;
            case self::TYPE_TEXT:
                $item = new ilTextInputGUI($title, $postvar);
                $item->setValue($this->value);
                break;
            case self::TYPE_INT:
                $item = new ilNumberInputGUI($title, $postvar);
                $item->allowDecimals(false);
                $item->setSize(10);
                $item->setValue($this->value);
                break;
            case self::TYPE_BOOLEAN:
                $item = new ilCheckboxInputGUI($title, $postvar);
                $item->setChecked($this->value);
                break;
            case self::TYPE_FLOAT:
                $item = new ilNumberInputGUI($title, $postvar);
                $item->allowDecimals(true);
                $item->setSize(10);
                $item->setValue($this->value);
                break;
            case self::TYPE_REF_ID:
                $item = new ilNumberInputGUI($title, $postvar);
                $item->allowDecimals(false);
                $item->setSize(10);
                $item->setValue($this->value);
                break;
        }

        if (strpos($description, '-') !== 0) {
            $item->setInfo($description);
        }

        return $item;
    }
}
