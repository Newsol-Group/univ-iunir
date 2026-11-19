<?php
/**
 *
 *
 * @package 	Reconocimiento de Actividades
 * @subpackage 	Otorgar Equivalencia
 * 
 */
class ci_nav_otorgar_equivalencia extends ci_navegacion
{
	//Selección Trámites
	protected $guardar = false;
	protected $cerrar = false;
	
	function get_tramite()
	{
		return $this->s__seleccion;
	}

	function ini()
	{
		// Se setea no volver al guardar en el ci padre.
		parent::ini();
		$this->controlador()->set_no_volver_al_guardar();
	}

	function conf__seleccion(toba_ei_pantalla $pantalla)
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

	//-----------------------------------------------------------------------------------
	//------ filtro ---------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function get_filtro_condicion()
	{
		$filtro = "sga_equiv_tramite.estado = '" . equivalencia::estado_abierto . "'";
		$filtro .= ((isset($this->s__filtro)) && ($this->s__filtro != '')) ? ' AND ' . $this->s__filtro : '';
		return $filtro;
	}
	
	//-----------------------------------------------------------------------------------
	//------ cuadro ---------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function evt__cuadro__seleccion($seleccion)
	{
		$propuesta = $seleccion['propuesta_alumno_id'];
		$plan = $seleccion['plan_alumno_id'];
		unset($seleccion['propuesta_alumno_id']);
		unset($seleccion['plan_alumno_id']);
		
		parent::evt__cuadro__seleccion($seleccion);
		
		$this->cn()->entidad()->cargar_atributos_parametros($propuesta, $plan);
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__cancelar()
	{
		$this->dep('editor')->limpiar_seleccion();
		parent::evt__cancelar();
	}

	function evt__guardar()
	{
		// Se chequea si se debe cerrar el tramite automaticamente
		$datos = $this->cn()->entidad()->get_datos();
		if (guarani::parametros()->get_valor('equiv_cerrar_tramite_automaticamente') == comunes::si) {
			$datos['estado'] = equivalencia::estado_cerrado;
			$datos['fecha_cierre'] = guarani_fecha::get_hoy();
		} else {
			if ($this->cerrar) {
				$datos['estado'] = equivalencia::estado_cerrado;
				$datos['fecha_cierre'] = guarani_fecha::get_hoy();
				$this->cerrar = false;
			} else {
				$datos['estado'] = equivalencia::estado_abierto;
				$datos['fecha_cierre'] = null;
			}
		}

		$datos['digital'] = guarani::parametros()->get_valor(equivalencia::equiv_utiliza_acta_digital);
		$utiliza_acta_digital = ($datos['digital'] == comunes::si);
		$ras = toba::consulta_php('co_alumnos')->get_ras_alumno($datos['alumno']);
		
		/**
		 * @see https://redmine.siu.edu.ar/redmine/issues/31318 Ticket #31318
		 */
		if ($utiliza_acta_digital && ($datos['estado'] == equivalencia::estado_cerrado)) {
			comunes::verificar_jasper_corriendo();
			arai_documentos::validar_cuenta_usuario_origen_arai_documentos();
			equivalencia::validar_autorizantes_asociados_acta($ras);
		}

		$persona = toba::consulta_php('co_personas')->get_persona_x_usuario(toba::usuario()->get_id());
		$datos['operador'] = $persona['persona'];
		$this->cn()->entidad()->set_datos($datos);

		// Los controles correspondientes se ejecutan en el cn manejador.
		parent::evt__guardar();
		$datos = $this->cn()->entidad()->get_datos();
		$this->s__seleccion = $datos['equivalencia_tramite'];
		
		/**
		 * Envía a SIU-Araí Documentos.
		 * @see https://redmine.siu.edu.ar/redmine/issues/31318 Ticket #31318
		 */
		if ($datos['estado'] == equivalencia::estado_cerrado) {
			$parametros = [];
			$parametros['utiliza_acta_digital'] = $datos['digital'];
			equivalencia::enviar_tramite_equivalencia_arai_documentos($datos['equivalencia_tramite'], $parametros, $ras);
		}
		
		$this->dep('editor')->habilitar_imprimir();
		$this->guardar = true;
		$this->dep('editor')->set_pantalla('resumen');
	}

	function evt__cerrar_tramite()
	{
		$this->cerrar = true;
		$this->evt__guardar();
	}

	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		parent::extender_objeto_js();

		if ($this->get_id_pantalla() == 'edicion') {
			try {
				if (guarani::parametros()->get_valor('equiv_cerrar_tramite_automaticamente') == comunes::opcional) {

					$msj_confirmacion = guarani_mensajes::get('800EQU_confirmar_cierre');
					$msj_guardar_error = guarani_mensajes::get('800EQU_falta_equivalencia');

					// Verifica la cantidad de equivalencias cargadas para otorgar.
					$equiv_otorgada = $this->cn()->entidad()->get_datos_equiv_otorgada();
					$cant_equiv = guarani_arreglos::count($equiv_otorgada);

					echo "

						{$this->objeto_js}.evt__guardar = function()
						{
							/**
							 * Verifica si hay equivalencias para otorgar cargadas (actividades). En ese caso se consulta 
							 * al usuario si desea Cerrar el trámite que dio de Alta (el trámite se almacenará en estado 
							 * Abierto o Cerrado según la respuesta). En caso contrario se emite un msj de error apropiado
							 * sin Guardar el trámite.
							 */
							if ($cant_equiv > 0) {
								if (confirm('$msj_confirmacion')) {
									this.set_evento(new evento_ei('cerrar_tramite', true, ''));
								} else {
									return true;
								}
							} else {
								notificacion.agregar('$msj_guardar_error');
								notificacion.mostrar();
								return false;
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