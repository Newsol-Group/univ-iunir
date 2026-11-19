<?php
/**
 *
 *
 * @package 	Reconocimiento de Actividades
 * @subpackage 	Otorgar Equivalencia
 * 
 */
class ci_edi_otorgar_equivalencia extends ci_edicion_simple
{
	protected $s__datos_temp;
	protected $s__imprimir = false;

	function get_datos_basicos()
	{
		return $this->s__datos_temp;
	}

	function get_tramite_equiv_seleccionado()
	{
		return $this->controlador->get_tramite();
	}

	function habilitar_imprimir()
	{
		$this->s__imprimir = true;
	}

	function conf()
	{
		if (!$this->s__imprimir) {
			$this->pantalla()->tab('resumen')->anular();
		} else {
			$this->pantalla()->tab('tipo_equivalencia')->anular();
			$this->pantalla()->tab('cargar_equivalencia')->anular();
		}
	}

	function evt__cargar_equivalencia__salida()
	{
		$this->dep('edicion_equivalencia')->evt__edicion__salida();
	}

	
	//-----------------------------------------------------------------------------------
	//---- PANTALLA TIPO EQUIVALENCIA 
	//-----------------------------------------------------------------------------------
	
	//-----------------------------------------------------------------------------------
	//---- form_tipo_equiv --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_tipo_equiv(toba_ei_formulario $form)
	{
		if (isset($this->s__datos_temp)) {
			$this->controlador->set_hubo_cambios();
		} else {
			// Datos para el form
			$this->s__datos_temp = $this->cn()->entidad()->get_datos();
			if ((!is_null($this->s__datos_temp)) && (isset($this->s__datos_temp['alumno']))) {
				$this->s__datos_temp['alumno_nombre'] = $this->s__datos_temp['alumno'];
				$where = "sga_alumnos.alumno = " . $this->s__datos_temp['alumno'];
				$datos_alumno = current(guarani::co('co_alumnos')->get_listado($where));
				$this->s__datos_temp['alumno_id'] = $datos_alumno['documento_principal'];
				$this->s__datos_temp['alumno_propuesta'] = $datos_alumno['propuesta_nombre_abreviado'];
				$this->s__datos_temp['alumno_plan'] = $datos_alumno['plan_nombre'];
				$this->s__datos_temp['alumno_version'] = $datos_alumno['plan_version_nombre'];
				$this->s__datos_temp['cod_plan_version_origen'] = $datos_alumno['plan_version'];
				$this->s__datos_temp['tipo_equivalencia'] = $this->s__datos_temp['origen'];
				if ($this->s__datos_temp['tipo_equivalencia'] == equivalencia::origen_int_prop) {
					$this->s__datos_temp['propuesta_cambio'] = $this->s__datos_temp['propuesta'];
					$this->s__datos_temp['plan_cambio'] = $this->s__datos_temp['plan'];
				}
				$this->s__datos_temp['alumno_legajo'] = $datos_alumno['legajo'];
			}
		}
		$form->set_datos($this->s__datos_temp);
	}

	function evt__form_tipo_equiv__modificacion($datos)
	{
		if ($datos) {
			// Se chequeo si cambiaron los datos del alumno seleccionado
			if (isset($datos['alumno_nombre']) && (is_array($datos['alumno_nombre']))) {
				$cod_plan = $datos['alumno_nombre'][3];
				$nombre = $datos['alumno_nombre'][0];
				$alumno = $datos['alumno_nombre'][0];
			} else {
				$cod_plan = $this->s__datos_temp['cod_plan_version_origen'];
				$nombre = $this->s__datos_temp['alumno_nombre'];
				$alumno = $this->s__datos_temp['alumno'];
			}

			// Se guardan los datos
			if ($datos['tipo_equivalencia'] == equivalencia::origen_int_prop) {
				$datos['propuesta'] = $datos['propuesta_cambio'];
				$datos['plan'] = $datos['plan_cambio'];
			}
			$this->s__datos_temp = $datos;
			$this->s__datos_temp['cod_plan_version_origen'] = $cod_plan;
			$this->s__datos_temp['alumno_nombre'] = $nombre;
			$this->s__datos_temp['alumno'] = $alumno;

			// Se preparan los datos a guardar en la BD
			$datos_new = $datos;
			$datos_new['alumno'] = $nombre;
			$datos_new['origen'] = $datos_new['tipo_equivalencia'];
			$datos_new['plan_version'] = $cod_plan;
			if ($datos['tipo_equivalencia'] == equivalencia::origen_int_prop) {
				unset($datos_new['propuesta_cambio']);
				unset($datos_new['plan_cambio']);
			}

			unset($datos_new['tipo_equivalencia']);
			unset($datos_new['alumno_nombre']);
			unset($datos_new['alumno_id']);
			unset($datos_new['alumno_propuesta']);
			unset($datos_new['alumno_plan']);
			unset($datos_new['alumno_version']);
			
			try {
				$this->cn()->entidad()->set_datos($datos_new);
			} catch (guarani_error $e) {
				$this->s__datos_temp = $datos;
				throw $e;
			}
		}
	}

