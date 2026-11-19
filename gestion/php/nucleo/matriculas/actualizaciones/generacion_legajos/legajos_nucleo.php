<?php
class legajos_nucleo 
{
	const generacion_automatica = 'A';
	const generacion_manual = 'M';
	
	const generacion_automatica_descr = 'Automática';
	const generacion_manual_descr = 'Manual';
	
	const combo_autocompletar_valor_todos = 'todos';
	const combo_autocompletar_descr_todos = 'Todos';
	
	const combo_autocompletar_valor_vacios = 'vacios';
	const combo_autocompletar_descr_vacios = 'Vacíos';
	
	const combo_con_legajo_descr = 'Con legajo';
	const combo_sin_legajo_descr = 'Sin legajo';
		
	/**
	 * Obtiene un nuevo nro. de legajo automático
	 *
	 */
	static function get_nuevo_nro_legajo($unico_legajo, $inscripcion, $persona, $propuesta, $ubicacion)
	{
		$unico_legajo = toba::db()->quote($unico_legajo);
		$sql = "SELECT f_generar_legajo_alumno($unico_legajo, $inscripcion, $persona, $propuesta, $ubicacion) as nro_legajo;";

		$res = guarani_db::consultar_fila($sql);
		return $res['nro_legajo'];
	}
	
	/**
	 * Obtiene la descripción de la forma de generación de legajo (obtenida a partir
	 * del parámetro 'mat_legajo_alumno_generacion').
	 * 
	 * @param char $forma_generacion
	 * @return string
	 */
	static function get_descripcion_forma_generacion($forma_generacion)
	{
		$salida = '';
		if ($forma_generacion == self::generacion_automatica) {
			$salida = self::generacion_automatica_descr;
		} elseif ($forma_generacion == self::generacion_manual) {
			$salida = self::generacion_manual_descr;
		}
		return $salida;
	}
}
?>