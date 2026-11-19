<?php
/**
 * 	Representa una colacion
 *
 * @package Egresados
 * @subpackage Actualizaciones
 */
class cn_ent_colacion extends cn_entidad
{
	function set_datos($datos, $tabla = null, $validar = true)
	{
		$this->validar_datos($datos);
		$this->tabla('colaciones')->set($datos);
	}

	//--------------------------------------------------------------------------
	//---- Propuestas
	//--------------------------------------------------------------------------

	function get_datos_propuestas()
	{
		return $this->tabla('colaciones_propuestas')->get_filas();
	}

	function agregar_propuesta($datos_propuesta)
	{
		$this->tabla('colaciones_propuestas')->nueva_fila($datos_propuesta);
	}

	function eliminar_propuesta($id_fila_propuesta)
	{
		$this->tabla('colaciones_propuestas')->eliminar_fila($id_fila_propuesta);
	}

	function eliminar_propuestas()
	{
		$this->tabla('colaciones_propuestas')->eliminar_filas();
	}

	function validar_datos($datos)
	{
		$datos['colacion'] = $this->tabla('colaciones')->get_columna('colacion');
		$this->validar_nombre_colacion($datos['nombre'], $datos['colacion']);
		$this->validar_fechas($datos['fecha'], $datos['fecha_inicio_inscripcion'], $datos['fecha_fin_inscripcion']);
		$this->validar_numero($datos['numero']);
		if ($this->hay_errores()) {
			throw new guarani_error($this->get_mensaje_error());
		}
	}

	function validar_nombre_colacion($nombre, $colacion)
	{
		if (!toba::consulta_php('co_colaciones')->validar_nombre_colacion($nombre, $colacion)) {
			$this->set_error('dato_duplicado', array('nombre', 'colación'));
		}
	}

	function validar_fechas($fecha_colacion, $fecha_inicio_inscripcion, $fecha_fin_inscripcion)
	{
		if (!($this->dep('datos')->tabla('colaciones')->esta_cargada())) {
			if (guarani_fecha::es_menor($fecha_colacion, guarani_fecha::get_hoy())) {
				$this->set_error('800_fecha_del_pasado');
			}
		}

		if (!empty($fecha_inicio_inscripcion) && empty($fecha_fin_inscripcion)) {
			$this->set_error('800_fecha_nula', array('Fin Inscripción'));
		}
		if (empty($fecha_inicio_inscripcion) && !empty($fecha_fin_inscripcion)) {
			$this->set_error('800_fecha_nula', array('Inicio Inscripción'));
		}

		if (guarani_fecha::es_mayor_igual($fecha_inicio_inscripcion, $fecha_fin_inscripcion)) {
			$this->set_error('800_control_fechas', array('inicio', 'mayor o igual', 'fin.'));
		}
		if (!empty($fecha_fin_inscripcion)) {
			if (!guarani_fecha::es_menor_igual($fecha_fin_inscripcion, $fecha_colacion)) {
				$this->set_error('800_control_fechas', array('fin de inscripción', 'mayor', 'de la colación.'));
			}
		}
	}

	function validar_numero($numero)
	{
		$es_numero_entero = $numero == round($numero);
		$esta_en_rango = ($numero >= 0) && ($numero <= 999999);
		if (!$es_numero_entero || !$esta_en_rango) {
			$this->set_error('800_dato_invalido', array('Número', 'debe ser un entero entre 0 y 999.999'));
		}
	}

}
?>