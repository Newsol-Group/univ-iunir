<?php
/**
 * Clase nucleo para Beneficio de Boleto Estudiantil
 * @see https://colab.siu.edu.ar/trac/guarani3/wiki/contrib/boleto_estudiantil
 * @package Boleto Estudiantil
 */
class boleto_estudiantil_nucleo
{
	const RESPUESTA_ACEPTADA = 'ACEPTADA';
	const RESPUESTA_RECHAZADA = 'RECHAZADA';
	const RESPUESTA_NUEVA = 'NUEVA';
	const RESPUESTA_PENDIENTE = 'PENDIENTE';
	const RESPUESTA_ACTUALIZADA = 'ACTUALIZADA';
    
	const RESPUESTA_DESC_OK = 'OK';
	const RESPUESTA_DESC_TRAMITE_EXPIRADO = 'TRAMITE_EXPIRADO';
	
	const OPERACION_A = 'A';
	const OPERACION_B = 'B';
	
	// Códigos /benefit-request
	const STATUS_CODE_SOLICITUD_OK = 201;
	const STATUS_CODE_SOLICITUD_ERROR_VALIDAR_DATOS = 400;
	const STATUS_CODE_SOLICITUD_ERROR_CON_DATOS = 401;
	const STATUS_CODE_SOLICITUD_ERROR_CREDENCIALES = 403;
	const STATUS_CODE_SOLICITUD_BENEFICIO_YA_ASIGNADO = 409;
	const STATUS_CODE_SOLICITUD_BENEFICIO_YA_ASIGNADO_PERO_DESHABILITADO = 423;
	const STATUS_CODE_SOLICITUD_ERROR = 500;
	
	const OPERACION_QUITAR_BENEFICIO = 'disable';
	const OPERACION_REANUDAR_BENEFICIO = 'enable';
	
	const CONDICION_INGRESANTE = 'I';
	const CONDICION_NO_INGRESANTE = 'C';
	
	// Códigos /benefit-update
	const STATUS_CODE_UPDATE_OK = 200;
	const STATUS_CODE_UPDATE_STUDENT_NOT_FOUND = 404;
	const STATUS_CODE_UPDATE_INVALID_OPERATION = 400;
    
    /**
     * Parámetro: usa_boleto_estudiantil
     * @see https://redmine.siu.edu.ar/redmine/projects/guarani3/wiki/ParametrosG3usa_boleto_estudiantil
     */
    const USA_BOLETO_ESTUDIANTIL = 'usa_boleto_estudiantil';

    /**
     * Verifica si cumple las condiciones para solicitar el beneficio de Boleto Estudiantil.
     *
     * @param int $persona     ID de persona.
     *
     * @return bool Retorna true si cumple las condiciones, false en caso contrario.
     */
    public static function verificar_beneficio($persona)
    {
        return toba::consulta_php('co_boleto_estudiantil')->verificar_beneficio($persona);
    }

}