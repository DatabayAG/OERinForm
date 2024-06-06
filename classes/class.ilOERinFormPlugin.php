<?php

/**
 * Plugin class and sevice locator / factory
 */
class ilOERinFormPlugin extends ilUserInterfaceHookPlugin
{
    protected static ilOERinFormPlugin $instance;
    protected ilOERinFormConfig $config;

    public function getPluginName(): string
    {
        return 'OERinForm';
    }

    public static function getInstance(): self
    {
        global $DIC;

        if (!isset(self::$instance)) {
            /** @var ilComponentFactory $factory */
            /** @var self $plugin */
            $factory = $DIC['component.factory'];
            $plugin = $factory->getPlugin('oerinf');
            self::$instance = $plugin;
        }
        return self::$instance;
    }

    /**
     * Check if the object type is allowed for publishing as oer
     */
    public function isAllowedType(string $type): bool
    {
        return in_array($type, ['file','lm','htlm','sahs','glo','wiki', 'tst', 'qpl']);
    }


    public function getData(int $obj_id): ilOERinFormData
    {
        return new ilOERinFormData($this, $obj_id);
    }


    public function getConfig(): ilOERinFormConfig
    {
        if (!isset($this->config)) {
            $this->config = new ilOERinFormConfig($this);
        }
        return $this->config;
    }

}
