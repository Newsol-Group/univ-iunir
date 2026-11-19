<?php
/**
 *
 * 
 * @package		Reconocimiento de Actividades
 * @subpackage	Equivalencias
 * 
 */
class ci_nav_rectificar_equivalencia extends ci_navegacion
{
	protected $s__filtro_equivalencias;
	protected $s__datos_filtro_equivalencias;
	protected $s__datos_equivalencias;

	protected $guardar = false;
	protected $cerrar = false;

	function get_tramite()
	{
		return $this->s__seleccion;
	}

	function get_datos_tramite_original()
	{
		if (!isset($this->s__datos_equivalencias)) {
			$this->s__datos_equivalencias = array();
			if ($this->cn()->entidad_cargada()) {
				$datos = $this->cn()->entidad()->get_datos();
				$seleccion = $datos['equivalencia_tramite'];

				// Se guardan los datos del trámite original
				$filtro = " sga_equiv_tramite.equivalencia_tramite = '$seleccion'";
				$this->s__datos_equivalencias = current($this->cn()->get_listado($filtro));
			}
		}
		return $this->s__datos_equivalencias;
	}

	function limpiar()
	{
		unset($this->s__datos_equivalencias);
		unset($this->s__datos_filtro_equivalencias);
		unset($this->s__filtro_equivalencias);
	}

	function ini()
	{
		// Se setea no volver al guardar en el ci padre.
		parent::ini();
		$this->controlador()->set_no_volver_al_guardar();
	}

	function conf()
	{
		parent::conf();
		$this->indx_msg_eof = '800EQ_eof_cuadro_rectificativos_abiertos';
	}

	//--------------------------------------------------------------------------
	//---- Pantallas  ----------------------------------------------------------
	//--------------------------------------------------------------------------

	function conf__seleccion(toba_ei_pantalla $pantalla)
	{
		$this->cn()->resetear_atributos_parametros();
	}

	function conf__seleccion_agregar(toba_ei_pantalla $pantalla)
	{
		$this->cn()->resetear_atributos_parametros();
	}
	
	function conf__edicion(toba_ei_pantalla $pantalla)
	{
		if ($this->guardar) {
			$pantalla->eliminar_evento('guardar');
		}
		parent::conf__edicion($pantalla);
	}

	//--------------------------------------------------------------------------
	//---- filtro --------------------------------------------------------------
	//--------------------------------------------------------------------------

	function get_filtro_condicion()
	{
		$filtro = " sga_equiv_tramite.rectifica_a IS NOT NULL AND sga_equiv_tramite.estado = '" . equivalencia::estado_abierto . "'";
		$filtro .= ((isset($this->s__filtro)) && ($this->s__filtro != '')) ? ' AND ' . $this->s__filtro : '';
		return $filtro;
	}
	
	//--------------------------------------------------------------------------
	//---- cuadro --------------------------------------------------------------
	//--------------------------------------------------------------------------
	
	function evt__cuadro__seleccion($seleccion)
	{
		$propuesta = $seleccion['propuesta_alumno_id'];
		unset($seleccion['propuesta_alumno_id']);
		parent::evt__cuadro__seleccion($seleccion);
		$this->cn()->cargar_atributos_parametros($propuesta);
	}

	//--------------------------------------------------------------------------
	//---- filtro_equivalencias ------------------------------------------------
	//--------------------------------------------------------------------------

	function get_filtro_equivalencias_condicion()
	{
		// Se deben listar todos los trámites originales que no tengan rectificativas abiertas 
		$filtro = " sga_equiv_tramite.rectifica_a IS NULL AND sga_equiv_tramite.estado = '" . equivalencia::estado_cerrado . "' AND
					sga_equiv_tramite.equivalencia_tramite NOT IN 
															(SELECT sga_equiv_tramite.rectifica_a 
																FROM sga_equiv_tramite
																WHERE sga_equiv_tramite.rectifica_a IS NOT NULL AND 
																	  sga_equiv_tramite.estado = '" . equivalencia::estado_abierto . "')";
		$filtro .= ((isset($this->s__filtro_equivalencias)) && ($this->s__filtro_equivalencias != '')) ? ' AND ' . $this->s__filtro_equivalencias : '';
		return $filtro;
	}

	function evt__filtro_equivalencias__filtrar($datos)
	{
		if (isset($datos) && !empty($datos)) {
			$this->s__datos_filtro_equivalencias = $datos;
			$this->s__filtro_equivalencias = $this->dep('filtro_equivalencias')->get_sql_where();
		} else {
			unset($this->s__datos_filtro_equivalencias);
			$this->s__filtro_equivalencias = '';
		}
	}

	function evt__filtro_equivalencias__cancelar()
	{
		unset($this->s__datos_filtro_equivalencias);
		unset($this->s__filtro_equivalencias);
	}

	function conf__filtro_equivalencias(toba_ei_filtro $filtro)
	{
		if (isset($this->s__datos_filtro_equivalencias)) {
			$filtro->set_datos($this->s__datos_filtro_equivalencias);
		}
	}

	//--------------------------------------------------------------------------
	//---- cuadro_equivalencias ------------------------------------------------
	//--------------------------------------------------------------------------

