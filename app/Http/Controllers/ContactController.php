<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactRequest;
use App\Models\ContactSubmission;
use Illuminate\Http\JsonResponse;

class ContactController extends Controller
{
    public function store(StoreContactRequest $request): JsonResponse
    {
        $data = $request->validated();
        $submission = ContactSubmission::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'message' => $data['message'],
            'ip' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
            'flags' => [
                'source' => $request->route()->uri(),
            ],
        ]);

        return response()->json(['ok' => true, 'id' => $submission->id], 201);
    }
}

