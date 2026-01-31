<?php
namespace App\Http\Controllers;

use App\Models\WhatsappSetting;
use Illuminate\Http\Request;
use Validator;

class WhatsappSettingController extends Controller
{
    public function show()
    {
        $data = WhatsappSetting::first();

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    /**
     * SAVE (create or update)
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'api_key' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors()->first(),
            ], 422);
        }

        $setting = WhatsappSetting::updateOrCreate(
            ['id' => 1], // 👈 single-row enforced
            ['api_key' => $request->api_key]
        );

        return response()->json([
            'success' => true,
            'message' => 'WhatsApp settings saved successfully',
            'data'    => $setting,
        ]);
    }
}
