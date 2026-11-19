<?php
/**
 *
 *
 * @package		Constancias y Certificados
 *
 */
class constancias_nucleo
{
	// Estados de Solicitudes de Constancias.
	const estado_solicitud_pendiente	= 'Pendiente';
	const estado_solicitud_impreso		= 'Impreso';
	const estado_solicitud_rechazado	= 'Rechazado';
	const estado_solicitud_entregado	= 'Entregado';
	const estado_solicitud_online		= 'Online';

	const con_notifica_certificado_curso		= 'con_notifica_certificado_curso';
	const con_notifica_estado_solicitud			= 'con_notifica_estado_solicitud';

	// Calidades de Autoridades Firmantes de Constancias.
	const autoridad_titular			= 'T';
	const autoridad_suplente		= 'S';
	
	const constancia_de_alumno_regular					= 1;
	const constancia_de_promedio_academico				= 2;
	const constancia_de_promedio_academico_sin_aplazos	= 3;
	const constancia_de_actividades_rendidas_por_examen	= 4;
	const constancia_de_actividades_aprobadas			= 5;
	const constancia_de_actividades_desaprobadas		= 6;
	const constancia_de_examen_final						= 7;
	const constancia_de_evaluacion_parcial				= 8;
	const constancia_de_aprobacion_de_curso				= 9;
	const constancia_de_asistencia_a_curso				= 10;
	const constancia_de_asistencia_a_clase				= 11;
	
	const operacion_validador_3w = 'validador_certificados';
		
	/*
	 * Código de verificación:
	 * 2 dígitos para el año en curso (ej: 15 => 2015)
	 * 2 dígitos para el mes en curso (ej: 03 => Marzo)
	 * 2 dígitos para el día de hoy (ej: 25 => 25/03)
	 * 2 dígitos para la hora en la que se genera el código (ej: 15 => 15:19)
	 * 2 dígitos para los minutos de la hora en la que se genera el código (ej: 19 => 15:19)
	 * 2 dígitos para los segundos de la hora en la que se genera el código (ej: 27 => 15:19:27)
	 * 2 dígito random (ej: 45).
	 */
	static function generar_codigo_verificacion()
	{	 
		return date('ymdHis').rand(10,99);
	}
}
?>