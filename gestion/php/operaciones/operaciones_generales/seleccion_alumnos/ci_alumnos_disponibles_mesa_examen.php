<?php
class ci_alumnos_disponibles_mesa_examen extends guarani_pers_ci
{
	protected $s__datos;
	protected $s__filtro_mesa;
	protected $s__datos_filtro_otros;
	protected $s__filtro_otros;
	protected $s__inscripto_instancia;
	
	//-----------------------------------------------------------------------------------
	//---- Auxiliares -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function controlador_edicion()
	{
		return $this->controlador->controlador();
	}

	function resetear_variables()
	{
		unset($this->s__datos);
		unset($this->s__filtro_mesa);
		unset($this->s__datos_filtro_otros);
		unset($this->s__filtro_otros);
		unset($this->s__inscripto_instancia);
	}

	//-----------------------------------------------------------------------------------
	//---- Configuraciones --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf()
	{
		/**
		 * Si el parámetro de sistema indica que no es posible agregar alumnos no inscriptos al acta, se desactiva el tab
		 * correspondiente en el selector de alumnos disponibles para agregar a la mesa.
		 */
		if (guarani::parametros()->get_valor('exa_agregar_alumnos_no_inscriptos') == comunes::no) {
			$this->pantalla()->eliminar_tab('otros');
		}
	}

	//-----------------------------------------------------------------------------------
	//---- filtro_mesa ------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__filtro_mesa(guarani_ei_filtro $filtro)
	{
		$filtro->columna('con_nota')->set_condicion_fija('es_igual_a');
		if (isset($this->s__datos)) {
			$filtro->set_datos($this->s__datos);
		} else {
			$filtro->colapsar();
		}
	}

	function evt__filtro_mesa__filtrar($datos)
	{
		if (isset($datos) && !empty($datos)) {
			$this->s__datos = $datos;
			$filtro_nota = '';
			if (isset($datos['con_nota'])) {
				if ($datos['con_nota']['valor'] == comunes::si) {
					$filtro_nota .= " AND sga_eval_detalle_examenes.nota IS NOT NULL";
				} else {
					$filtro_nota .= " AND sga_eval_detalle_examenes.nota IS NULL";
				}
				$this->dep('filtro_mesa')->columna('con_nota')->resetear_estado();
			}
			$this->s__filtro_mesa = "{$this->dep('filtro_mesa')->get_sql_where()} $filtro_nota";
		} else {
			unset($this->s__datos);
			$this->s__filtro_mesa = '';
		}
	}

	function evt__filtro_mesa__cancelar()
	{
		unset($this->s__datos);
		unset($this->s__filtro_mesa);
	}

	//-----------------------------------------------------------------------------------
	//---- filtro_otros -----------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__filtro_otros(guarani_ei_filtro $filtro)
	{
		if (isset($this->s__datos_filtro_otros)) {
			$filtro->set_datos($this->s__datos_filtro_otros);
		}
	}

	function evt__filtro_otros__filtrar($datos)
	{
		if (isset($datos)) {
			$this->s__datos_filtro_otros = $datos;
			$this->s__filtro_otros = $this->dep('filtro_otros')->get_sql_where();
		} else {
			unset($this->s__datos_filtro_otros);
			$this->s__filtro_otros = '';
		}
	}

	function evt__filtro_otros__cancelar()
	{
		unset($this->s__datos_filtro_otros);
		unset($this->s__filtro_otros);
	}

    
	function conf__otros(toba_ei_pantalla $pantalla)	
    {
        $sel_auto = guarani::parametros()->get_valor('exa_instancia_automatica');
		if ($sel_auto == comunes::si) {
            $pantalla->eliminar_dep('inscripto_para');
        }        
	}
    
	//-----------------------------------------------------------------------------------
	//---- inscripto_para ---------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__inscripto_para(toba_ei_formulario $form)
	{
		
	}

	function evt__inscripto_para__modificacion($datos)
	{		
        //Se setea la instancia que se eligió en el cn.
        $this->controlador_edicion()->set_instancia($datos['instancia']);
	}

