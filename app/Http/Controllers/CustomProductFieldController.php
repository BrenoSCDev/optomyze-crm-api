<?php

namespace App\Http\Controllers;

use App\Models\CustomProductField;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CustomProductFieldController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = CustomProductField::query();

        return response()->json(
            $query->get()
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'field_key'         => 'required|string|max:255',
            'field_type'        => 'required|string|in:text,title,number,boolean,json,date',
            'field_value'       => 'nullable',
        ]);

        $field = CustomProductField::create([
            'company_id' => $request->user()->company_id,
            ...$validator->validated()
        ]);

        return response()->json($field, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(CustomProductField $customProductField)
    {
        return response()->json($customProductField);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CustomProductField $customProductField)
    {
        $validated = $request->validate([
            'field_key'         => 'required|string|max:255',
            'field_type'        => 'required|string|in:text,title,number,boolean,json,date',
            'field_value'       => 'nullable',
        ]);

        $customProductField->update($validated);

        return response()->json($customProductField);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CustomProductField $customProductField)
    {
        $customProductField->delete();

        return response()->json([
            'message' => 'Custom product field deleted successfully.'
        ]);
    }
}
