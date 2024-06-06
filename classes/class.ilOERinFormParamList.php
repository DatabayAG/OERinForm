<?php

/**
 * Base class for plugin configuration and publishing data
 */
abstract class ilOERinFormParamList
{
    /**
     * Declaration of parameters (must be defined in child classes)
     * A parameter name is used as post variable and language variable of its label
     * A Parameter name with added '_info' is used as language variable of its byline
     * @var string[][] section => name => type
     */
    protected array $param_list = [];

    protected ilDBInterface $db;
    protected ilOERinFormPlugin $plugin;

    /** @var ilOERinFormParam[] name => ilOERinFormParam  */
    protected array $params = [];


    /**
     * Constructor
     * Initialize and read the parameters
     */
    public function __construct(ilOERinFormPlugin $plugin)
    {
        global $DIC;
        $this->db = $DIC->database();
        $this->plugin = $plugin;

        foreach($this->param_list as $section => $definitions) {
            foreach ($definitions as $name => $type) {
                $this->params[$name] = ilOERinFormParam::_create(
                    $name,
                    $this->plugin->txt($name),
                    $this->plugin->txt($name . '_info'),
                    $type
                );
            }
        }
        $this->read();
    }


    /**
     * Get the array of all parameters for a section
     * @return ilOERinFormParam[]
     */
    public function getParamsBySection($section): array
    {
        $params = [];
        if (isset($this->param_list[$section]) && is_array($this->param_list[$section])) {
            foreach ($this->param_list[$section] as $name => $type) {
                if (isset($this->params[$name])) {
                    $params[$name] = $this->params[$name];
                }
            }
        }
        return $params;
    }

    /**
     * Get all parameters as an assoc array of name => value
     */
    public function getAllValues(): array
    {
        $result = [];
        foreach ($this->params as $name => $param) {
            $result[$name] = $param->value;
        }
        return $result;
    }

    /**
     * Get the value of a named parameter
     * @return  mixed
     */
    public function get(string $name)
    {
        if (!isset($this->params[$name])) {
            return null;
        } else {
            return $this->params[$name]->value;
        }
    }

    /**
     * Set the value of the named parameter
     * @param mixed $value
     */
    public function set(string $name, $value = null): void
    {
        $param = $this->params[$name] ?? null;
        if (isset($param)) {
            $param->setValue($value);
        }
    }


    /**
     * Read the parameters from the database
     */
    abstract public function read(): void;

    /**
     * Write the parameters to the database
     */
    abstract public function write(): void;
}