	function evt__form_tipo_equiv__cambio($datos)
	{
		$this->cn()->entidad()->eliminar_otorgadas();

		$this->evt__form_tipo_equiv__modificacion($datos);
	}

	//-----------------------------------------------------------------------------------
	//---- Validación de salida ---------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function validar_tipo_equivalencia()
	{
		// Se valida, si se elige convenio, que el mismo contenga actividades, si no no es posible avanzar
		if ($this->s__datos_temp['tipo_equivalencia'] == equivalencia::origen_ext_convenio) {
			if (is_null($this->s__datos_temp['convenio'])) {
				$mje = guarani_mensajes::get('800EQU_convenio_actividad_error');
				throw guarani::error($mje);
			}
			// Se obtienen las actividades para el convenio seleccionado
			$activ_conv = toba::consulta_php('co_convenios')->get_actividades_convenio($this->s__datos_temp['convenio']);
			$in = guarani_sql::armar_condicion_in($activ_conv, 'sga_elementos', 'elemento');
			$lista_actividades = toba::consulta_php('co_elementos')->get_actividades_plan_version($this->s__datos_temp['cod_plan_version_origen'], $in);
			if (empty($lista_actividades)) {
				$mje = guarani_mensajes::get('800EQU_convenio_actividad_error');
				throw guarani::error($mje);
			} else {
				toba::memoria()->eliminar_dato('plan_version');
				toba::memoria()->eliminar_dato('alumno');
				toba::memoria()->set_dato('lista_actividades', $lista_actividades);
			}
		}
	}

