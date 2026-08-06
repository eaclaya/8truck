<?php

namespace App\Http\Controllers;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Http\Requests\StoreDocumentRequest;
use App\Models\Document;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DocumentController extends Controller
{
    public function index(Request $request): Response
    {
        $transporter = $request->user()->transporterProfile;

        abort_unless($transporter !== null, 403);

        return Inertia::render('documents/Index', [
            'documents' => $transporter->documents()->latest()->get()
                ->map(fn (Document $document) => [
                    'id' => $document->id,
                    'type' => $document->type->value,
                    'status' => $document->status->value,
                    'expires_at' => $document->expires_at?->toDateString(),
                    'notes' => $document->notes,
                    'created_at' => $document->created_at?->toDateString(),
                ]),
            'documentTypes' => array_column(DocumentType::cases(), 'value'),
        ]);
    }

    public function store(StoreDocumentRequest $request): RedirectResponse
    {
        $transporter = $request->user()->transporterProfile;

        abort_unless($transporter !== null, 403);

        $path = $request->file('file')->store('documents', 'local');

        $transporter->documents()->create([
            'type' => $request->validated()['type'],
            'path' => $path,
            'status' => DocumentStatus::Pending,
            'expires_at' => $request->validated()['expires_at'] ?? null,
        ]);

        return back();
    }

    public function download(Request $request, Document $document): BinaryFileResponse
    {
        Gate::authorize('view', $document);

        abort_unless(Storage::disk('local')->exists($document->path), 404);

        return response()->file(Storage::disk('local')->path($document->path));
    }
}
