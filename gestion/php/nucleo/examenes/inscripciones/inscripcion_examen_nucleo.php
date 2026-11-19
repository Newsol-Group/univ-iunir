<?php
/**
 * Constantes y métodos para Inscripciones a Examenes
 *
 * 
 * @package		Examenes
 * @subpackage	Inscripciones
 * 
 */
class inscripcion_examen_nucleo
{
	// Estados
	const estado_aceptada = 'A';
	const estado_aceptada_descr = 'Aceptada';
	const estado_pendiente = 'P';
        const estado_pendiente_descr = 'Pendiente';
	//no son estados pero los trabajamos como estados
	const estado_rechazo = 'R';
	const estado_rechazo_desc = 'Rechazada';
	const estado_baja = 'B';
	const estado_baja_desc = 'Baja';
	const estado_suspendido= 'S';
	const estado_suspendido_desc= 'Suspendida';

	const inst_insc_regular = 3;
	const inst_insc_libre = 4;
	const inst_insc_revalida_cur = 5;
	const inst_insc_revalida_exa = 6;
	const inst_insc_complementario = 7;
	const inst_insc_equivalencias = 8;
	
	const operacion_rechazo = 'R';
	const operacion_baja = 'B';

        // Parametros
        const exa_notificar_estado_inscripcion = 'exa_notificar_estado_inscripcion';
	/*
	 * Determina si en la inscripción a exámenes se ejecutan o no los controles en los puntos de control.
	 */
	static function ejecutar_controles($propuestas)
	{
		return (guarani::parametros()->get_valor('exa_ejecuta_controles', $propuestas) == comunes::si);
	}

	/*
	 * Devuelve el estado correspondiente al retorno de validación de puntos de control recibido.
	 */
	static function get_estado_equivalente($retorno_punto_de_control)
	{
		switch ($retorno_punto_de_control) {
			case guarani_punto_de_control::autorizado:
				return self::estado_aceptada;
			case guarani_punto_de_control::advertido:
				return self::estado_pendiente;
			case guarani_punto_de_control::rechazado:
				$msj = guarani_mensajes::get('800_inscripcion_fallo_ctrl');
				throw guarani::error($msj);
		}
	}

}