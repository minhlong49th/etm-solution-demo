<?php

namespace Modules\Whatsapp\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

use Modules\Whatsapp\App\Models\SysConfig;

class ConfigurationController extends Controller
{
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
    public function update(Request $request)
    {
        // Validate the secret key
        $secretKey = $request->input('secret_key');
        if ($secretKey !== '2c4b3b5c0756e56714711555d9fc16249a16e2b2') {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Update configuration data
        $key = $request->input('key');
        $value = $request->input('value');
        $description = $request->input('description');

        SysConfig::updateOrCreate(['key' => $key], [
            'value' => $value,
            'description' => $description,
        ]);

        return response()->json(['message' => 'Configuration updated successfully']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        //
    }
}
