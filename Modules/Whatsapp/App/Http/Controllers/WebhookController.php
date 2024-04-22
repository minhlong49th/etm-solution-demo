<?php

namespace Modules\Whatsapp\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

use Netflie\WhatsAppCloudApi\WebHook;
use Netflie\WhatsAppCloudApi\WhatsAppCloudApi;
use Modules\Whatsapp\Scenarios\WhatsAppFlowManager;

class WebhookController extends Controller
{
    protected $webhook;

    public function __construct(protected WhatsAppFlowManager $whatsAppFlowManager) {
        $this->webhook = new WebHook();
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('whatsapp::index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('whatsapp::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        //
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('whatsapp::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('whatsapp::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id): RedirectResponse
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        //
    }

    public function verifyWebhook(Request $request)
    {
        $whatsapToken = (new WhatsAppConfig())->getWhatsappToken();
        $challenge = $this->webhook->verify($request->query(), $whatsapToken);
        return response($challenge);
    }

    public function receiveWebhook(Request $request)
    {

        $data = json_decode($request->getContent(), true);
        Log::info("receiveWebhook:");
        Log:info($request->getContent());

        // Process the incoming data
        $notification = $this->webhook->read($data);

        $this->whatsAppFlowManager->handleIncomingMessage($notification, $request->getContent());
    }

}
