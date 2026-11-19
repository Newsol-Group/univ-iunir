<?php
/**
 *	Representa los datos de anticipos
 *
 * @package Matrícula
 * @subpackage Actualizaciones
 */
class cn_ent_datos_anticipos_nsg extends cn_entidad
{

	function set_datos_anticipos($datos)
	{
		$this->tabla('datos_anticipos')->set($datos);
	}

	function get_anticipos_alumno()
	{
		$datos = $this->tabla('datos_anticipos')->get();
		return $datos;
	}

	function hay_cursor_anticipos()
	{
		return $this->tabla('datos_anticipos')->hay_cursor();
	}


	//-------------------------------------------------------------------

	function seleccionar_anticipo($id_fila_anticipo)
	{
		$this->tabla('datos_anticipos')->set_cursor($id_fila_anticipo);
	}

	function deseleccionar_anticipo()
	{
		$this->tabla('datos_anticipos')->resetear_cursor();
	}

	function hay_anticipo_seleccionado()
	{
		return $this->tabla('datos_anticipos')->hay_cursor();
	}

	function nuevo_anticipo($datos, $alumno)
	{
		$datos['alumno'] = $alumno['alumno'];
		$this->tabla('datos_anticipos')->nueva_fila($datos);
	}

	function eliminar_anticipo_seleccionado()
	{
		if ($this->hay_anticipo_seleccionado()) {
			$id_fila = $this->tabla('datos_anticipos')->get_cursor();

			$this->tabla('datos_anticipos')->eliminar_fila($id_fila);

		}
	}

	function get_datos_anticipo_seleccionado()
	{
		if ($this->hay_anticipo_seleccionado()) {
			return $this->tabla('datos_anticipos')->get();
		}
	}

	function get_datos_anticipos()
	{
		$datos = $this->tabla('datos_anticipos')->get_filas();
		$anticipos = toba::consulta_php('co_facturacion')->get_anticipos_alumno();

		if(!empty($datos)) {
			foreach ($datos as $clave => $valor) {
				foreach ($anticipos as $clave1 => $valor1) {
					if ($valor['dato_anticipo'] == $valor1['valor']) {
						$datos_anticipos[$clave] = $datos[$clave];
					}
				}
			}

			if(!empty($datos_anticipos)) {
				$datos = $datos_anticipos;
			} else {
				 $datos = array();
			}

		} else {
			$datos = array();
		}
		return $datos;

	}

	function recuperar_clave_db()
	{
		$tablas = $this->dep('datos')->get_tablas_raiz();
		if ( count($tablas) != 1 ) {
			throw guarani::error('La entidad debe poseer una única tabla cabecera');
		}

		$clave = $this->dep('datos')->tabla(current($tablas))->get_clave_valor($filas[0]);
		return $clave;
	}

}
?>