	//-----------------------------------------------------------------------------------
	//---- mesa_disponible --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__mesa_disponibles(toba_ei_cuadro $cuadro)
	{
		if (empty($this->s__filtro_mesa)) {
			$cuadro->set_eof_mensaje(guarani_mensajes::get('800_sin_alumnos_a_seleccionar'));
		} else {
			$etiqueta = guarani_etiquetas::get_tabla('sga_alumnos');
			$indx_msj = guarani_mensajes::get('800_eof_cuadro_filtrado', array($etiqueta->get_enf('plural')));
			$cuadro->set_eof_mensaje($indx_msj);
		}

		$filtro = empty($this->s__filtro_mesa) ? 'true' : $this->s__filtro_mesa;
		$alumnos_asociados = $this->controlador_edicion()->get_alumnos_asociados();

		if (!empty($alumnos_asociados)) {
			$filtro .= ' AND ' . guarani_sql::armar_condicion_in($alumnos_asociados, 'sga_alumnos', 'alumno', null, true);
			// Agrego filtro para que no traiga alumnos repetidos en otras propuestas
			$personas_filtro = guarani_sql::armar_condicion_in($alumnos_asociados, 'sga_alumnos', 'alumno', 'alumno');
			$personas = guarani::co('co_personas')->get_personas_alumnos($personas_filtro);
			$filtro .= ' AND ' . guarani_sql::armar_condicion_in($personas, 'sga_alumnos', 'persona', 'persona', true);
		}

		$consulta = $this->controlador_edicion()->get_alumnos_mesa_disponibles();
		if (isset($consulta['parametros']['filtro'])) {
			$consulta['parametros']['filtro'] = $filtro . $consulta['parametros']['filtro'];
		} else {
			$consulta['parametros']['filtro'] = $filtro;
		}
		$alumnos = call_user_func_array(array(toba::consulta_php($consulta['clase_co']), $consulta['metodo']), $consulta['parametros']);
		if (empty($alumnos)) {
			$cuadro->evento('agregar')->desactivar();
		}
		$cuadro->set_datos($alumnos);
	}

	function conf_evt__mesa_disponibles__seleccion(toba_evento_usuario $evento, $fila)
	{
		$datos = $this->dep('mesa_disponibles')->get_datos();
		if (isset($datos[$fila]['invalidado']) && $datos[$fila]['invalidado'] == comunes::si_desc) {
			$evento->anular();
		}
	}

	function evt__mesa_disponibles__seleccionar($seleccion)
	{
		$this->alumnos_seleccionados = $seleccion;
	}

	function evt__mesa_disponibles__agregar()
	{
		$this->controlador_edicion()->agregar_alumnos_mesa($this->alumnos_seleccionados);
	}    
    
	function conf_evt__mesa_disponibles__eliminar(toba_evento_usuario $evento, $fila)
	{
		$datos = $this->dep('mesa_disponibles')->get_datos();
		if (isset($datos[$fila]['inscripto']) && $datos[$fila]['inscripto'] == comunes::si_desc) {
			$evento->anular();
		}
		if (isset($datos[$fila]['en_acta']) && $datos[$fila]['en_acta'] == comunes::si_desc) {
			$evento->anular();
		}
		
		$evento->set_msg_confirmacion(toba::mensajes()->get('800EXA_sincronizar_eliminar'));
	}

	function evt__mesa_disponibles__eliminar($seleccion)
	{
        $this->controlador_edicion()->eliminar_alumno_disponible($seleccion['alumno']);
	}

