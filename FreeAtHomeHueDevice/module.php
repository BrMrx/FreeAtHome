<?php

class FreeAtHomeHueDevice extends IPSModule {

    public function Create() {
        parent::Create();
        $this->RegisterPropertyString("DeviceID", "");
        $this->RegisterPropertyString("Host", "");
        $this->RegisterPropertyString("Username", "");
        $this->RegisterPropertyString("Password", "");
        $this->RegisterVariableBoolean("STATE", "Hue Light", "~Switch", 1);
        $this->EnableAction("STATE");
    }

    public function RequestAction($Ident, $Value) {
        if ($Ident === "STATE") {
            $this->SetValue("STATE", $Value);
            $this->ControlHue($Value);
        }
    }

    private function ControlHue($value) {
        $this->SendDebug("Hue", "Hue light changed: " . var_export($value, true), 0);
    }
}
