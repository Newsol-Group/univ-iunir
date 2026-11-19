<?php

namespace SIU\Guarani\php\rest\v2\modelos;

use SIUToba\rest\rest;
use SIUToba\rest\lib\rest_error;
use SIUToba\rest\lib\rest_filtro_sql;
use SIUToba\rest\lib\rest_validador;
use SIU\Guarani\php\rest\v2\reaperturas_convocatorias\recurso_reaperturas_convocatorias;

class rest_reaperturas_convocatorias_nucleo
{
    /**
     * @var \act_becas
     */
    protected $modelo_act;

    /**
     * @var \co_becas
     */
    protected $modelo;

    function __construct()
    {
        $this->modelo_act = \guarani::act('act_becas');
        $this->modelo = \guarani::co('co_becas');
    }

    protected function get_modelo($nombre)
    {
        $modelos = recurso_reaperturas_convocatorias::_get_modelos();
        return $modelos[$nombre];
    }

    /**
     * @return rest_filtro_sql
     */
    function get_filtro_base(){
        $filtro = new rest_filtro_sql();
        return $filtro;
    }

    function post($data)
    {
        $this->validar_post($data);
        $this->procesar_post($data);
        rest::response()->post(['resultado' => 'Ok']);
    }

    function validar_post($data)
    {
        $modelos = recurso_reaperturas_convocatorias::_get_modelos();
        $modelo_reaperturas_convocatorias = $modelos['ReaperturasConvocatorias'];

        // Valido el modelo
        rest_validador::validar($data, $modelo_reaperturas_convocatorias);

        // Valido que id_convocatoria exista
        $id_convocatoria = $data['id_convocatoria'];
        $datos_habilitacion = \guarani::co('co_encuestas')->get_datos_habilitacion_convocatoria($id_convocatoria);

        // Si no existe la convocatoria
        if (empty($datos_habilitacion)) {
            $mensaje = "La convocatoria con el ID '{$id_convocatoria}' no existe.";
            $detalle = ["id_convocatoria" => $mensaje];
            throw new rest_error(404, $mensaje, $detalle);
        }

        // Valido que el cuil sea válido y exista
        $cuil = $data['cuil'];

        // Si el cuil es inválido
        if (!\persona::validarCUIT($cuil)) {
            $mensaje = "Error en la validación del recurso";
            $detalle = ["cuil" => "El campo 'cuil' debe ser un cuil válido. Se recibió '{$cuil}'."];
            throw new rest_error(400, $mensaje, $detalle);
        }

        // Si la persona no existe
        $persona = \guarani::co('co_personas')->get_persona_por_documento(\persona::pais_argentina, \persona::doc_CUIL, $cuil);
        if (is_null($persona)) {
            $mensaje = "El aspirante con el CUIL '{$cuil}' no existe.";
            $detalle = ["cuil" => $mensaje];
            throw new rest_error(404, $mensaje, $detalle);
        }

        // Valido que fecha_hasta sea futura
        $fecha_hasta = trim($data['fecha_hasta']);

        // Valido el parámetro 'fecha_hasta'
        if (!\guarani_fecha::validar_fecha($fecha_hasta, 'Y-m-d')) {
            $mensaje = "Error en la validación del recurso";
            $detalle = ["fecha_hasta" => "El campo 'fecha_hasta' debe ser una fecha válida. Se recibió '{$fecha_hasta}'."];
            throw new rest_error(400, $mensaje, $detalle);
        }

        // Valido que la fecha sea mayor o igual a hoy
        if (!\guarani_fecha::es_mayor_a_fecha_actual($fecha_hasta, true)) {
            $mensaje = "Error en la validación del recurso";
            $detalle = ["fecha_hasta" => "El campo 'fecha_hasta' debe ser mayor o igual a la fecha actual. Se recibió '{$fecha_hasta}'."];
            throw new rest_error(400, $mensaje, $detalle);
        }

        // Valido que observaciones no venga vacío
        $observaciones = trim($data['observaciones']);
        if (empty($observaciones)) {
            $mensaje = "Error en la validación del recurso";
            $detalle = ["observaciones" => "El campo 'observaciones' es obligatorio."];
            throw new rest_error(400, $mensaje, $detalle);
        }

        // Si no se pasa 'datos_censales'
        if (!isset($data['datos_censales'])) {
            $mensaje = "Error en la validación del recurso";
            $detalle = ["datos_censales" => "El campo 'datos_censales' es obligatorio."];
            throw new rest_error(400, $mensaje, $detalle);
        }

        // Si no se pasa 'documentos'
        if (!isset($data['documentos'])) {
            $mensaje = "Error en la validación del recurso";
            $detalle = ["documentos" => "El campo 'documentos' es obligatorio."];
            throw new rest_error(400, $mensaje, $detalle);
        }

        // Si hay 'datos_censales' los valido
        if (!empty($data['datos_censales'])) {
            $datos_censales_config = \guarani::co('co_datos_censales')->get_datos_censales_obligatorios_by_habilitacion($datos_habilitacion['habilitacion']);
            $datos_censales_config = array_column($datos_censales_config, 'campo');
            $diff_datos_censales = array_diff($data['datos_censales'], $datos_censales_config);

            // Si hay 'datos_censales' que no corresponden a la convocatoria
            if (!empty($diff_datos_censales)) {
                $mensaje = "Error en la validación del recurso";
                $opciones_invalidas = implode(', ', $diff_datos_censales);
                $opciones_validas = implode(', ', $datos_censales_config);
                $detalle = ["datos_censales" => "Uno de los 'datos_censales' no pertenece a la lista de opciones válidas. Opciones inválidas '$opciones_invalidas'. Posibles opciones: '$opciones_validas'"];
                throw new rest_error(400, $mensaje, $detalle);
            }
        }

        // Si hay 'documentos' los valido
        if (!empty($data['documentos'])) {
            $requisitos = \guarani::co('co_encuestas')->get_requisitos_habilitacion($datos_habilitacion['habilitacion']);
            $requisitos = array_column($requisitos, 'requisito');
            $diff_documentos = array_diff($data['documentos'], $requisitos);

            // Si hay 'documentos' que no corresponden a la convocatoria
            if (!empty($diff_documentos)) {
                $mensaje = "Error en la validación del recurso";
                $opciones_invalidas = implode(', ', $diff_documentos);
                $opciones_validas = implode(', ', $requisitos);
                $detalle = ["documentos" => "Uno de los 'documentos' no pertenece a la lista de opciones válidas. Opciones inválidas '$opciones_invalidas'. Posibles opciones: '$opciones_validas'"];
                throw new rest_error(400, $mensaje, $detalle);
            }
        }
    }

