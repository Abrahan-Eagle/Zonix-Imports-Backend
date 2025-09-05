<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ExportController extends Controller
{
    /**
     * Solicitar exportación de datos personales
     */
    public function requestDataExport(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'data_types' => 'array',
                'data_types.*' => 'string|in:profile,orders,activity,reviews,addresses,notifications',
                'format' => 'string|in:json,csv,pdf',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de entrada inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = Auth::user();
            $dataTypes = $request->get('data_types', ['profile', 'orders', 'activity']);
            $format = $request->get('format', 'json');

            // Generar ID único para la exportación
            $exportId = Str::uuid()->toString();

            // Simular procesamiento de exportación
            $exportData = $this->generateExportData($user, $dataTypes, $format);

            // Guardar archivo temporalmente
            $filename = "export_{$user->id}_{$exportId}.{$format}";
            $filePath = "exports/{$filename}";
            
            Storage::put($filePath, $exportData);

            // En producción, esto se guardaría en la base de datos
            $exportRecord = [
                'id' => $exportId,
                'user_id' => $user->id,
                'data_types' => $dataTypes,
                'format' => $format,
                'file_path' => $filePath,
                'file_size' => Storage::size($filePath),
                'status' => 'completed',
                'created_at' => now()->toISOString(),
                'completed_at' => now()->toISOString(),
            ];

            return response()->json([
                'success' => true,
                'message' => 'Exportación solicitada correctamente',
                'data' => $exportRecord
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al solicitar exportación',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verificar estado de la exportación
     */
    public function getExportStatus($exportId)
    {
        try {
            $user = Auth::user();

            // En producción, esto se consultaría de la base de datos
            $exportRecord = $this->getMockExportRecord($exportId, $user->id);

            if (!$exportRecord) {
                return response()->json([
                    'success' => false,
                    'message' => 'Exportación no encontrada'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $exportRecord
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al verificar estado',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Descargar archivo exportado
     */
    public function downloadExport($exportId)
    {
        try {
            $user = Auth::user();

            // En producción, esto se consultaría de la base de datos
            $exportRecord = $this->getMockExportRecord($exportId, $user->id);

            if (!$exportRecord) {
                return response()->json([
                    'success' => false,
                    'message' => 'Exportación no encontrada'
                ], 404);
            }

            if ($exportRecord['status'] !== 'completed') {
                return response()->json([
                    'success' => false,
                    'message' => 'La exportación aún no está lista'
                ], 400);
            }

            // Simular descarga del archivo
            $fileContent = $this->generateExportData($user, $exportRecord['data_types'], $exportRecord['format']);

            return response($fileContent)
                ->header('Content-Type', $this->getContentType($exportRecord['format']))
                ->header('Content-Disposition', "attachment; filename=export_{$exportId}.{$exportRecord['format']}");

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al descargar archivo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener historial de exportaciones
     */
    public function getExportHistory()
    {
        try {
            $user = Auth::user();

            // En producción, esto se consultaría de la base de datos
            $exportHistory = $this->generateMockExportHistory($user->id);

            return response()->json([
                'success' => true,
                'data' => $exportHistory
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener historial',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generar datos de exportación
     */
    private function generateExportData($user, $dataTypes, $format)
    {
        $data = [];

        foreach ($dataTypes as $type) {
            switch ($type) {
                case 'profile':
                    $data['profile'] = $this->getProfileData($user);
                    break;
                case 'orders':
                    $data['orders'] = $this->getOrdersData($user);
                    break;
                case 'activity':
                    $data['activity'] = $this->getActivityData($user);
                    break;
                case 'reviews':
                    $data['reviews'] = $this->getReviewsData($user);
                    break;
                case 'addresses':
                    $data['addresses'] = $this->getAddressesData($user);
                    break;
                case 'notifications':
                    $data['notifications'] = $this->getNotificationsData($user);
                    break;
            }
        }

        switch ($format) {
            case 'json':
                return json_encode($data, JSON_PRETTY_PRINT);
            case 'csv':
                return $this->convertToCsv($data);
            case 'pdf':
                return $this->convertToPdf($data);
            default:
                return json_encode($data);
        }
    }

    /**
     * Obtener datos del perfil
     */
    private function getProfileData($user)
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ];
    }

    /**
     * Obtener datos de pedidos
     */
    private function getOrdersData($user)
    {
        // Simular datos de pedidos
        return [
            [
                'id' => 1,
                'order_number' => 'ORD-001',
                'total' => 25.50,
                'status' => 'completed',
                'created_at' => now()->subDays(1)->toISOString(),
            ],
            [
                'id' => 2,
                'order_number' => 'ORD-002',
                'total' => 15.75,
                'status' => 'cancelled',
                'created_at' => now()->subDays(4)->toISOString(),
            ],
        ];
    }

    /**
     * Obtener datos de actividad
     */
    private function getActivityData($user)
    {
        return [
            [
                'id' => 1,
                'activity_type' => 'login',
                'description' => 'Inicio de sesión',
                'created_at' => now()->subHours(2)->toISOString(),
            ],
            [
                'id' => 2,
                'activity_type' => 'order_placed',
                'description' => 'Pedido realizado',
                'created_at' => now()->subDays(1)->toISOString(),
            ],
        ];
    }

    /**
     * Obtener datos de reseñas
     */
    private function getReviewsData($user)
    {
        return [
            [
                'id' => 1,
                'rating' => 5,
                'comment' => 'Excelente servicio',
                'created_at' => now()->subDays(3)->toISOString(),
            ],
        ];
    }

    /**
     * Obtener datos de direcciones
     */
    private function getAddressesData($user)
    {
        return [
            [
                'id' => 1,
                'street' => 'Calle Ejemplo 123',
                'city' => 'Madrid',
                'postal_code' => '28001',
                'is_default' => true,
            ],
        ];
    }

    /**
     * Obtener datos de notificaciones
     */
    private function getNotificationsData($user)
    {
        return [
            [
                'id' => 1,
                'title' => 'Pedido confirmado',
                'message' => 'Tu pedido ha sido confirmado',
                'read_at' => null,
                'created_at' => now()->subDays(1)->toISOString(),
            ],
        ];
    }

    /**
     * Convertir a CSV
     */
    private function convertToCsv($data)
    {
        $csv = '';
        foreach ($data as $section => $items) {
            $csv .= "# {$section}\n";
            if (is_array($items)) {
                if (!empty($items) && isset($items[0]) && is_array($items[0])) {
                    // Array de arrays (como orders, activity, etc.)
                    $headers = array_keys($items[0]);
                    $csv .= implode(',', $headers) . "\n";
                    foreach ($items as $item) {
                        $values = array_map(function($value) {
                            if (is_array($value) || is_object($value)) {
                                return json_encode($value);
                            }
                            return str_replace(',', ';', (string) $value);
                        }, array_values($item));
                        $csv .= implode(',', $values) . "\n";
                    }
                } else {
                    // Array simple (como profile)
                    $csv .= "key,value\n";
                    foreach ($items as $key => $value) {
                        $value = is_array($value) || is_object($value) ? json_encode($value) : (string) $value;
                        $csv .= "{$key}," . str_replace(',', ';', $value) . "\n";
                    }
                }
            }
            $csv .= "\n";
        }
        return $csv;
    }

    /**
     * Convertir a PDF (simulado)
     */
    private function convertToPdf($data)
    {
        // En producción, usar una librería como DomPDF
        return "PDF content for export: " . json_encode($data);
    }

    /**
     * Obtener tipo de contenido
     */
    private function getContentType($format)
    {
        switch ($format) {
            case 'json':
                return 'application/json';
            case 'csv':
                return 'text/csv';
            case 'pdf':
                return 'application/pdf';
            default:
                return 'application/octet-stream';
        }
    }

    /**
     * Obtener registro mock de exportación
     */
    private function getMockExportRecord($exportId, $userId)
    {
        $exports = [
            '123e4567-e89b-12d3-a456-426614174000' => [
                'id' => '123e4567-e89b-12d3-a456-426614174000',
                'user_id' => $userId,
                'data_types' => ['profile', 'orders'],
                'format' => 'json',
                'status' => 'completed',
                'file_size' => 1024,
                'created_at' => now()->subDays(1)->toISOString(),
                'completed_at' => now()->subDays(1)->toISOString(),
            ],
        ];

        return $exports[$exportId] ?? null;
    }

    /**
     * Generar historial mock de exportaciones
     */
    private function generateMockExportHistory($userId)
    {
        return [
            [
                'id' => '123e4567-e89b-12d3-a456-426614174000',
                'user_id' => $userId,
                'data_types' => ['profile', 'orders'],
                'format' => 'json',
                'status' => 'completed',
                'file_size' => 1024,
                'created_at' => now()->subDays(1)->toISOString(),
                'completed_at' => now()->subDays(1)->toISOString(),
            ],
            [
                'id' => '456e7890-e89b-12d3-a456-426614174001',
                'user_id' => $userId,
                'data_types' => ['activity'],
                'format' => 'csv',
                'status' => 'completed',
                'file_size' => 512,
                'created_at' => now()->subDays(3)->toISOString(),
                'completed_at' => now()->subDays(3)->toISOString(),
            ],
        ];
    }
}
