<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Controllers\Controller;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use App\Models\Branding;

class BrandingController extends Controller
{
    public function store(Request $request)
    {
        /* ---------- VALIDATION ---------- */
        $validator = Validator::make($request->all(), [
            'brand_name' => 'nullable|string|max:255',
            'logo'       => 'nullable|image|mimes:png,jpg,jpeg,svg|max:2048',
            'favicon'    => 'nullable|mimes:png,ico,svg|max:1024',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors'  => $validator->errors(),
            ], 422);
        }

        /* ---------- SAVE BRANDING ---------- */
        $branding = Branding::first() ?? new Branding();

        if ($request->filled('brand_name')) {
            $branding->brand_name = $request->brand_name;
        }

        if ($request->hasFile('logo')) {
            if ($branding->logo) {
                Storage::disk('public')->delete($branding->logo);
            }

            $branding->logo = $request->file('logo')
                ->store('branding/logos', 'public');
        }

        if ($request->hasFile('favicon')) {
            if ($branding->favicon) {
                Storage::disk('public')->delete($branding->favicon);
            }

            $branding->favicon = $request->file('favicon')
                ->store('branding/favicons', 'public');
        }

        $branding->save();

        return response()->json([
            'message' => 'Branding saved successfully',
            'data' => [
                'brand_name' => $branding->brand_name,
                'logo' => $branding->logo ? asset('storage/' . $branding->logo) : null,
                'favicon' => $branding->favicon ? asset('storage/' . $branding->favicon) : null,
            ],
        ]);
    }

    public function show()
{
    $branding = Branding::first();

    if (!$branding) {
        return response()->json([
            'message' => 'Branding not found',
            'data' => null
        ], 404);
    }

    return response()->json([
        'message' => 'Branding fetched successfully',
        'data' => [
            'brand_name' => $branding->brand_name,
            'logo' => $branding->logo
                ? asset('storage/' . $branding->logo)
                : null,
            'favicon' => $branding->favicon
                ? asset('storage/' . $branding->favicon)
                : null,
        ]
    ]);
}

}