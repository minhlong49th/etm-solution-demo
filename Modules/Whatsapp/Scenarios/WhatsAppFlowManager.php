<?php

namespace Modules\Whatsapp\Scenarios;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

use Modules\Whatsapp\App\Models\FlowState;
use Modules\Whatsapp\Scenarios\Scenario1425041524;
use Modules\Whatsapp\Scenarios\WhatsAppConfig;

use Netflie\WhatsAppCloudApi\WhatsAppCloudApi;
use Netflie\WhatsAppCloudApi\WebHook\Notification;
use Netflie\WhatsAppCloudApi\WebHook\Notification\StatusNotification;
use Netflie\WhatsAppCloudApi\Message\ButtonReply\Button;
use Netflie\WhatsAppCloudApi\Message\ButtonReply\ButtonAction;

use Netflie\WhatsAppCloudApi\Message\OptionsList\Row;
use Netflie\WhatsAppCloudApi\Message\OptionsList\Section;
use Netflie\WhatsAppCloudApi\Message\OptionsList\Action;

class WhatsAppFlowManager {

    protected $whatsapp_cloud_api;
    protected $flowState;
    protected $sceenFlows;
    protected $scenario;


    public function __construct() {
        $whatsappConfig = new WhatsAppConfig();
        $this->whatsapp_cloud_api = $whatsappConfig->init();

        $this->scenario = new Scenario1425041524();
        $this->sceenFlows = $this->scenario->getScenario();
    }

    public function handleIncomingMessage(Notification $notification, ?String $requestContent) {
        if ($notification instanceof StatusNotification) {
            Log::info('handleIncomingMessage - StatusNotification:', [
                'userPhoneNumber' => $notification->customerId(),
                'messageId' => $notification->id(),
                'status' => $notification->status(),
            ]);

            if ($notification->isMessageSent() == true) {
                $userPhoneNumber = $notification->customerId();

                $this->getDataForFlowState($userPhoneNumber);
                if (!$this->flowState || count($this->flowState) == 0) return;

                $lastAction = $this->flowState[array_key_last($this->flowState)];

                if ($lastAction['messageId'] != $notification->id()) return;

                $nextAction = $this->actionNexStep($userPhoneNumber, $lastAction, $notification);

                if ($nextAction && $nextAction['action']['type'] == 'finish') {
                    $this->determineNextStepForFinish($userPhoneNumber, $nextAction, $notification, $requestContent);
                }
            }

        } else {
            $userPhoneNumber = $notification->customer()->phoneNumber();
            $this->getDataForFlowState($userPhoneNumber);

            $this->initFlowStateData($userPhoneNumber, $notification, $requestContent);

            $lastAction = $this->getUserFlowState($userPhoneNumber, $notification, $requestContent);
            Log::info("Last Action: ", $lastAction);

            $nextAction = $this->actionNexStep($userPhoneNumber, $lastAction, $notification);
            if ($nextAction) {
                Log::info("Next Action: ", $nextAction);

                $this->determineNextStep($userPhoneNumber, $nextAction, $notification, $requestContent);
            }

        }

    }

    private function getDataForFlowState(String $userPhoneNumber) {
        if (!empty($userPhoneNumber)) {
            $flowData = FlowState::where('user_phone', $userPhoneNumber)->first();

            if ($flowData && !empty($flowData->flows)) {
                $this->flowState = json_decode($flowData->flows, true);
            } else {
                $this->flowState = [];
            }

        } else {
            $this->flowState = [];
        }

        Log::info("Flow State Data From DB: ", $this->flowState);

    }

    private function initFlowStateData(String $userPhoneNumber, Notification $notification, ?String $requestContent) {
        if(!$this->flowState) {
            Log::info("Add Fist Screen If Use is not exist");
            $firstStep = $this->sceenFlows['screens'][0];
            $firstStep['messageId'] = $notification->id();
            $firstStep['timestamp'] = Carbon::now()->timestamp;

            Log::info("User Phone Number: ", [$userPhoneNumber]);

            $this->flowState = array($firstStep);
            $this->updateFlowStateToDB($this->flowState, $userPhoneNumber, array($requestContent));
        }

        Log::info("Flow State: ", $this->flowState);
    }