	function evt__cuadro_equivalencias__seleccion($seleccion)
	{
		$seleccion = $seleccion['equivalencia_tramite'];

		// Se guardan los datos del trámite original
		$filtro = " sga_equiv_tramite.equivalencia_tramite = '$seleccion'";
		$this->s__datos_equivalencias = current($this->cn()->get_listado($filtro));
		$this->s__datos_equivalencias['rectifica_a'] = $seleccion;

		$datos = array();
		$datos['rectifica_a'] = $seleccion;
		$datos['alumno'] = $this->s__datos_equivalencias['alumno'];
		$datos['plan_version'] = $this->s__datos_equivalencias['plan_version'];
		$datos['fecha'] = $this->s__datos_equivalencias['fecha'];
		$datos['origen'] = $this->s__datos_equivalencias['origen'];
		$datos['documento'] = $this->s__datos_equivalencias['documento'];
		$datos['generacion_automatica'] = $this->s__datos_equivalencias['generacion_automatica'];
		$datos['propuesta'] = $this->s__datos_equivalencias['propuesta'];
		$datos['plan'] = $this->s__datos_equivalencias['plan'];
		$datos['institucion'] = $this->s__datos_equivalencias['institucion'];
		$datos['responsable_academica'] = $this->s__datos_equivalencias['responsable_academica'];
		$datos['convenio'] = $this->s__datos_equivalencias['convenio'];
		$this->cn()->entidad()->set_datos($datos);

		$this->cn()->cargar_atributos_parametros($this->s__datos_equivalencias['propuesta_alumno_id']);
		
		$this->set_pantalla('edicion');
	}

	function conf__cuadro_equivalencias(toba_ei_cuadro $cuadro)
	{
		// Si no está seteado el filtro, entonces es la carga inicial del cuadro (el usuario no presionó filtrar).
		// No se traen datos (no se ejecuta la consulta) y se pone un mensaje en el cuadro que indica al usuario
		// que debe filtrar para obtener datos.
		$this->indx_msj_eof = '800_eof_cuadro';
		if (!isset($this->s__filtro_equivalencias)) {
			$this->indx_msg_eof = '800_eof_cuadro_sinfiltro';
		} else {
			// El usuario presiono filtrar (ya sea con datos o con el filtro vacío).
			$datos = $this->cn()->get_listado($this->get_filtro_equivalencias_condicion());
			$cuadro->set_datos($datos);
			$this->indx_msg_eof = '800_eof_cuadro_filtrado';
		}		
		$cuadro->set_eof_mensaje($this->get_msg_eof());
	}
	
	function get_msg_eof($dep = null)
	{
		$indx_msg = $this->indx_msg_eof;
		$etiqueta = $this->cn()->etiquetas($dep)->get_enf('plural');
		return guarani_mensajes::get($indx_msg, array($etiqueta));
	}

	//--------------------------------------------------------------------------
	//---- Eventos -------------------------------------------------------------
	//--------------------------------------------------------------------------

	function evt__agregar()
	{
		parent::evt__agregar();
		$this->set_pantalla('seleccion_agregar');
	}

	function evt__cancelar()
	{
		$this->dep('editor')->limpiar_seleccion();
		$this->limpiar();
		parent::evt__cancelar();
	}

	function evt__guardar()
	{
		$datos = $this->cn()->entidad()->get_datos();
		$datos['tipo_tramite'] = equivalencia::tipo_tramite_rectificativo;
		$persona = toba::consulta_php('co_personas')->get_persona_x_usuario(toba::usuario()->get_id());
		$datos['operador'] = $persona['persona'];
		$datos['fecha_cierre'] = null;
		$datos['estado'] = equivalencia::estado_abierto;
		$datos['digital'] = guarani::parametros()->get_valor(equivalencia::equiv_utiliza_acta_digital);
		$this->cn()->entidad()->set_datos($datos);

		// Si el trámite va a cerrarse, se envía un flag al guardar del cn man, para que no se ejecuten los
		// controles, ya que se ejecutarán al llaman al método del cn man de Cerrar Tra?ites de Equivalencia.
		$a_cerrar = guarani::parametros()->get_valor('equiv_cerrar_tramite_automaticamente') == comunes::si || $this->cerrar;
		$this->cn()->guardar(!$a_cerrar);
		$datos = $this->cn()->entidad()->get_datos();

		if ($a_cerrar) {
			$cn_manejador_cerrar_equivalencia = toba::cn('manejador_cerrar_equivalencia');
			$equiv_utiliza_acta_digital = $datos['digital'];
			$cn_manejador_cerrar_equivalencia->cerrar_tramite_equivalencia($datos['equivalencia_tramite'], $equiv_utiliza_acta_digital);
			$this->cerrar = false;
		}

		$this->s__seleccion = $datos['equivalencia_tramite'];
		$this->dep('editor')->habilitar_imprimir();
		$this->guardar = true;
		$this->dep('editor')->set_pantalla('resumen');
	}

	function evt__cerrar_tramite()
	{
		$this->cerrar = true;
		$this->evt__guardar();
	}

	//--------------------------------------------------------------------------
	//---- JAVASCRIPT ----------------------------------------------------------
	//--------------------------------------------------------------------------

	function extender_objeto_js()
	{
		parent::extender_objeto_js();

		if ($this->get_id_pantalla() == 'edicion') {
			try {
				if (guarani::parametros()->get_valor('equiv_cerrar_tramite_automaticamente') == comunes::opcional) {
					$msj = guarani_mensajes::get('800EQU_confirmar_cierre');
					echo "
						{$this->objeto_js}.evt__guardar = function()
						{
							if (confirm('$msj')) {
								this.set_evento(new evento_ei('cerrar_tramite',true,''));
							}
						}
					";
				}
			} catch (guarani_error_params $e) {
				// Si todavía no se puede determinar el valor del parámetro, 
				// se omite pospone la salida JavaScript asociada
			}
		}
	}

}