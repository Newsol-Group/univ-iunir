<?php
/**
 *
 *
 * @package     Matrícula
 * @subpackage  Alumnos
 * 
 */
class vencimiento_regularidad_nucleo
{
	const fecha = 'fecha';
	const sin_vencimiento = 'sin_vencimiento';
	
	const descr_fecha = 'Fecha';
	const descr_sin_vencimiento = 'Sin Vencimiento';
	
	// Estados de la Readmisión de un Alumno.
	const readmision_controlada = 'C';

	// Estados de la solicitud de readmisión de un alumno.
	const solicitud_readmision_aceptada = 'A';
	const solicitud_readmision_pendiente = 'P';
	const solicitud_readmision_rechazada = 'R';

	// Tipos de readmisión.
	const readmision_tipo_reincorporacion = 'R';

	// Reglas de cálculo de regularidad para controlar...
	const para_controlar_regularidad = 1;
	const para_controlar_readmisiones = 2;
	const para_controlar_regularidad_readmisiones = 0;
	const para_controlar_regularidad_descr = 'Sólo para control de regularidad';
	const para_controlar_readmisiones_descr = 'Sólo para control de readmisiones vencidas';
	const para_controlar_regularidad_readmisiones_descr = 'Para control de regularidad y readmisiones vencidas';

	// Alumnos a verificar...
	const alumnos_regulares_sin_readmision = 'RS';
	const alumnos_regulares_sin_readmision_descr = 'Regulares sin readmisión';
	const alumnos_regulares_con_readmision_vencida = 'RV';
	const alumnos_regulares_con_readmision_vencida_descr = 'Regulares con readmisión vencida';
	const alumnos_no_regulares = 'NR';
	const alumnos_no_regulares_descr = 'No regulares';

	static function get_valores_fecha()
	{
		return array(array('valor' => self::fecha, 'descr' => self::descr_fecha),
		             array('valor' => self::sin_vencimiento,'descr' => self::descr_sin_vencimiento)
		            );
	}

	/**
	 * Retorna un arreglo con los requisitos de regularidad no cumplidos por el alumno dado, de acuerdo a las
	 * causas de pérdida de regularidad dadas y utilizando como parámetro para la validación el año académico y rango de fechas dado.
	 * @param array $alumno datos del alumno para el cual se verifican los requisitos
	 * @param array $causas_perdidas_regularidad causas por las cuales el alumno puede perder la regularidad
	 * @param int $anio_academico año utilizado para validar las reglas
	 * @param date $fecha_desde fecha desde utilizada para validar las reglas
	 * @param date $fecha_hasta fecha hasta utilizada para validar las reglas
	 * @return array arreglo con todas las causas de pérdida de regularidad para las que el alumno no valida el requisito.
	 */
	static function get_requisitos_no_cumplidos($alumno, $causas_perdidas_regularidad, $anio_academico, $fecha_desde, $fecha_hasta)
	{
		$requisitos_no_cumplidos = array();
		foreach ($causas_perdidas_regularidad as $causa) {
			$regla = guarani::regla($causa['regla']);

			// Parámetros de contexto de la regla.
			$parametros_contexto = array();
			$parametros_contexto['alumno'] = $alumno['alumno'];
			$parametros_contexto['persona'] = $alumno['persona'];
			$parametros_contexto['valor_parametro'] = guarani::parametros()->get_valor('mat_regularidad_alumno', array($alumno['propuesta']));

			// Parámetros asociativos de la regla.
			$parametros_asociativos = array();
			$parametros_asociativos['anio'] = $anio_academico;
			$parametros_asociativos['fecha_desde'] = $fecha_desde;
			$parametros_asociativos['fecha_hasta'] = $fecha_hasta;

			$regla->set_parametros_contexto($parametros_contexto);
			$regla->set_parametros_asociativos($parametros_asociativos);
			$valida = $regla->validar();
			if (!$valida) {
				$requisitos_no_cumplidos[] = array(	'causa_perdida_reg' => $causa['causa_perdida_reg'],
													'descripcion' => $causa['descripcion']);
			}
		}
		return $requisitos_no_cumplidos;
	}
}
?>