    private function updateFlowStateToDB(Array $flow, String $phoneNumber, ?Array $requestContent) {
        $flowState = FlowState::updateOrCreate([
            'user_phone' => $phoneNumber,
        ], [
            'flows' => json_encode($flow),
            'messages'=> json_encode($requestContent)
        ]);


        if ($flowState->wasRecentlyCreated) {
            Log::info("updateFlowStateToDB: New record was created");
        } else if ($flowState->wasChanged()) {
            Log::info("updateFlowStateToDB: Existing record was updated");
        } else {
            Log::info("updateFlowStateToDB: Record already existed");
        }
    }

    private function actionNexStepForStatus($userPhoneNumber, $action) {
        $actionData = $action['action'];

        if($actionData['type'] == 'navigate'){
            $nextAction = collect($this->sceenFlows['screens'])->first(function ($value, $key) use ($actionData) {
                return $value['id'] == $actionData['screen_id'];
            });

            return $nextAction;
        }

        if (!isset($actionData['layout']['variables'])) return null;

        $variables = $actionData['layout']['variables'];
        if (in_array("components", $variables)){
            $nextAction = collect($this->sceenFlows['screens'])->first(function ($value, $key) use ($actionData) {
                return $value['id'] == $actionData['components'][0]['action']['screen_id'];
            });

            return $nextAction;
        }

        return null;
    }

    private function actionNexStepForText($userPhoneNumber, $action, $notification) {
        $actionData = $action['action'];

        Log::info('handleIncomingMessage - Defaul/Text Message:', [
            'userPhoneNumber' => $userPhoneNumber,
            'text' => $notification->message(),
        ]);

        if($actionData['type'] == 'navigate'){
            $nextAction = collect($this->sceenFlows['screens'])->first(function ($value, $key) use ($actionData) {
                return $value['id'] == $actionData['screen_id'];
            });

            return $nextAction;
        }

        return null;
    }

    private function actionNexStepForMedia($userPhoneNumber, $action, $notification) {
        Log::info("Media - id: ".$notification->imageId());
        if ($notification->imageId()) {
            $extension = explode('/', $notification->mimeType())[1];
            $filename = 'whatsapp_image_'.$notification->imageId().'.'.$extension;

            return [
                'id' => 'download_file',
                'action' => [
                    'type' => 'download',
                    'image_id' => $notification->imageId(),
                    'mime_type' => $notification->mimeType(),
                    'file_name' => $filename,
                ]
            ];
        }

        return null;
    }

    private function actionNexStepForButton($userPhoneNumber, $action, $notification) {
        $actionData = $action['action'];

        if(isset($actionData['components'])) {
            $component = collect($actionData['components'])->first(function ($value, $key) use ($notification) {
                return $value['text'] == $notification->payload();
            });

            Log::info("Button - component", $component);

            if ($component) {
                $nextAction = collect($this->sceenFlows['screens'])->first(function ($value, $key) use ($component) {
                    return $value['id'] == $component['action']['screen_id'];
                });

                $nextAction['reply_value'] = $notification->payload();
                return $nextAction;
            }
        }

        return null;
    }

    private function actionNexStepForInteractive($userPhoneNumber, $action, $notification) {
        $actionData = $action['action'];

        if(isset($actionData['components'])) {
            $component = collect($actionData['components'])->first(function ($value, $key) use ($notification) {
                return ($value['text'] == $notification->itemId() || $value['text'] == $notification->title());
            });

            Log::info("Interactive - component", $component);

            if ($component) {
                $nextAction = collect($this->sceenFlows['screens'])->first(function ($value, $key) use ($component) {
                    return $value['id'] == $component['action']['screen_id'];
                });

                $nextAction['reply_value'] = $notification->title();
                return $nextAction;
            }
        }

        return null;
    }

