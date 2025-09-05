<?php

namespace App\Http\Controllers\Profiles;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Document;
use App\Models\Profile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;


class DocumentController extends Controller
{
    public function index()
    {
        $documents = Document::with('profile')->active()->get();
        return response()->json($documents);
    }

    public function store(Request $request)
    {
        // Log::info('Datos recibidos:', $request->all());
// Datos recibidos: {"profile_id":"3","type":"ci","issued_at":"2024-12-21T00:00:00.000","expires_at":"2024-12-21T00:00:00.000","number_ci":"94646464","front_image":{"Illuminate\\Http\\UploadedFile":"/tmp/php9hrbPi"}}

        // Validar que el tipo de documento sea válido
        if (!in_array($request->type, ['ci', 'passport', 'rif', 'neighborhood_association'])) {
            return response()->json(['error' => 'Invalid document type'], 400);
        }

        $profile = Profile::where('user_id', $request->profile_id)->firstOrFail();

        // Validación para verificar si el documento ya existe
        $existingDocument = Document::where('profile_id', $profile->id)
            ->where('type', $request->type)
            ->first();

        if ($existingDocument) {
            return response()->json(['error' => 'A document of type ' . $request->type . ' already exists for this profile.'], 400);
        }

        $validator = $this->getValidator($request->all(), $request->type);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $paths = $this->handleImageUpload($request);

        // Crear el documento con valores predeterminados
        $document = Document::create(array_merge(
            $request->only([
                 'type', 'number_ci', 'RECEIPT_N', 'rif_url',
                'taxDomicile', 'issued_at', 'expires_at', 'sky', 'commune_register', 'community_rif'
            ]),
            $paths,
            [
                'profile_id' => $profile->id,
                'status' => true,
                'approved' => false,
            ]
        ));

        return response()->json(['message' => 'Document created successfully', 'document' => $document], 201);
    }

    // public function show($id)
    // {

    //     // Log::info('Valor de $id recibido en show:', ['id' => $id]);

    //     $profile = Profile::where('user_id', $id)->firstOrFail();

    //     $document = Document::with('profile')
    //         ->where('profile_id', $profile->id)
    //         // ->where('status', true)
    //         ->get();

    //     if ($document->isEmpty()) {
    //         return response()->json(['message' => 'Document not found'], 404);
    //     }

    //     return response()->json($document);
    // }


    public function show($id)
        {
            $profile = Profile::where('user_id', $id)->firstOrFail();

            $document = Document::with('profile')
                ->where('profile_id', $profile->id)
                // ->where('status', true) // Eliminar o descomentar si es necesario
                ->get();

            if ($document->isEmpty()) {
                return response()->json(['message' => 'Document not found'], 404);
            }

            // Log::info('+++++++++++++++++++++++++++++++++++ document===== :', ['document' => json_encode($document)]);

            return response()->json($document);
        }

    public function update(Request $request, $id)
    {

          // Validar que el tipo de documento sea válido
        if (!in_array($request->type, ['ci', 'passport', 'rif', 'neighborhood_association'])) {
            return response()->json(['error' => 'Invalid document type'], 400);
        }


        $document = Document::find($id);

        if (!$document) {
            return response()->json(['message' => 'Document not found'], 404);
        }

        $validator = $this->getValidator($request->all(), $request->type ?? $document->type);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $paths = $this->handleImageUpload($request, $document);

        $document->update(array_merge(
            $request->only([
                'type', 'number_ci', 'RECEIPT_N', 'rif_url', 'taxDomicile',
                'issued_at', 'expires_at', 'status', 'sky', 'commune_register', 'community_rif'
            ]),
            $paths
        ));

        return response()->json(['message' => 'Document updated successfully', 'document' => $document]);
    }

    public function destroy($id)
    {
        $document = Document::find($id);

        if (!$document) {
            return response()->json(['message' => 'Document not found'], 404);
        }

        $this->deleteImages($document);
        $document->delete();

        return response()->json(['message' => 'Document deleted successfully']);
    }

    private function getValidator(array $data, string $type)
    {
        $rules = [
            'profile_id' => 'required|exists:profiles,user_id',
            'issued_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after_or_equal:issued_at',
            'status' => 'boolean',
            'front_image' => 'nullable|image|mimes:jpg,jpeg,png',
        ];

        switch ($type) {
            case 'ci':
                $rules = array_merge($rules, [
                    'number_ci' => 'required|integer', // Cambiar 'number' por 'number_ci'
                    'front_image' => 'nullable|image|mimes:jpg,jpeg,png',
                ]);
                break;
            case 'passport':
                $rules = array_merge($rules, [
                    'number_ci' => 'required|integer',
                    'RECEIPT_N' => 'nullable|integer',
                    'front_image' => 'nullable|image|mimes:jpg,jpeg,png',
                ]);
                break;

            case 'rif':
                $rules = array_merge($rules, [
                    'sky' => 'nullable|integer',
                    'RECEIPT_N' => 'nullable|integer',
                    'rif_url' => 'nullable|string',
                    'taxDomicile' => 'nullable|string',
                    'front_image' => 'nullable|image|mimes:jpg,jpeg,png',
                ]);
                break;

            case 'neighborhood_association':
                $rules = array_merge($rules, [
                    'commune_register' => 'nullable|string',  // Agregar validación para 'commune_register'
                    'community_rif' => 'nullable|string',    // Agregar validación para 'community_rif'
                    'taxDomicile' => 'nullable|string',
                    'front_image' => 'nullable|image|mimes:jpg,jpeg,png',
                ]);
                break;
        }

        return Validator::make($data, $rules);
    }

    private function handleImageUpload(Request $request, Document $document = null)
    {
        $paths = [];

        if ($request->hasFile('front_image')) {
            if ($document && $document->front_image) {
                Storage::disk('public')->delete($document->front_image);
            }
            $paths['front_image'] = $request->file('front_image')->store('documents/front', 'public');
        }

         return $paths;
    }

    private function deleteImages(Document $document)
    {
        if ($document->front_image) {
            Storage::disk('public')->delete($document->front_image);
        }

    }
}

