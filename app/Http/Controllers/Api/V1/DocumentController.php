<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\DocumentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDocumentRequest;
use App\Models\Document;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DocumentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $transporter = $request->user()->transporterProfile;

        abort_unless($transporter !== null, 403);

        return response()->json([
            'data' => $transporter->documents()->latest()->get()
                ->map(fn (Document $document) => [
                    'id' => $document->id,
                    'type' => $document->type->value,
                    'status' => $document->status->value,
                    'expires_at' => $document->expires_at?->toDateString(),
                    'notes' => $document->notes,
                    'created_at' => $document->created_at?->toIso8601String(),
                ]),
        ]);
    }

    public function store(StoreDocumentRequest $request): JsonResponse
    {
        $transporter = $request->user()->transporterProfile;

        abort_unless($transporter !== null, 403);

        $path = $request->file('file')->store('documents', 'local');

        $document = $transporter->documents()->create([
            'type' => $request->validated()['type'],
            'path' => $path,
            'status' => DocumentStatus::Pending,
            'expires_at' => $request->validated()['expires_at'] ?? null,
        ]);

        return response()->json(['data' => ['id' => $document->id, 'status' => $document->status->value]], 201);
    }

    public function download(Request $request, Document $document): BinaryFileResponse
    {
        Gate::authorize('view', $document);

        abort_unless(Storage::disk('local')->exists($document->path), 404);

        return response()->file(Storage::disk('local')->path($document->path));
    }
}
