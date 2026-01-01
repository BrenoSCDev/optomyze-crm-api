<?php

namespace App\Http\Controllers;

use App\Models\CustomProductField;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function panel()
    {
        $customProductFields = CustomProductField::all();

        return response()->json([
            "customProductFields" => $customProductFields
        ]);
    }
}
