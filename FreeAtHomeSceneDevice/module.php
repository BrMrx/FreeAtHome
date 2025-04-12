<?php

class FreeAtHomeSceneDevice extends IPSModule {

    public function Create() {
        parent::Create();
        $this->RegisterPropertyString("DeviceID", "");
        $this->RegisterPropertyString("Host", "");
        $this->RegisterPropertyString("Username", "");
        $this->RegisterPropertyString("Password", "");
        $this->RegisterVariableBoolean("Trigger", "Trigger Scene", "~Switch", 1);
        $this->EnableAction("Trigger");
    }

    public function RequestAction($Ident, $Value) {
        if ($Ident === "Trigger") {
            $this->SetValue("Trigger", $Value);
            $this->TriggerScene($Value);
        }
    }

    private function TriggerScene($value) {
        // Sende Scene-Trigger an SysAP
        $this->SendDebug("Scene", "Scene triggered: " . var_export($value, true), 0);
    }
}
