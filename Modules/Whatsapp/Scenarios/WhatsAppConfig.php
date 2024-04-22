<?php

namespace Modules\Whatsapp\Scenarios;

use Modules\Whatsapp\App\Models\SysConfig;
use Netflie\WhatsAppCloudApi\WhatsAppCloudApi;
use Illuminate\Support\Facades\Log;

class WhatsAppConfig {

    public function init(): WhatsAppCloudApi {
        if (config('whatsapp.whatsapp-from-db')) {

            Log::info("WhatsAppCloudApi - ini - db", [
                SysConfig::where('key','WHATSAPP_FROM_PHONE_NUMBER_ID')->first()->value,
                SysConfig::where('key','WHATSAPP_ACCESS_TOKEN')->first()->value
            ]);

            return new WhatsAppCloudApi([
                'from_phone_number_id' => SysConfig::where('key','WHATSAPP_FROM_PHONE_NUMBER_ID')->first()->value,
                'access_token' => SysConfig::where('key','WHATSAPP_ACCESS_TOKEN')->first()->value,
                'graph_version' => 'v19.0'
            ]);
        } else {
            Log::info("WhatsAppCloudApi - ini - evn", [
                config('whatsapp.whatsapp-phone-number'),
                config('whatsapp.whatsapp-access-token')
            ]);

            return new WhatsAppCloudApi([
                'from_phone_number_id' => config('whatsapp.whatsapp-phone-number'),
                'access_token' => config('whatsapp.whatsapp-access-token'),
                'graph_version' => 'v19.0',
            ]);
        }
    }

    public function getWhatsappToken(): string {
        if (config('whatsapp.whatsapp-from-db')) {
            return SysConfig::where('key','WHATSAPP_TOKEN')->first()->value;
        } else return config('whatsapp.whatsapp-token');
    }
}
