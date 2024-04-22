<?php
namespace Modules\Whatsapp\Scenarios;

use Modules\Whatsapp\Scenarios\Scenario;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

use Modules\Whatsapp\App\Models\FlowState;
use Modules\Whatsapp\Scenarios\WhatsAppConfig;

use Netflie\WhatsAppCloudApi\WhatsAppCloudApi;

class Scenario1425041524 extends Scenario {

    protected $whatsapp_cloud_api;

    public function __construct() {
        parent::__construct('scenarios/scenario.1425.041524.json');

        $whatsappConfig = new WhatsAppConfig();
        $this->whatsapp_cloud_api = $whatsappConfig->init();
    }

    protected function initDataForScenario() {
        $this->initDataForPersonalOptionList();
    }

    private function initDataForPersonalOptionList() {
        $screens = $this->scenario['screens'];

        $index = collect($screens)->search(function ($value, $key) {
            return $value['id'] == 'personal_option_list';
        });

        $this->scenario['screens'][$index]['action']['layout']['variable'] = 'components';

        $supporters = ['Kaishan', 'JL', 'Minh', 'Ying'];

        foreach ($supporters as $key => $value) {
            $this->scenario['screens'][$index]['action']['components'][] = [
                "type" => "option",
                "text" => $value,
                "action" => [
                    "type" => "navigate",
                    "screen_id" => "send_notification_to_support"
                ]
            ];
        }
    }

    public function determineNextStep($userPhoneNumber, $action, $notification, $requestContent) {}

    public function determineNextStepForSendMessage($userPhoneNumber, $action, $notification, $requestContent) {
        $actionData = $action['action'];
        $messageType = $actionData['layout']['type'];
        $text = $actionData['layout']['text'];

        switch($messageType) {
            case 'text_variable':
                    $suporter = "";

                    if (isset($action['reply_value'])) {
                        $suporter = $action['reply_value'];
                        $text = str_replace($actionData['layout']['variables'][0], $suporter, $text);
                    }
                    $response = $this->whatsapp_cloud_api->sendTextMessage($userPhoneNumber, $text);

                    $action['messageId'] = $response->decodedBody()['messages'][0]['id'];
                    $action['timestamp'] = Carbon::now()->timestamp;

                    return $action;
            default:
                return null;
        }
    }
}