	function evt__tipo_equivalencia__salida()
	{
		$this->validar_tipo_equivalencia();
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA cargar_equivalencia 
	//-----------------------------------------------------------------------------------
	
	//-----------------------------------------------------------------------------------
	//---- Validación de salida ---------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function validar_cargar_equivalencia()
	{
		// Se valida que se haya seleccionado alguna cargar_equivalencia
		if ($this->falta_seleccionar('id')) {
			$mje = guarani_mensajes::get('800_no_seleccion', array('alguna cargar_equivalencia'));
			throw guarani::error($mje);
		}

		$this->cn()->set_cargar_equivalencia($this->get_seleccion_valor('id'));
	}

	function limpiar_seleccion()
	{
		$this->s__imprimir = false;
		unset($this->s__datos_temp);
	}

	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT  ------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function ajax__validar_cambio_alumno($params, toba_ajax_respuesta $respuesta)
	{
		$alumno = current($params);
		$equivalencia = $this->cn()->entidad()->get_datos();
		$salida = false;

		if ((isset($equivalencia['alumno'])) && ($alumno != $equivalencia['alumno'])) {
			$salida['mensaje'] = guarani_mensajes::get('800EQU_confirmar_cambio');
			$where = "sga_alumnos.alumno = " . $equivalencia['alumno'];
			$datos_alumno = current(guarani::co('co_alumnos')->get_listado($where));
			$salida['alumno_nombre_clave'] = $datos_alumno['alumno'];
			$salida['alumno_nombre_descr'] = $datos_alumno['apellido_y_nombre'];
			$salida['alumno_id'] = $datos_alumno['documento_principal'];
			$salida['alumno_propuesta'] = $datos_alumno['propuesta_nombre_abreviado'];
			$salida['alumno_plan'] = $datos_alumno['plan_nombre'];
			$salida['alumno_version'] = $datos_alumno['plan_version_nombre'];
			$salida['alumno_legajo'] = $datos_alumno['legajo'];
		}
		$respuesta->set($salida);
	}

	function ajax__datos_alumno($params, toba_ajax_respuesta $respuesta)
	{
		$alumno = current($params);
		$where = "sga_alumnos.alumno = " . toba::db()->quote($alumno);
		$datos_alumno = current(guarani::co('co_alumnos')->get_listado($where));
		$salida = empty($datos_alumno['legajo']) ? array('alumno_legajo' => null) : array('alumno_legajo' => $datos_alumno['legajo']);
		$salida['alumno_id'] = $datos_alumno['documento_principal'];
		$salida['alumno_propuesta'] = $datos_alumno['propuesta_nombre_abreviado'];
		$salida['alumno_plan'] = $datos_alumno['plan_nombre'];
		$salida['alumno_version'] = $datos_alumno['plan_version_nombre'];
		$respuesta->set($salida);
	}

	function ajax__validar_cambio_tipo($params, toba_ajax_respuesta $respuesta)
	{
		$tipo = current($params);
		$equivalencia = $this->cn()->entidad()->get_datos();
		$salida = false;
		if ((isset($equivalencia['origen'])) && ($tipo != $equivalencia['origen'])) {
			$salida['mensaje'] = guarani_mensajes::get('800EQU_confirmar_cambio');
			$salida['estado'] = $equivalencia['origen'];
		}
		$respuesta->set($salida);
	}

	//-----------------------------------------------------------------------------------
	//---- Configuraciones --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__resumen(toba_ei_pantalla $pantalla)
	{
		$this->controlador()->evento('cancelar')->set_etiqueta('Ir al inicio');
		$this->controlador()->evento('cancelar')->set_imagen('/home.png');
	}

	//-----------------------------------------------------------------------------------
	//---- COMBOS -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function get_propuestas_alumno($alumno)
	{
		$alumno = explode('||', $alumno);
		$datos_alumno = current(toba::consulta_php('co_alumnos')->get_datos_alumno($alumno[0]));
		$filtro = "sga_propuestas.propuesta = {$datos_alumno['propuesta']}";
		return toba::consulta_php('co_propuestas')->get_listado_nombres_propuestas($filtro);
	}

	function get_propuestas_alumno_cambio($filtro_propuesta='', $alumno)
	{
		$filtro = 'true';
		if (isset($filtro_propuesta) && trim($filtro_propuesta) != '') {
			$filtro_propuesta = guarani_texto::limpiar_acentos($filtro_propuesta);
			$filtro_condicion = new toba_filtro_condicion('contiene', 'ILIKE', '%', '%', '', '');
			$filtro .= ' AND ' . $filtro_condicion->get_sql('f_limpiar_acentos(sga_propuestas.nombre::varchar)', $filtro_propuesta);
		}
		$alumno = explode('||', $alumno);
		$datos_alumno = current(toba::consulta_php('co_alumnos')->get_datos_alumno($alumno[0]));
		$filtro .= " AND sga_propuestas.propuesta <> {$datos_alumno['propuesta']}";
		return toba::consulta_php('co_propuestas')->get_listado_nombres_propuestas($filtro);
	}

	function get_planes_alumno_tipo_propuesta($alumno, $tipo_equivalencia, $propuesta)
	{
		$alumno = explode('||', $alumno);
		$datos_alumno = current(toba::consulta_php('co_alumnos')->get_datos_alumno($alumno[0]));
		$filtro = '';
		if ($tipo_equivalencia == equivalencia::origen_int_plan) {
			$filtro = "sga_planes.plan <> {$datos_alumno['plan']}";
		} elseif ($tipo_equivalencia == equivalencia::origen_int_version) {
			$filtro = "sga_planes.plan = {$datos_alumno['plan']}";
		}
		return toba::consulta_php('co_planes')->get_planes_propuesta($propuesta, $filtro);
	}

	function get_versiones_alumno_plan($alumno, $plan)
	{
		$alumno = explode('||', $alumno);
		$datos_alumno = current(toba::consulta_php('co_alumnos')->get_datos_alumno($alumno[0]));
		$filtro = "sga_planes_versiones.plan_version <> {$datos_alumno['plan_version']}";
		return toba::consulta_php('co_planes')->get_versiones_por_plan($plan, $filtro);
	}

	function get_alumno_nombre($id)
	{
		return toba::consulta_php('co_alumnos')->get_alumno_nombre($id);
	}
}
?>