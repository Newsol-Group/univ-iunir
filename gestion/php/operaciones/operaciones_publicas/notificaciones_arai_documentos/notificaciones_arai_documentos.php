<?php

use Ramsey\Uuid\Uuid;

// La petición debe ser POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // https://www.geeksforgeeks.org/how-to-receive-json-post-with-php/
    // Takes raw data from the request
    $json = file_get_contents('php://input');

    // Converts it into a PHP object
    $data = json_decode($json, true);

    toba::logger()->info('Datos JSON:');
    toba::logger()->var_dump($data);

    // Si en el POST viene la 'notificacion' y es un UUID valido
    if (isset($data['notificacion']) && Uuid::isValid($data['notificacion'])) {

        try {

            toba::db()->abrir_transaccion();

            $notificacion = $data['notificacion'];
            
            toba::logger()->info("Notificación: {$notificacion}");

            $datos_notificacion = arai_documentos::getNotificacion($notificacion);

            $id_documento_digital = $datos_notificacion['uid_documento'];

            // Recupero los movimientos del documento
            $movimientos_documento = arai_documentos::getMovimientosDocumento($id_documento_digital);

            // Si el documento fue autorizado
            $autorizado = arai_documentos::esDocumentoAutorizadoByMovimientos($movimientos_documento);

            // Tomo el resultado del último evento
            $estado_codigo = arai_documentos::getResultadoUltimoEvento($movimientos_documento);

            // Tomo el modelo de autorización del documento
            $modelo = arai_documentos::getModeloAutorizacionByMovimientos($movimientos_documento);

            // Actualizo el estado del documento
            guarani::act('act_arai_documentos')->actualizar_estado_documento($id_documento_digital, $autorizado, $estado_codigo, $modelo, $notificacion);

            // Tomo las autorizaciones
            $autorizaciones = arai_documentos::getAutorizacionesByMovimientos($movimientos_documento);
            
            // Si el documento fue rechazado por el origen/originante ('info_rechazo' viene en null)
            if ($datos_notificacion['estado_codigo'] == arai_documentos::FALLO_PROCESO_AUTORIZACION && is_null($datos_notificacion['info_rechazo'])) {
                $origen = $datos_notificacion['referencia_interna']['parametros']['origen'] ?? '';
                foreach ($autorizaciones as $key => $autorizacion) {
                    // Si es el origen/originante lo marco como 'Rechazada'
                    if ($autorizacion['usuario_sso'] == $origen) {
                        $autorizaciones[$key]['estado'] = arai_documentos::ESTADO_SOLICITUD_AUTORIZACION_RECHAZADA_DESC;
                        break;
                    }
                }
            }
            
            // Registro las autorizaciones del documento
            if (!empty($autorizaciones)) {
                guarani::act('act_arai_documentos')->eliminar_autorizaciones_documento($id_documento_digital);
                guarani::act('act_arai_documentos')->agregar_autorizaciones_documento($id_documento_digital, $autorizaciones);
            }

            toba::db()->cerrar_transaccion();

            // OK
            http_response_code(200);

        } catch (\Exception $e) {

            toba::db()->abortar_transaccion();
            toba::logger()->error('Status Code 500 - Internal Server Error');
            toba::logger()->error($e->getMessage());
            // Internal Server Error
            http_response_code(500);
            
        }

    // Si en el POST NO viene la 'notificacion'
    } else {

        toba::logger()->error('Status Code 400 - Bad Request');
        // Bad Request
        http_response_code(400);

    }

// Si la petición NO es POST
} else {

    toba::logger()->error('Status Code 405 - Method Not Allowed');
    // Method Not Allowed
    http_response_code(405);
    
}