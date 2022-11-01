<?php

namespace App\Http\Controllers;

use App\Models\Person;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\File;

class PersonController extends Controller
{
    public function index(): JsonResponse
    {
        $data = Person::all();
        return response()->json($data);
    }

    public function store(Request $request): JsonResponse
    {
        $response = [
            'ok' => false,
            'messages' => []
        ];
        $validate = Validator::make($request->all(), [
            'name' => 'required|string',
            'email' => 'required|email|unique:people,email',
            'age' => 'required|integer',
            'image' => 'required|image',
        ]);
        if ($validate->fails()) $response['messages'][] = $validate->errors();
        else {
            $image = $request->file('image')->store('images/person', [
                'disk' => 'public_folder'
            ]);
            $person = new Person();
            $person->name = $request->input('name');
            $person->email = $request->input('email');
            $person->age = $request->input('age');
            $person->image = $image;
            if ($person->save()) {
                $response['data'] = $person;
                $response['ok'] = true;
            }
        }
        return response()->json($response);
    }

    public function show($id): JsonResponse
    {
        $response = [
            'ok' => false,
            'messages' => [],
            'data' => []
        ];
        $validate = Validator::make(['id' => $id], [
            'id' => 'required|integer|exists:people'
        ]);
        if ($validate->fails()) $response['messages'][] = $validate->errors();
        else {
            $response['ok'] = true;
            $response['data'] = Person::find($id);
        }

        return response()->json($response);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $response = [
            'ok' => false,
            'messages' => [],
            'data' => []
        ];
        $validator = Validator::make($request->all() + ['id' => $id], [
            'id' => 'required|integer|exists:people',
            'name' => 'string',
            'email' => [
                'email',
                Rule::unique('people', 'email')->whereNot('id', $id)
            ],
            'age' => 'integer',
            'image' => 'image',
        ]);
        if ($validator->fails()) $response['messages'][] = $validator->errors();
        else {
            $person = Person::find($id);
            $current_image = $person->image;
            File::delete($current_image);
            if ($request->has('name')) $person->name = $request->input('name');
            if ($request->has('email')) $person->email = $request->input('email');
            if ($request->has('age')) $person->age = $request->input('age');
            if ($request->hasFile('image')) {
                $image = $request->file('image')->store('images/person', ['disk' => 'public_folder']);
                $person->image = $image;
            }
            if ($person->save()) {
                $response['ok'] = true;
                $response['data'] = $person;
            }

        }
        return response()->json($response);
    }

    public function destroy($id)
    {
        Person::destroy($id);
    }
}
