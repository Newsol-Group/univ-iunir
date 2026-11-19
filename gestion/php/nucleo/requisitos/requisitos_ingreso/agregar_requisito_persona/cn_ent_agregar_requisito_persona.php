<?php 
/**
 *
 *
 * @package 	Requisitos
 * @subpackage 	Requisitos de Ingreso
 * 
 */
class cn_ent_agregar_requisito_persona extends cn_entidad
{
	function set_datos_requisito_persona($datos, $propuesta_aspira)
	{
		$datos['propuesta_aspira'] = $propuesta_aspira;
		$this->tabla('req_aspirante')->nueva_fila($datos);
	}
	
	function set_datos_requisitos_asignados($datos)
	{
		$this->tabla('req_aspirante')->procesar_filas($datos);
	}

	function set_datos_requisito($datos)
	{
		if ($this->hay_requisito_seleccionado()) {
			$this->tabla('req_aspirante')->set($datos);
		}
	}

	function seleccionar_requisito($id_fila_requisito)
	{
		$this->tabla('req_aspirante')->set_cursor($id_fila_requisito);
	}

	function deseleccionar_requisito()
	{
		$this->tabla('req_aspirante')->resetear_cursor();
	}

	function get_datos_requisito_seleccionado()
	{
		if ($this->hay_requisito_seleccionado()) {
			return $this->tabla('req_aspirante')->get();
		}
	}

	function hay_requisito_seleccionado()
	{
		return $this->tabla('req_aspirante')->hay_cursor();
	}

	function get_datos($tabla=null)
	{
		$datos = $this->tabla('req_aspirante')->get_filas();
		foreach ($datos as $clave => $valor) {
			if ($valor['requisito_tipo'] == requisito::tipo_procesos && !empty($valor['parametros'])) {
				$regla = guarani::regla($valor['regla']);
				$regla->set_parametros($valor['parametros']);
				$datos[$clave]['nombre'] = $regla->get_mensaje();
			}
		}
		return $datos;
	}
	
	function eliminar_datos($seleccion)
	{
		$this->tabla('req_aspirante')->eliminar_fila($seleccion);
	}
	
	/**
	 * Redefinición del método de carga de una entidad.
	 */
	function cargar($id)
	{
		//Se preparan los wheres para las tablas del datos relación
		$where_requisitos  = 'sga_requisitos_aspirante.propuesta_aspira = '.current($id);
		
		//Se asocia cada where con su respectiva tabla
		$wheres['req_aspirante'] = $where_requisitos;
		
		//Se carga el datos relación a partir de los wheres
		$this->dep('datos')->persistidor()->cargar_con_wheres($wheres);
		$this->cargar_etiquetas();
	}
	
	/**
	 * Se redefine el método porque el original retorna el identificador del registro agregado en la tabla cabecera de la relación.
	 * En este caso, la tabla cabecera (única en la relación) posee más de un registro.
	 * 
	 */
	function sincronizar()
	{
		$this->dep('datos')->sincronizar();
	}

	function validar_parametros_requisito($parametros_actuales)
	{
		$datos = $this->get_datos_requisito_seleccionado();
		$parametros_formales = toba::consulta_php('co_requisitos')->get_requisito_regla_params_def($datos['requisito']);
		$i = 0;
		foreach ($parametros_formales as $parametro_formal) {
			regla::validar_parametro($parametro_formal, $parametros_actuales[$i]);
			$i++;
		}
	}
}
?>