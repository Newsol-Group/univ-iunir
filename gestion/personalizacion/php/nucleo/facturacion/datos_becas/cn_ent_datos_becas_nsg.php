<?php
/**
 *	Representa los datos de becas y bonificaciones
 *
 * @package Matrícula
 * @subpackage Actualizaciones
 */
class cn_ent_datos_becas_nsg extends cn_entidad
{

	function set_datos_becas($datos)
	{
		$this->tabla('datos_beneficios')->set($datos);
	}

	function get_becas_alumno()
	{
		$datos = $this->tabla('datos_beneficios')->get();
		return $datos;
	}

	function hay_cursor_becas()
	{
		return $this->tabla('datos_beneficios')->hay_cursor();
	}


	//-------------------------------------------------------------------

	function seleccionar_beca($id_fila_beca)
	{
		$this->tabla('datos_beneficios')->set_cursor($id_fila_beca);
	}

	function deseleccionar_beca()
	{
		$this->tabla('datos_beneficios')->resetear_cursor();
	}

	function hay_beca_seleccionada()
	{
		return $this->tabla('datos_beneficios')->hay_cursor();
	}

	function nueva_beca($datos, $alumno)
	{
		$datos['alumno'] = $alumno['alumno'];
		$datos['tipo_beneficio'] = $datos['tipo_beneficio'];
		$this->tabla('datos_beneficios')->nueva_fila($datos);
	}

	function eliminar_beca_seleccionada()
	{
		if ($this->hay_beca_seleccionada()) {
			$id_fila = $this->tabla('datos_beneficios')->get_cursor();

			$this->tabla('datos_beneficios')->eliminar_fila($id_fila);

		}
	}

	function get_datos_beca_seleccionada()
	{
		if ($this->hay_beca_seleccionada()) {
			return $this->tabla('datos_beneficios')->get();
		}
	}

	function get_datos_becas()
	{
		$datos = $this->tabla('datos_beneficios')->get_filas();
		$becas = toba::consulta_php('co_facturacion')->get_tipos_beca_alumno();

		if(!empty($datos)) {
			foreach ($datos as $clave => $valor) {
				foreach ($becas as $clave1 => $valor1) {
					if ($valor['tipo_beneficio'] == $valor1['valor']) {
						$datos_becas[$clave] = $datos[$clave];
					}
				}
			}

			if(!empty($datos_becas)) {
				$datos = $datos_becas;
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