    private function actionNexStep($userPhoneNumber, $action, $notification) {
        $classNames = explode("\\",get_class($notification));
        $class = $classNames[array_key_last($classNames)];
        Log::info("instanceof notification: ".$class);

        if (!isset($action['action'])) return null;

        $actionData = $action['action'];

        switch ($class) {
            case 'Text':
                return $this->actionNexStepForText($userPhoneNumber, $action, $notification);
            case 'Media':
                return $this->actionNexStepForMedia($userPhoneNumber, $action, $notification);
            case 'Button':
                return $this->actionNexStepForButton($userPhoneNumber, $action, $notification);
            case 'Interactive':
                return $this->actionNexStepForInteractive($userPhoneNumber, $action, $notification);
            case 'StatusNotification':
                return $this->actionNexStepForStatus($userPhoneNumber, $action);
            // case 'Reaction':
            //     break;
            // case 'Location':
            //     break;
            // case 'Contact':
            //     break;
            // case 'Order':
            //     break;
            // case 'System':
            //     break;
            default:
                return null;
        }
    }

    protected function getUserFlowState($userPhoneNumber, Notification $notification, $requestContent)
    {
        // Retrieve the user's current flow state from the database or cache
        $lastestAction = $this->flowState[array_key_last($this->flowState)];
        Log::info("lastest Action Timestamp: ".$lastestAction['timestamp']);
        if ($lastestAction['timestamp']) {
            $currentTime = Carbon::now();
            $targetDatetime = Carbon::parse($lastestAction['timestamp']);
            // $targetDatetime->addHours(2);
            $targetDatetime->addMinutes(1);

            if ($currentTime->greaterThan($targetDatetime)) {
                $deleted = FlowState::where('user_phone', $userPhoneNumber)->delete();
                if ($deleted) {
                    Log::info("Deleted the flow state of ". $userPhoneNumber);
                }

                $this->flowState = null;
                $this->initFlowStateData($userPhoneNumber, $notification, $requestContent);

                return $this->flowState[array_key_last($this->flowState)];
            }
        }

        return $lastestAction;
    }

    private function determineNextStepForSendTemplate($userPhoneNumber, $action, $notification, $requestContent) {
        $actionData = $action['action'];

        Log::info("Send Upload document template");
        $response = $this->whatsapp_cloud_api->sendTemplate($userPhoneNumber, $actionData['layout']['templateId'],'en_US');

        Log::info("send_template - response: ", $response->decodedBody());
        $action['messageId'] = $response->decodedBody()['messages'][0]['id'];
        $action['timestamp'] = Carbon::now()->timestamp;
        $this->flowState[] = $action;

        $this->updateFlowStateToDB($this->flowState, $userPhoneNumber, array($requestContent));
    }

    private function determineNextStepForFinish($userPhoneNumber, $action, $notification, $requestContent) {
        $deleted = FlowState::where('user_phone', $userPhoneNumber)->delete();
        if ($deleted) {
            Log::info("Deleted the flow state of ". $userPhoneNumber);
        }
    }

    private function determineNextStepForWaiting($userPhoneNumber, $action, $notification, $requestContent) {
        $action['messageId'] = $notification->id();
        $action['timestamp'] = Carbon::now()->timestamp;
        $this->flowState[] = $action;

        $this->updateFlowStateToDB($this->flowState, $userPhoneNumber, array($requestContent));
    }

    private function determineNextStepForDownload($userPhoneNumber, $action, $notification, $requestContent) {
        $actionData = $action['action'];

        if (isset($actionData['image_id'])) {
            $response = $this->whatsapp_cloud_api->downloadMedia($notification->imageId());

            $filename = $actionData['file_name'];
            Log::info("Media - file name: ".$filename);

            $today = Carbon::now();
            $path = $notification->customer()->name()."/".$today->year."/".$today->month;
            if (Storage::disk('public')->makeDirectory($path)) {
                Storage::disk('public')->put($path.'/'.$filename, $response->body());
            }

            $action['messageId'] = $notification->id();
            $action['timestamp'] = Carbon::now()->timestamp;
            $this->flowState[] = $action;

            $this->updateFlowStateToDB($this->flowState, $userPhoneNumber, array($requestContent));
        }
    }