    function procesar_post($data)
    {

        try {
            
            \toba::db()->abrir_transaccion();

            $id_convocatoria = $data['id_convocatoria'];
            $cuil = $data['cuil'];

            // Recupero datos de una convocatoria a reabrir
            $datos_convocatoria = $this->modelo->get_datos_convocatoria_reabrir($id_convocatoria, $cuil);

            // Si no se recuperaron datos de la convocatoria
            if (empty($datos_convocatoria)) {
                $mensaje = "Ya se llevó a cabo una reapertura para el aspirante con el CUIL '{$cuil}' en la convocatoria con el ID '{$id_convocatoria}'.";
                throw new rest_error(422, $mensaje);
            }

            $respuesta = $datos_convocatoria['respuesta'];
            $observaciones = trim($data['observaciones']);
            $fecha_hasta = $data['fecha_hasta'];
            $fecha_respuesta = $datos_convocatoria['fecha_respuesta'];
            $datos_censales = $data['datos_censales'];
            $requisitos = $data['documentos'];

            // Inserto reapertura a beca
            $this->modelo_act->insertar_reapertura_beca($respuesta, $observaciones, $fecha_hasta, $fecha_respuesta, $datos_censales, $requisitos);

            // Recupero datos de la habilitación
            $datos_habilitacion = \toba::consulta_php('co_encuestas')->get_datos_habilitacion_convocatoria($id_convocatoria);

            $kolla_unidad_gestion = $datos_habilitacion['kolla_unidad_gestion'];
            $kolla_id_habilitacion = $datos_habilitacion['kolla_id_habilitacion'];
            $hash = $codigo_externo = $datos_convocatoria['hash'];

            // Reabro la encuesta respondida
            \guarani::act('act_encuestas')->reabrir_encuesta_respondida($hash);

            $persona = $datos_convocatoria['persona'];
            $habilitacion = $datos_habilitacion['habilitacion'];

            // Si hay requisitos a rechazar
            if (!empty($requisitos)) {
                // Rechazo requisitos del alumno
                $this->modelo_act->rechazar_requisitos_alumno($habilitacion, $persona, $requisitos);
            }

            // Envio notificación al alumno
            // No se considera la opción "Deseo recibir notificaciones vía email de los anuncios recibidos" dentro de "Configuración"
            // ya que se trata de algo importante que el alumno debe intervenir.
            $solo_reciben_mail = false;
            $emails_personas = \toba::consulta_php('co_personas')->get_emails_personas([$persona], $solo_reciben_mail);
            $nombres_personas = \toba::consulta_php('co_personas')->get_apellido_nombres_personas([$persona]);
            $parametros['persona'] = $persona;
            $parametros['email'] = $emails_personas;
            $parametros['nombres_elegido_alumno'] = $nombres_personas[$persona]['nombres'];
            $parametros['fecha_hasta'] = \DateTime::createFromFormat('Y-m-d', $fecha_hasta)->format('d/m/Y');
            $parametros['observaciones'] = $observaciones;
            $parametros['datos_censales'] = $datos_censales;
            $parametros['documentos'] = $requisitos;

            $tehuelche_unidad_gestion = $datos_habilitacion['tehuelche_unidad_gestion'];
            // Obtengo la convocatoria de Tehuelche
            try {
                $convocatoria = \beca::get_convocatoria($tehuelche_unidad_gestion, $id_convocatoria);
            // Si falla obtener la convocatoria de Tehuelche
            } catch (\Exception $e) {
                \toba::logger_ws()->error($e->getMessage());
                \toba::logger_ws()->error($e->getTraceAsString());
                $mensaje = 'Falló la comunicación con Tehuelche';
                throw new rest_error(500, $mensaje);
            }

            $parametros['nombre_convocatoria'] = $convocatoria['nombre'];

            // Envío la notificación al aspirante
            try {
                \guarani::notificador()->enviar(\notificador::notificar_reapertura_convocatoria_beca, $parametros);
            // Si falla enviar la notificación al aspirante
            } catch (\Exception $e) {
                \toba::logger_ws()->error($e->getMessage());
                \toba::logger_ws()->error($e->getTraceAsString());
                $mensaje = 'Falló el envío de la notificación al aspirante';
                throw new rest_error(500, $mensaje);
            }

            $datos = [
                'prorroga_hasta' => $fecha_hasta
            ];
            // Realizo la prorroga de la encuesta respondida
            try {
                \encuesta::modificar_formulario_respondido($kolla_id_habilitacion, $codigo_externo, $kolla_unidad_gestion, $datos);
            // Si falla la prorroga de la encuesta respondida
            } catch (\Exception $e) {
                \toba::logger_ws()->error($e->getMessage());
                \toba::logger_ws()->error($e->getTraceAsString());
                $mensaje = 'Falló la comunicación con Kolla';
                throw new rest_error(500, $mensaje);
            }

            \toba::db()->cerrar_transaccion();

        // En caso de arrojar un 400 o 404
        } catch (rest_error $e) {

            \toba::db()->abortar_transaccion();
            // Relanzo la excepción
            throw $e;

        // En caso de arrojar un Error o Excepción devuelvo 500
        } catch (\Throwable $e) {
            
            \toba::logger_ws()->error($e->getMessage());
            \toba::logger_ws()->error($e->getTraceAsString());
            \toba::db()->abortar_transaccion();

            throw new rest_error(500, $e->getMessage());

        }
    }
}