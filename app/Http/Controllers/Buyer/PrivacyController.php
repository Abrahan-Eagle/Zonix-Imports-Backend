<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PrivacyController extends Controller
{
    /**
     * Obtener configuración actual de privacidad
     */
    public function getPrivacySettings()
    {
        try {
            $user = Auth::user();

            // En producción, esto se consultaría de la base de datos
            $settings = $this->getMockPrivacySettings($user->id);

            return response()->json([
                'success' => true,
                'data' => $settings
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener configuración',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar configuración de privacidad
     */
    public function updatePrivacySettings(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'profile_visibility' => 'boolean',
                'order_history_visibility' => 'boolean',
                'activity_visibility' => 'boolean',
                'marketing_emails' => 'boolean',
                'push_notifications' => 'boolean',
                'location_sharing' => 'boolean',
                'data_analytics' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de entrada inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = Auth::user();
            $updates = $request->only([
                'profile_visibility',
                'order_history_visibility',
                'activity_visibility',
                'marketing_emails',
                'push_notifications',
                'location_sharing',
                'data_analytics',
            ]);

            // En producción, esto se guardaría en la base de datos
            $updatedSettings = $this->updateMockPrivacySettings($user->id, $updates);

            return response()->json([
                'success' => true,
                'message' => 'Configuración actualizada correctamente',
                'data' => $updatedSettings
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar configuración',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener política de privacidad
     */
    public function getPrivacyPolicy()
    {
        try {
            $policy = [
                'version' => '1.0',
                'last_updated' => '2024-01-01',
                'content' => $this->getPrivacyPolicyContent(),
            ];

            return response()->json([
                'success' => true,
                'data' => $policy
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener política',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener términos de servicio
     */
    public function getTermsOfService()
    {
        try {
            $terms = [
                'version' => '1.0',
                'last_updated' => '2024-01-01',
                'content' => $this->getTermsOfServiceContent(),
            ];

            return response()->json([
                'success' => true,
                'data' => $terms
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener términos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener configuración mock de privacidad
     */
    private function getMockPrivacySettings($userId)
    {
        return [
            'user_id' => $userId,
            'profile_visibility' => true,
            'order_history_visibility' => false,
            'activity_visibility' => true,
            'marketing_emails' => true,
            'push_notifications' => true,
            'location_sharing' => false,
            'data_analytics' => true,
            'created_at' => now()->subDays(30)->toISOString(),
            'updated_at' => now()->toISOString(),
        ];
    }

    /**
     * Actualizar configuración mock de privacidad
     */
    private function updateMockPrivacySettings($userId, $updates)
    {
        $currentSettings = $this->getMockPrivacySettings($userId);
        
        foreach ($updates as $key => $value) {
            if (array_key_exists($key, $currentSettings)) {
                $currentSettings[$key] = $value;
            }
        }
        
        $currentSettings['updated_at'] = now()->toISOString();
        
        return $currentSettings;
    }

    /**
     * Contenido de la política de privacidad
     */
    private function getPrivacyPolicyContent()
    {
        return "
        <h1>Política de Privacidad</h1>
        
        <h2>1. Información que recopilamos</h2>
        <p>Recopilamos información que nos proporcionas directamente, como cuando creas una cuenta, realizas un pedido o te pones en contacto con nosotros.</p>
        
        <h2>2. Cómo utilizamos tu información</h2>
        <p>Utilizamos la información que recopilamos para:</p>
        <ul>
            <li>Procesar y gestionar tus pedidos</li>
            <li>Comunicarnos contigo sobre tu cuenta y pedidos</li>
            <li>Enviarte información sobre productos y servicios</li>
            <li>Mejorar nuestros servicios</li>
        </ul>
        
        <h2>3. Cómo compartimos tu información</h2>
        <p>No vendemos, alquilamos ni compartimos tu información personal con terceros, excepto en las siguientes circunstancias:</p>
        <ul>
            <li>Con tu consentimiento explícito</li>
            <li>Para cumplir con obligaciones legales</li>
            <li>Para proteger nuestros derechos y seguridad</li>
        </ul>
        
        <h2>4. Tus derechos</h2>
        <p>Tienes derecho a:</p>
        <ul>
            <li>Acceder a tu información personal</li>
            <li>Corregir información inexacta</li>
            <li>Solicitar la eliminación de tus datos</li>
            <li>Oponerte al procesamiento de tus datos</li>
        </ul>
        
        <h2>5. Seguridad</h2>
        <p>Implementamos medidas de seguridad técnicas y organizativas apropiadas para proteger tu información personal.</p>
        
        <h2>6. Contacto</h2>
        <p>Si tienes preguntas sobre esta política de privacidad, contáctanos en privacy@zonix-eats.com</p>
        ";
    }

    /**
     * Contenido de los términos de servicio
     */
    private function getTermsOfServiceContent()
    {
        return "
        <h1>Términos de Servicio</h1>
        
        <h2>1. Aceptación de los términos</h2>
        <p>Al utilizar nuestros servicios, aceptas estar sujeto a estos términos de servicio.</p>
        
        <h2>2. Descripción del servicio</h2>
        <p>Zonix Eats es una plataforma que conecta usuarios con restaurantes para la entrega de alimentos.</p>
        
        <h2>3. Cuenta de usuario</h2>
        <p>Eres responsable de mantener la confidencialidad de tu cuenta y contraseña.</p>
        
        <h2>4. Uso aceptable</h2>
        <p>Te comprometes a usar nuestros servicios solo para fines legales y de acuerdo con estos términos.</p>
        
        <h2>5. Pedidos y pagos</h2>
        <p>Los precios mostrados incluyen todos los impuestos aplicables. Los pagos se procesan de forma segura.</p>
        
        <h2>6. Entrega</h2>
        <p>Nos esforzamos por entregar tus pedidos en el tiempo estimado, pero no garantizamos tiempos específicos.</p>
        
        <h2>7. Cancelaciones y reembolsos</h2>
        <p>Las políticas de cancelación y reembolso varían según el restaurante y las circunstancias.</p>
        
        <h2>8. Limitación de responsabilidad</h2>
        <p>Nuestra responsabilidad está limitada al monto pagado por el servicio.</p>
        
        <h2>9. Modificaciones</h2>
        <p>Nos reservamos el derecho de modificar estos términos en cualquier momento.</p>
        
        <h2>10. Contacto</h2>
        <p>Para preguntas sobre estos términos, contáctanos en legal@zonix-eats.com</p>
        ";
    }
}
