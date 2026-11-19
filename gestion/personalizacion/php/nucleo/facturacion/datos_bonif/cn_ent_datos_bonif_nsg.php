<?php
/**
 *	Representa los datos de bonificaciones
 *
 * @package Matrícula
 * @subpackage Actualizaciones
 */
class cn_ent_datos_bonif_nsg extends cn_entidad
{

	function set_datos_bonif($datos, $tabla = null, $validar=true)
	{
	//	$this->validar_datos($datos);
		$this->tabla('datos_beneficios')->set($datos);
	}

	function get_bonif_alumno()
	{
		$datos = $this->tabla('datos_beneficios')->get();
		return $datos;
	}

	function hay_cursor_bonif()
	{
		return $this->tabla('datos_beneficios')->hay_cursor();
	}


	//-------------------------------------------------------------------

	function seleccionar_bonif($id_fila_bonif)
	{
		$this->tabla('datos_beneficios')->set_cursor($id_fila_bonif);
	}

	function deseleccionar_bonif()
	{
		$this->tabla('datos_beneficios')->resetear_cursor();
	}

	function hay_bonif_seleccionada()
	{
		return $this->tabla('datos_beneficios')->hay_cursor();
	}

	function nueva_bonif($datos, $alumno)
	{
		$datos['alumno'] = $alumno['alumno'];
		$datos['tipo_beneficio'] = $datos['tipo_beneficio'];
		$this->tabla('datos_beneficios')->nueva_fila($datos);
	}

	function eliminar_bonif_seleccionada()
	{
		if ($this->hay_bonif_seleccionada()) {
			$id_fila = $this->tabla('datos_beneficios')->get_cursor();

			$this->tabla('datos_beneficios')->eliminar_fila($id_fila);
		}
	}

	function get_datos_bonif_seleccionada()
	{
		if ($this->hay_bonif_seleccionada()) {
			return $this->tabla('datos_beneficios')->get();
		}
	}

	function get_datos_bonif()
	{
		$datos = $this->tabla('datos_beneficios')->get_filas();
		$bonif = toba::consulta_php('co_facturacion')->get_tipos_bonif_alumno();

		if(!empty($datos)) {
			foreach ($datos as $clave => $valor) {
				foreach ($bonif as $clave1 => $valor1) {
					if ($valor['tipo_beneficio'] == $valor1['valor']) {
						$datos_bonif[$clave] = $datos[$clave];
					}
				}
			}

			if(!empty($datos_bonif)) {
				$datos = $datos_bonif;
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

	//-------------------------------------------------------------------

	function validar_datos($datos)
	{
		$datos['tipo_beneficio'] = $this->tabla('datos_beneficios')->get_columna('tipo_beneficio');
		$this->validar_nombre_datos_bonif($datos['descr_beneficio'], $datos['alumno']);
		if ($this->hay_errores()) {
			throw new guarani_error($this->get_mensaje_error());
		}
	}
	
	// Determinar si el nombre del Tipo de Bonificación ya existe
	function validar_nombre_datos_bonif($nombre, $tipo_bonif) 
	{
		if (! toba::consulta_php('co_facturacion')->validar_nombre_datos_becas_bonif_alumno($nombre, $tipo_bonif)) {
			$this->set_error('dato_duplicado', array('nombre', 'tipo de bonif'));
		}			
	}

}
?>