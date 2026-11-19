<?php
/**
 * 
 * @package Asistencia
 */
class asistencia_nucleo
{
	
	// Estados de ausencias a examen.
	const estado_ausencia_aceptado = 'Aceptado';
	const estado_ausencia_rechazado = 'Rechazado';
	const asist_default_presente = 'Presente';
	const asist_default_ausente = 'Ausente';
	
	//Asistencia
	const asist_presente = '0';
	const asist_ausente = '1';
	const asist_media_falta = '0.50';
	
	const asist_presente_descripcion = 'Presente';
	const asist_ausente_descripcion = 'Ausente';
	const asist_media_falta_descripcion = 'Media Falta';
	
	// Parámetro "asistencia_registrar_asistencia"
	const asist_no_registra = 'N';
	const asist_registra_valida_acta = 'S';
	const asist_registra_no_valida_acta = 'O';
	
	
	//PRESENTE (0) / AUSENTE (1) / MEDIA FALTA (0.5)
	
	static protected $instancia;

	static function instancia()
	{
		if (!isset(self::$instancia)) {
			self::$instancia = new asistencia();	
		}
		return self::$instancia;	
	}

	private function __construct()
	{
	
	}
	
	// Calcula el porcentaje de asistencia de un alumno a las clases de una comision
	// toDo: Resta determinar el tema de si se debe o no comparar el porcentaje via $instancia para determinar si un alumno es 'libre'
	static function calcular_asistencia_alumno($alumno=0, $comision=0, $instancia='Regular')
	{
		$porcentaje_asistencia = toba::consulta_php('co_asistencias')->get_alumno_comision_porc_asistencia($alumno, $comision);
    	
		return number_format($porcentaje_asistencia,2);
    }
    
	static function es_alumno_libre($alumno=0, $comision=0, $instancia='Regular')
	{
		$libre = toba::consulta_php('co_asistencias')->get_alumno_comision_libre($alumno, $comision);
    	
		if($libre == 0 )
			return comunes::no_desc;
		else 
			return comunes::si_desc;
    }
	
    // Devuelve cantidad de horas dictadas o cantidad de clases de acuerdo al parametro del sistema respectivo
    static function get_clases_horas_dictadas($alumno, $comision)
    {
    	$datos = toba::consulta_php('co_clases')->get_cant_clases_horas_comision_alumno($alumno, $comision);
    	
    	if (empty($datos)) {
			return 0;
		}
		
    	if ($datos['asistencia_cantidad_inasist'] == comunes::si) {
    		return $datos['cant_horas_clases'];
		} else {
			return $datos['cant_clases'];
		}
    }
    
    // Obtiene cantidad total de asistencia,cantidad total de asistencia justificadas y el porcentaje de asistencia de un alumno en una comision
	static function calcular_inasistencias_acumulada_de_un_alumno_x_comision($alumno=0, $comision=0)
	{
		$acum_asistencia = toba::consulta_php('co_asistencias')->get_inasistencias_acum($alumno, $comision);
		return $acum_asistencia;
    }
}
?>