	//-----------------------------------------------------------------------------------
	//---- otros_disponibles ------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__otros_disponibles(toba_ei_cuadro $cuadro)
	{
		if (empty($this->s__filtro_otros)) {
			$cuadro->set_eof_mensaje(guarani_mensajes::get('800_eof_cuadro_sinfiltro'));
		} else {
			$etiqueta = guarani_etiquetas::get_tabla('sga_alumnos');
			$indx_msj = guarani_mensajes::get('800_eof_cuadro_filtrado', array($etiqueta->get_enf('plural')));
			$cuadro->set_eof_mensaje($indx_msj);

			$filtro = empty($this->s__filtro_otros) ? 'true' : $this->s__filtro_otros;

			// Al conjunto de "otros alumnos" se restan los asociados y los alumnos de la mesa (porque ya se muestran en el otro cuadro).
			$alumnos_asociados = $this->controlador_edicion()->get_alumnos_asociados();
			if (!empty($alumnos_asociados)) {
				$filtro .= ' AND ' . guarani_sql::armar_condicion_in($alumnos_asociados, 'sga_alumnos', 'alumno', 'alumno', true);
				// Agrego filtro para que no traiga alumnos repetidos en otras propuestas
				$personas_filtro = guarani_sql::armar_condicion_in($alumnos_asociados, 'sga_alumnos', 'alumno', 'alumno');
				$personas = guarani::co('co_personas')->get_personas_alumnos($personas_filtro);
				$filtro .= ' AND ' . guarani_sql::armar_condicion_in($personas, 'sga_alumnos', 'persona', 'persona', true);
			}
			$alumnos_mesa = $this->dep('mesa_disponibles')->get_datos();
			if (!empty($alumnos_mesa)) {
				$filtro .= ' AND ' . guarani_sql::armar_condicion_in($alumnos_mesa, 'sga_alumnos', 'alumno', 'alumno', true);
				// Agrego filtro para que no traiga alumnos repetidos en otras propuestas
				$personas_filtro = guarani_sql::armar_condicion_in($alumnos_mesa, 'sga_alumnos', 'alumno', 'alumno');
				$personas = guarani::co('co_personas')->get_personas_alumnos($personas_filtro);
				$filtro .= ' AND ' . guarani_sql::armar_condicion_in($personas, 'sga_alumnos', 'persona', 'persona', true);
			}

			$consulta = $this->controlador_edicion()->get_alumnos_otros_disponibles();
			if (isset($consulta['parametros']['filtro'])) {
				$consulta['parametros']['filtro'] = $filtro . $consulta['parametros']['filtro'];
			} else {
				$consulta['parametros']['filtro'] = $filtro;
			}
			$consulta['parametros'][] = $filtro;
			$alumnos = call_user_func_array(array(toba::consulta_php($consulta['clase_co']), $consulta['metodo']), $consulta['parametros']);
			$cuadro->set_datos($alumnos);
		}
	}

	function evt__otros_disponibles__seleccion($seleccion)
	{
		$this->controlador_edicion()->agregar_alumno_otros($seleccion);
	}

	function get_instancias_mesas_examen()
	{
		$filtro = 'sga_mesas_examen_instancias.mesa_examen = ' . $this->controlador_edicion()->get_mesa_examen();
		return toba::consulta_php('co_mesas_de_examen')->get_instancias_mesas_examen($filtro);
	}

	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		parent::extender_objeto_js();
		$msj_confirm_filtrar = guarani_mensajes::get('800_confirmar_filtro_pierde_datos');
		/**
		 * Se realiza la advertencia de perdida de datos en los cuadros multiselección al filtrar, para la pantalla
		 * de alumnos disponibles de la mesa. Para la pantalla de otros alumnos no es necesario debido a que el cuadro
		 * no es multiselección y se agrega de a un alumno.
		 */
		if ($this->get_id_pantalla() == 'mesa_examen') {
			$id_js_filtro = $this->dependencia('filtro_mesa')->get_id_objeto_js();
			$id_js_cuadro = $this->dependencia('mesa_disponibles')->get_id_objeto_js();

			echo "
				{$id_js_filtro}.evt__filtrar = function() {
					//La advertencia sólo desea emitirse en caso de que el cuadro tenga filas seleccionadas.
					filas_seleccionadas = {$id_js_cuadro}.get_ids_seleccionados('seleccion');
					if (filas_seleccionadas.length > 0) {
						return confirmar_filtrado();
					}
				}
				
				function confirmar_filtrado(proyecto, operacion, url, es_popup) {
					if (!es_popup || typeof(es_popup) == 'undefined') {
						resp = confirm('$msj_confirm_filtrar');
						if (resp) {
							return true;
						} else {
							return false;
						}
					}
					return true;
				}
			";
		}
        else {
            if ($this->pantalla()->existe_dependencia('inscripto_para')) {
                $id_js_cuadro_otros = $this->dependencia('otros_disponibles')->get_id_objeto_js();
                $id_js_inscripto_para = $this->dependencia('inscripto_para')->get_id_objeto_js();
                echo "
                    {$id_js_cuadro_otros}.evt__seleccion = function() {
                        if(!$id_js_inscripto_para.ef('instancia').tiene_estado()) {
                            var error = 'Debe seleccionar la instancia para agregar el alumno al acta';
                            notificacion.agregar('<b>Inscripto Para: </b>' + error);
                            $id_js_inscripto_para.ef('instancia').resaltar(error);
                            return false;
                        }
                        return true;
                    }
                ";
            }
        }
	}

}
?>