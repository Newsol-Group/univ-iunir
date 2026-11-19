<?php
class ci_navegacion_planes extends ci_navegacion
{
	// Se modifica el ancho máximo del combo para que se visualicen mejor las propuestas.
	const chars_maximo_combo = 70;

	function usa_planes_personalizados()
	{
		return guarani::parametros()->get_valor('pro_plan_pers_usa_planes_personalizados') == comunes::si;
	}

	function ini()
	{
		if (toba::zona()->cargada() && !$this->cn()->entidad_cargada()) {
			$plan_version = toba::zona()->get_editable();
			$this->s__seleccion = $plan_version;
			$this->cn()->cargar($plan_version);
			$this->set_pantalla('edicion');
		}
	}

	//--------------------------------------------------------------------------
	//---- Filtro --------------------------------------------------------------
	//--------------------------------------------------------------------------

	function conf__filtro(toba_ei_filtro $filtro)
	{
		toba_ef_combo::set_maximo_descripcion(self::chars_maximo_combo);
		parent::conf__filtro($filtro);
		if (!$this->usa_planes_personalizados()) {
			$filtro->eliminar_columnas(array('tipo_plan'));
		}
		if (isset($this->s__datos)) {
			$filtro->set_datos($this->s__datos);
		} elseif ($this->usa_planes_personalizados() && (!isset($this->s__filtro))) {
			// Si no hay datos seteados, en el conf inicial del Filtro si se usan Planes Personalizados, deben traerse por defecto los Estructurados.
			$this->s__datos = array('tipo_plan' => array('valor' => planes::tipo_estructurado, 'condicion' => 'es_igual_a'));
			$filtro->set_datos($this->s__datos);
			$this->s__filtro = $filtro->get_sql_where();
		}
	}

	//--------------------------------------------------------------------------
	//---- Cuadro --------------------------------------------------------------
	//--------------------------------------------------------------------------

	function evt__cuadro__seleccion($seleccion)
	{
		toba::zona()->cargar($seleccion['plan_version']);
		parent::evt__cuadro__seleccion($seleccion['plan_version']);
	}

	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		$cuadro->set_usar_ordenamiento_con_cortes();
		$datos = $this->cn()->get_listado($this->get_filtro_condicion());
		foreach ($datos as $key => $valor) {
			if (isset($valor['plan_nombre'])) {
				$plan_nombre = $valor['plan_nombre'];
			}
			if ($valor['version_estado'] == 'N') {
				$datos[$key]['activo'] = false;
			} else {
				$datos[$key]['activo'] = true;
			}
			$datos[$key]['propuesta_datos'] = "(" . $datos[$key]['propuesta_codigo'] . ") - " . $datos[$key]['propuesta_nombre'];
			$datos[$key]['plan_datos'] = "Plan: (" . $datos[$key]['plan_codigo'] . ") - " . $plan_nombre . " - " . $datos[$key]['plan_estado_descripcion'];
		}
		
		// El evento de Copiar Plan se usa solo desde administrar, pero debe ocultarse en los demás casos, 
		// ya que el componente cuadro es compartido desde todas las operaciones.
		if (guarani::get_id_operacion() != planes::cod_op_admin_planes) {
			$cuadro->evento('copiar_estructura_a_plan_personalizado')->ocultar();
		}
		$cuadro->set_datos($datos);
		$msj = $this->get_msg_eof();
		$cuadro->set_eof_mensaje($msj);
	}

	//--------------------------------------------------------------------------
	//---- Eventos  ------------------------------------------------------------
	//--------------------------------------------------------------------------

	function evt__cancelar()
	{
		toba::zona()->resetear();
		parent::evt__cancelar();
	}

}
?>