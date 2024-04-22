<?php
namespace Modules\Whatsapp\Scenarios;

use Illuminate\Support\Facades\File;

abstract class Scenario {

    protected $scenario;

    public function __construct(string $path) {
        $this->scenario = json_decode(File::get(resource_path($path)), true);
        $this->initDataForScenario();
    }

    protected abstract function initDataForScenario();
    public abstract function determineNextStep($userPhoneNumber, $action, $notification, $requestContent);
    public abstract function determineNextStepForSendMessage($userPhoneNumber, $action, $notification, $requestContent);

    public function getScenario() {
        return $this->scenario;
    }

}
