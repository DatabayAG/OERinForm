<?php

/**
 * User interface hook class
 * Adds the OER functions to the export tab of an object
 */
class ilOERinFormUIHookGUI extends ilUIHookPluginGUI
{
    protected ilCtrl $ctrl;

    public function __construct()
    {
        global $DIC;
        $this->ctrl = $DIC->ctrl();
    }

    /**
     * Modify GUI objects, before they generate ouput
     *
     * @param string $a_comp component
     * @param string $a_part string that identifies the part of the UI that is handled
     * @param array $a_par array of parameters (depend on $a_comp and $a_part)
     */
    public function modifyGUI(
        string $a_comp,
        string $a_part,
        array $a_par = []
    ): void {

        if ($a_part === 'tabs') {

            $class = $this->ctrl->getCmdClass();
            $cmd = $this->ctrl->getCmd();

            if ($class === strtolower(ilExportGUI::class)
                || $class === strtolower(ilTestExportGUI::class)
                || $class === strtolower(ilQuestionPoolExportGUI::class)
                || ($class === strtolower(ilObjSCORM2004LearningModuleGUI::class) && $cmd === 'listExportFiles')) {
                $gui = new ilOERinFormPublishGUI();
                $gui->addPublishInfo();
            }
        }
    }
}