    private function determineNextStepForButton($userPhoneNumber, $action, $notification, $requestContent) {
        $actionData = $action['action'];
        $text = $actionData['layout']['text'];

        $components = $actionData['components'];
        $rows = [];
        foreach ($components as $key => $value) {
            $rows[] = new Button($value['text'], $value['text']);
        }
        $buttonAction = new ButtonAction($rows);

        $response = $this->whatsapp_cloud_api->sendButton($userPhoneNumber, $text, $buttonAction);

        $action['messageId'] = $response->decodedBody()['messages'][0]['id'];
        $action['timestamp'] = Carbon::now()->timestamp;
        $this->flowState[] = $action;
        $this->updateFlowStateToDB($this->flowState, $userPhoneNumber, array($requestContent));
    }

    private function determineNextStepForOptionList($userPhoneNumber, $action, $notification, $requestContent) {
        $actionData = $action['action'];
        $text = $actionData['layout']['text'];
        $rows = [];

        if ($actionData['layout']['variable'] == 'components') {
            $components = $actionData['components'];
            foreach ($components as $key => $value) {
                $rows[] = new Row($key, $value['text']);
            }

            $waSections = [new Section('', $rows)];
            $waAction = new Action($actionData['layout']['header'], $waSections);

            $response = $this->whatsapp_cloud_api->sendList(
                $userPhoneNumber,
                '',
                $text,
                $actionData['layout']['footer'],
                $waAction
            );

            $action['messageId'] = $response->decodedBody()['messages'][0]['id'];
            $action['timestamp'] = Carbon::now()->timestamp;
            $this->flowState[] = $action;
            $this->updateFlowStateToDB($this->flowState, $userPhoneNumber, array($requestContent));

        }
    }

    private function determineNextStepForSendMessage($userPhoneNumber, $action, $notification, $requestContent) {
        $actionData = $action['action'];

        if (isset($actionData['layout']['type'])) {
            $messageType = $actionData['layout']['type'];

            Log::info("determineNextStepForSendMessage - Type: ".$messageType);
            switch ($messageType) {
                case 'button':
                        $this->determineNextStepForButton($userPhoneNumber, $action, $notification, $requestContent);
                    break;
                case 'option_list':
                        $this->determineNextStepForOptionList($userPhoneNumber, $action, $notification, $requestContent);
                    break;
                default:
                        $newAction = $this->scenario->determineNextStepForSendMessage($userPhoneNumber, $action, $notification, $requestContent);
                        if ($newAction) {
                            $this->flowState[] = $newAction;
                            $this->updateFlowStateToDB($this->flowState, $userPhoneNumber, array($requestContent));
                        }
                    break;
            }
        }
    }

    protected function determineNextStep($userPhoneNumber, $action, $notification, $requestContent)
    {
        // Logic to determine the next step based on the current flow state and incoming message
        // This is where you would implement your flow logic

        if (isset($action['action'])) {
            $actionData = $action['action'];
            $type = $actionData['type'];
            Log::info("determineNextStep - Type: ". $type);

            switch($type) {
                case 'send_template':
                        $this->determineNextStepForSendTemplate($userPhoneNumber, $action, $notification, $requestContent);
                    break;
                case 'finish':
                        $this->determineNextStepForFinish($userPhoneNumber, $action, $notification, $requestContent);
                    break;
                case 'waiting':
                        $this->determineNextStepForWaiting($userPhoneNumber, $action, $notification, $requestContent);
                    break;
                case 'download':
                        $this->determineNextStepForDownload($userPhoneNumber, $action, $notification, $requestContent);
                    break;
                case 'send_message':
                        $this->determineNextStepForSendMessage($userPhoneNumber, $action, $notification, $requestContent);
                    break;
                default:
                        $newAction = $this->scenario->determineNextStep($userPhoneNumber, $action, $notification, $requestContent);
                        if ($newAction) {
                            $this->flowState[] = $newAction;
                            $this->updateFlowStateToDB($this->flowState, $userPhoneNumber, array($requestContent));
                        }
                    break;

            }
        }

    }

}
