<?php
/**
 *
 * 
 * @package 	Administración
 * @subpackage 	Parámetros
 * 
 */
class cn_ent_parametros extends cn_entidad
{
	function sincronizar()
	{
		parent::sincronizar();
		$datos = $this->get_datos();
		guarani::cache_parametros()->limpiar_cache_parametro($datos['parametro']);
	}

	//--------------------------------------------------------------------------
	//---- par_parametros_sistema ----------------------------------------------
	//--------------------------------------------------------------------------

	function set_datos($datos, $tabla = null, $validar = true)
	{
		$this->validar_datos($datos);
		$this->tabla('parametros_sistema')->set($datos);
	}

	function get_datos($tabla = null)
	{
		return $this->tabla('parametros_sistema')->get();
	}

	function validar_datos($datos)
	{
		$tipo_de_parametro = toba::consulta_php('co_parametros')->get_tipo_parametro($datos['parametro']);
		if ($tipo_de_parametro == parametro::tipo_param_rango) {
			if (($datos['valor_default'] < $datos['valor_desde']) || ($datos['valor_default'] > $datos['valor_hasta'])) {
				$this->set_error('800ADM_param_fuera_de_rango', array($datos['valor_default'], $datos['valor_desde'], $datos['valor_hasta']));
			}

			if ($this->hay_errores()) {
				throw guarani::error($this->errores);
			}
		}
	}

	//--------------------------------------------------------------------------
	//---- par_param_sist_valores ----------------------------------------------
	//--------------------------------------------------------------------------

	function seleccionar_param_sist_valores($tipo, $entidad_tipo, $valor)
	{
		$datos = $this->get_param_sist_valores();
		// Se obtiene el ID de fila para seleccionar el cursor
		foreach ($datos as $dato) {
			if (($dato['tipo'] == $tipo) && ($dato['entidad_tipo'] == $entidad_tipo) && ($dato['valor'] == $valor)) {
				$this->set_cursor($dato['x_dbr_clave']);
				break;
			}
		}
	}

	function agregar_param_sist_valores($datos)
	{
		$cabecera = $this->get_datos();
		$cabecera['valor_default'] = $datos['valor'];
		$this->validar_datos($cabecera);

		return $this->tabla('param_sist_valores')->nueva_fila($datos);
	}

	function set_param_sist_valores($datos)
	{
		return $this->tabla('param_sist_valores')->procesar_filas($datos);
	}

	function get_param_sist_valores()
	{
		return $this->tabla('param_sist_valores')->get_filas();
	}

	function set_cursor($id_fila)
	{
		$this->tabla('param_sist_valores')->set_cursor($id_fila);
	}

	function hay_cursor()
	{
		return $this->tabla('param_sist_valores')->hay_cursor();
	}

	function unset_cursor()
	{
		$this->tabla('param_sist_valores')->resetear_cursor();
	}

	function set_param_sist_valores_seleccionado($datos)
	{
		if ($this->hay_cursor()) {
			$cabecera = $this->get_datos();
			$cabecera['valor_default'] = $datos['valor'];
			$this->validar_datos($cabecera);

			$this->tabla('param_sist_valores')->set($datos);
		}
	}

	function get_param_sist_valores_seleccionado()
	{
		if ($this->hay_cursor()) {
			return $this->tabla('param_sist_valores')->get();
		}
	}

	function eliminar_param_sist_valores_seleccionado()
	{
		if ($this->hay_cursor()) {
			$this->tabla('param_sist_valores')->eliminar_fila($this->tabla('param_sist_valores')->get_cursor());
		}
	}

	/**
	 * Obtiene un listado de entidades que no pueden ser seleccionables, por tener otra
	 * configuración de valor para el mismo tipo de entidad
	 * 
	 * @param chat		$tipo
	 * @param int		$entidad_tipo
	 * @param string	$valor
	 * @return array
	 */
	function get_entidades_no_seleccionables($tipo, $entidad_tipo, $valor)
	{
		$salida = array();
		if ($tipo == parametro::nivel_tipo_entidad) {
			$tabla = 'param_sist_valores_entidad';
			$datos_nivel = $this->tabla('param_sist_valores_entidad')->get_filas(null, false, false);
			$campo = 'entidad';
		} else {
			$tabla = 'param_sist_valores_subtipo';
			$datos_nivel = $this->tabla('param_sist_valores_subtipo')->get_filas(null, false, false);
			$campo = 'entidad_subtipo';
		}
		
		foreach ($datos_nivel as $key => $dato_nivel) {
			$id_padre = $this->tabla($tabla)->get_id_fila_padre('param_sist_valores', $dato_nivel['x_dbr_clave']);
			$datos_padre = $this->tabla('param_sist_valores')->get_fila($id_padre);
						
			$datos = array_merge($dato_nivel, $datos_padre);
			
			if (($datos['tipo'] == $tipo) && 
				($datos['entidad_tipo'] == $entidad_tipo) && 
				($datos['valor'] <> $valor)) {
				// Entidad no editable
				$salida[] = $dato_nivel[$campo];
			}
		}
		
		return $salida;
	}
	
	//--------------------------------------------------------------------------
	//---- par_param_sist_valores_entidad --------------------------------------
	//--------------------------------------------------------------------------

	function set_param_sist_valores_entidad($datos)
	{
		$entidades = $this->tabla('param_sist_valores_entidad')->get_filas();
		$entidades_aplanadas = aplanar_matriz($entidades, 'entidad');
		$datos = aplanar_matriz($datos, 'entidad');
		$entidades_a_borrar = array_diff($entidades_aplanadas, $datos);
		$entidades_a_insertar = array_diff($datos, $entidades_aplanadas);

		$entidades_asociativas = rs_convertir_asociativo($entidades, array('entidad'), apex_datos_clave_fila);
		foreach ($entidades_a_borrar as $entidad) {
			$this->tabla('param_sist_valores_entidad')->eliminar_fila($entidades_asociativas[$entidad]);
		}

		foreach ($entidades_a_insertar as $entidad) {
			$this->tabla('param_sist_valores_entidad')->nueva_fila(array('entidad' => $entidad));
		}
	}

	function get_param_sist_valores_entidad()
	{
		return $this->tabla('param_sist_valores_entidad')->get_filas();
	}

	//--------------------------------------------------------------------------
	//---- par_param_sist_valores_subtipo --------------------------------------
	//--------------------------------------------------------------------------

	function set_param_sist_valores_subtipo($datos, $parametro = null, $tipo = null, $entidad_tipo = null)
	{
		$subtipos = $this->tabla('param_sist_valores_subtipo')->get_filas();
		$subtipos_aplanadas = aplanar_matriz($subtipos, 'entidad_subtipo');
		$datos = aplanar_matriz($datos, 'entidad_subtipo');
		$subtipos_a_borrar = array_diff($subtipos_aplanadas, $datos);
		$subtipos_a_insertar = array_diff($datos, $subtipos_aplanadas);

		$subtipos_asociativo = rs_convertir_asociativo($subtipos, array('entidad_subtipo'), apex_datos_clave_fila);
		foreach ($subtipos_a_borrar as $subtipo) {
			$this->tabla('param_sist_valores_subtipo')->eliminar_fila($subtipos_asociativo[$subtipo]);
		}

		foreach ($subtipos_a_insertar as $subtipo) {
			$this->tabla('param_sist_valores_subtipo')->nueva_fila(array('entidad_subtipo' => $subtipo));
		}
	}

	function get_param_sist_valores_subtipo()
	{
		return $this->tabla('param_sist_valores_subtipo')->get_filas();
	}

}
?>