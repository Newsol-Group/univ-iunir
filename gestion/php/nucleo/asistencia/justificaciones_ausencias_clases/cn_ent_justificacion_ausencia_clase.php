<?php 
/**
 *
 *
 * @package Asistencias
 * @subpackage -
 */
class cn_ent_justificacion_ausencia_clase extends cn_entidad
{
	protected $s__carga_horas;
	
	function set_asistencias_por_horas($valor)
	{
		// Indica si la inasistencia se carga en horas o en presente/ausente.
		$this->s__carga_horas = $valor;
	}
	
	function set_datos($datos, $tabla = null, $validar=true)
	{
		$this->validar_datos($datos);
		$this->tabla('asistencia')->set($datos);
	}
	
	function get_datos($tabla=null)
	{
		return $this->tabla('asistencia')->get();
	}
	
	// Resetea los campos referidos a la justificación del registro de asistencia del alumno a clase.
	function eliminar_justificacion_inasistencia()
	{
		$this->tabla('asistencia')->set_columna_valor('cant_justificadas', 0);
		$this->tabla('asistencia')->set_columna_valor('motivo_ausencia', null);
		$this->tabla('asistencia')->set_columna_valor('estado_justificacion', null);
		$this->tabla('asistencia')->set_columna_valor('observaciones', null);
	}
	
	//-----------------------------------------------------------------------------------
	//---- Validaciones -----------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function validar_datos($datos)
	{
		 // Control sobre el campo cant_justificadas.
		if ($datos['estado_justificacion'] != asistencia::estado_ausencia_rechazado) {
			if ((isset($datos['cant_justificadas'])) && (isset($datos['cant_inasistencias']))){
				$this->validar_cant_maxima_justificadas($datos);
				$this->validar_cant_minima_justificadas($datos);
			}
		}

		if ($this->hay_errores()) {
			throw guarani::error($this->errores);
		}
	}
	
	/**
	 * Verifica que el valor de cantidad justificadas no supere al del campo cantidad inasistencias.
	 */
	function validar_cant_maxima_justificadas($datos)
	{
		$cant_inasistencias = $datos['cant_inasistencias'];
		$cant_justificadas = $datos['cant_justificadas'];
		if ($cant_justificadas > $cant_inasistencias) {
			$this->set_error('800_valor_mayor', array('Cantidad Justificadas', 'Cantidad Inasistencias'));
		}
	}
	
	/**
	 * Verifica que el valor de cantidad justificadas no sea menor al permitido.
	 * El valor mínimo varía de acuerdo al parámetro asist_computa_inasist_por_horas.
	 * Si es N el menor valor será 0.50, de lo contrario el menor valor será 0
	 */
	function validar_cant_minima_justificadas($datos)
	{
		$valor = $this->obtener_valor_minimo_justificadas();
		if ($datos['cant_justificadas'] < $valor) {
			$this->set_error('800_valor_menor', array('Cantidad Justificadas', $valor));
		}
	}
	
	//-----------------------------------------------------------------------------------
	//---- Auxiliares -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	
	// Retorna true o false dependiendo de si hay cargado un motivo de ausencia para la asistencia del alumno a la clase.
	function hay_motivo_justificacion()
	{
		$datos_justificacion = $this->get_datos();
		return isset($datos_justificacion['motivo_ausencia']);
	}
	
	function obtener_valor_minimo_justificadas()
	{
		if ($this->s__carga_horas == comunes::si) {
			return 0;
		} else {
			return 0.5;
		}
	}
}
?>
