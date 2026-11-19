<?php
class ci_nav_plan_activacion extends ci_navegacion_planes
{
	protected $s__navegar;
	protected $s__vinculo_cambiar_plan;

	function conf()
	{
		$this->s__filtro = "sga_planes.estado <> '" . planes::estado_baja . "' 
							AND sga_planes_versiones.estado = '" . planes::estado_nuevo . "'";
		parent::conf();
	}
	
	function conf__seleccion(toba_ei_pantalla $pantalla)
	{
		$this->cn()->resetear_atributos_parametros();
	}

	/**
	 * Activar Plan
	 */
	function evt__guardar() 
	{
		// Se recuperan los datos de la Versión a Activar para realizar el Pasaje de Alumnos antes de Guardar, 
		// ya que en el proceso de Activación el DR se recarga con la Versión Vigente como cabecera, en lugar de la 
		// seleccionada, y no se vuelve al estado origen, asumiendo que a continuación se resetea para volver a la 
		// pantalla inicial, con lo cual no puede consultarse la entidad.
		if ($this->cn()->entidad()->es_nueva_version()) {
			$datos_version_a_activar = $this->cn()->entidad()->get_datos_version();
			$activador = toba::cn($this->cn()->get_proceso_activacion());
			$activador->procesar($this->cn()->entidad()->get_id_plan_version());
			// Se genera el vínculo y se setea el flag para consultar al usuario si desea cambiar los alumnos al nuevo plan activo.
			if ($this->cn()->entidad()->es_plan_personalizado() || $this->cn()->entidad()->es_plan_para_administrar_cursos()) {
				toba::notificacion()->info(guarani_mensajes::get('800PRO_activar_plan_ok'));
				$this->evt__cancelar();
			} else {
				$this->s__vinculo_cambiar_plan = toba::vinculador()->crear_vinculo(toba_proyecto::get_id(), '37000111');
				$this->s__navegar = true;
			}
		}
	}

	function evt__cancelar()
	{
		$this->cn()->resetear();
		toba::zona()->resetear();
		$this->set_pantalla('seleccion');
	}

	//--------------------------------------------------------------------------
	//---- cuadro --------------------------------------------------------------
	//--------------------------------------------------------------------------

	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		parent::conf__cuadro($cuadro);
		$cuadro->set_usar_ordenamiento_con_cortes();
		$datos = $this->cn()->get_listado($this->get_filtro_condicion());
		foreach ($datos as $key => $valor) {
			if (isset($valor['plan_nombre'])) {
				$plan_nombre = $valor['plan_nombre'];
			}
			if ($valor['version_estado'] == planes::estado_nuevo) {
				$datos[$key]['activo'] = false;
			} else {
				$datos[$key]['activo'] = true;
			}
			$datos[$key]['propuesta_datos'] = "(" . $datos[$key]['propuesta_codigo'] . ") - " . $datos[$key]['propuesta_nombre'];
			$datos[$key]['plan_datos'] = "Plan: (" . $datos[$key]['plan_codigo'] . ") - " . $plan_nombre . " - " . $datos[$key]['plan_estado_descripcion'];

			if (isset($datos[$key]['version_actual'])) {
				$datos[$key]['plan_datos'] .= ' - (versión actual: ' . $datos[$key]['version_actual_nombre'] . ')';
			}
		}
		$cuadro->set_datos($datos);

		$etiqueta = guarani_etiquetas::get_tabla('sga_planes');
		$cuadro->set_eof_mensaje(guarani_mensajes::get($this->indx_msg_eof, array($etiqueta->get_enf('plural'))));
	}

	function evt__cuadro__seleccion($seleccion)
	{
		$plan_version = $seleccion['plan_version'];
		$this->cn()->cargar($plan_version);
		$this->s__seleccion = $plan_version;
		$this->set_pantalla('edicion');
		toba::zona()->cargar($plan_version);
	}

	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		$msj_cambiar_plan = guarani_mensajes::get('800PRO_cambiar_alumnos_plan_version');
		// Al activar el plan se posibilita al usuario navegar hacia la operación "Cambiar Alumnos de Plan de Estudios".
		if ($this->s__navegar) {
			unset($this->s__navegar);
			echo "
				resp = confirm('$msj_cambiar_plan');
				if (resp) {
					document.location.href='$this->s__vinculo_cambiar_plan';
				} else {
					{$this->objeto_js}.set_evento(new evento_ei('cancelar'), true);
				} 
			";
		}
	}
}
?>