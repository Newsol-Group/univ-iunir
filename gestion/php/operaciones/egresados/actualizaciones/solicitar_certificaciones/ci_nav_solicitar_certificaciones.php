<?php
/**
 * 
 *
 * @package 	Egresados
 * @subpackage 	Actualizaciones
 * 
 */
class ci_nav_solicitar_certificaciones extends ci_nav_con_preseleccion
{
	protected $s__alumno;
	protected $s__tramites;
	protected $s__datos_alumno;
	
	protected $s__datos_resumen;
	protected $s__config_ha;

	function ini()
	{
		parent::set_concepto_preseleccion('Alumno');
	}

	function ini__operacion()
	{
		if (toba::memoria()->existe_dato('__config_ha__')) {
			toba::memoria()->eliminar_dato('__config_ha__');
		}
	}

	function set_preseleccion($seleccion)
	{
		$this->s__alumno = $seleccion['alumno'];
	}

	function get_preseleccion()
	{
		return $this->s__alumno;
	}

	function get_datos_alumno()
	{
		return $this->s__datos_alumno;
	}

	function get_tramites()
	{
		return $this->s__tramites;
	}

	function set_datos_resumen($datos_resumen)
	{
		$this->s__datos_resumen = $datos_resumen;
	}

	function get_datos_resumen()
	{
		return $this->s__datos_resumen;
	}

	function set_config_ha($config_ha)
	{
		$this->cn()->set_config_ha($config_ha);
	}

	function get_config_ha()
	{
        return $this->cn()->get_config_ha();
	}

	//-----------------------------------------------------------------------------------
	//---- Configuraciones --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf()
	{
		parent::conf();
		$this->set_sin_listado_inicial_preseleccion();
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA Preselección
	//-----------------------------------------------------------------------------------

	function conf__preseleccion(toba_ei_pantalla $pantalla)
	{
		$this->cn()->resetear_atributos_parametros();
	}

	function evt__cuadro_preseleccion__seleccion($seleccion)
	{
		$propuesta = $seleccion['propuesta'];
		unset($seleccion['propuesta']);
		parent::evt__cuadro_preseleccion__seleccion($seleccion);
		$this->cn()->cargar_atributos_parametros($propuesta);
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA Selección 
	//-----------------------------------------------------------------------------------

	function conf__seleccion(toba_ei_pantalla $pantalla)
	{
		$this->s__datos_alumno = toba::consulta_php('co_alumnos')->get_datos_basicos_alumno($this->get_preseleccion());
		$this->ev_info()->set_datos($this->s__datos_alumno);
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		$etiqueta = guarani_etiquetas::get_tabla('sga_certificados_otorg');
		$cuadro->set_eof_mensaje(guarani_mensajes::get($this->indx_msg_eof, array($etiqueta->get_enf('plural'))));

		$this->s__tramites = $this->cn()->get_listado_tramites_certificado($this->get_preseleccion());
		$cuadro->set_datos($this->s__tramites);
	}

	function conf_evt__cuadro__seleccion(toba_evento_usuario $evento, $fila)
	{
		// Sólo pueden seleccionarse Trámites de Solicitud de Certificado sin estado o en estado "Observado", "Adelantado".
		$datos = $this->dependencia('cuadro')->get_datos();
		if (empty($datos[$fila]['estado']) ||
				$datos[$fila]['estado'] == tramite_certificacion::estado_observado ||
				$datos[$fila]['estado'] == tramite_certificacion::estado_adelantado) {
			$evento->mostrar();
		} else {
			$evento->ocultar();
		}
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA Edición 
	//-----------------------------------------------------------------------------------

	function conf__edicion(toba_ei_pantalla $pantalla)
	{
		parent::conf__edicion($pantalla);
		if (guarani::parametros()->get_valor('egr_configurar_ha') == comunes::si) {
			$pantalla->eliminar_evento('guardar');
		} else {
			$pantalla->eliminar_evento('validar');
		}
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA Resumen 
	//-----------------------------------------------------------------------------------

	function conf__resumen(toba_ei_pantalla $pantalla)
	{
		$this->ev_info()->set_datos($this->s__datos_alumno);
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA Configuración Historia Académica 
	//-----------------------------------------------------------------------------------

	function conf__config_ha(toba_ei_pantalla $pantalla)
	{
		$this->ev_info()->set_datos($this->s__datos_alumno);
	}

	//-----------------------------------------------------------------------------------
	//---- ev_info ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_controlador($this);
			$this->ev_info->set_style('width: 75%;');
			$this->ev_info->set_columnas_a_mostrar(array('apellido_y_nombre', 'legajo', 'propuesta_nombre_abreviado', 'plan_nombre', 'ubicacion_nombre'));

			$this->ev_info->set_columnas_fila(array('apellido_y_nombre', 'legajo'), 1);
			$this->ev_info->set_columnas_fila(array('propuesta_nombre_abreviado', 'plan_nombre'), 2);
			$this->ev_info->set_columnas_fila(array('ubicacion_nombre'), 3);

			$this->ev_info->set_etiqueta('apellido_y_nombre', 'Alumno');
			$this->ev_info->set_etiqueta('legajo', 'Legajo');
			$this->ev_info->set_etiqueta('propuesta_nombre_abreviado', 'Propuesta');
			$this->ev_info->set_etiqueta('plan_nombre', 'Plan');
			$this->ev_info->set_etiqueta('ubicacion_nombre', 'Ubicación');
		}
		return $this->ev_info;
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__guardar()
	{
		// Si hay un trámite seleccionado => se trata de una edición.
		if (isset($this->s__seleccion)) {
			parent::evt__guardar();
		} else {
			$this->cn()->desactivar_transacciones_anidadas();
			
			//Si la solicitud no se pudo generar, devuelve false, sino un id de tramite.
			list($nro_solicitud, $estado_requisitos) = $this->cn()->generar_solicitud();
			if ($nro_solicitud) {
				if ($estado_requisitos == tramite_certificacion::estado_requisitos_aceptado) {
					// Si el alumno está en condiciones de obtener el certificado se navega a "Actualizar Trámite de Certificado".
					echo toba_js::abrir();
					echo "document.location.href='" .
					toba::vinculador()->crear_vinculo(toba_proyecto::get_id(), '32000019', array('nro_solicitud' => $nro_solicitud)) . "'\n";
					echo toba_js::cerrar();
				} else { // El estado de requisitos es "Pendiente".
					$datos_alumno = $this->get_datos_alumno();
					echo toba_js::abrir();
					echo "document.location.href='" .
					toba::vinculador()->crear_vinculo(toba_proyecto::get_id(), '5000111', array('persona' => $datos_alumno['persona'])) . "'\n";
					echo toba_js::cerrar();
				}
			} else {
				// Si el alumno NO está en condiciones de obtener el certificado se muestra un resumen de los requisitos no cumplidos.
				$this->set_datos_resumen($this->cn()->get_observaciones());
				$this->set_pantalla('resumen');
			}			
		}
	}

	function evt__validar()
	{
		$tramite = $this->cn()->entidad()->get_datos();
		$proceso_deteccion_egresado = toba::cn('cn_deteccion_egresado');

		/**
		 * Se inicializa (si corresponde según el parámetro 'egr_configurar_ha') 
		 * la estructura para manejar la configuración de exclusión de actividades 
		 * de la historia académica 
		 */
		if (guarani::parametros()->get_valor('egr_configurar_ha') == comunes::si) {
			$proceso_deteccion_egresado->inicializar_config_ha($tramite['alumno']);
		}

		if ($proceso_deteccion_egresado->validar($tramite['alumno'], $tramite['certificado'])) {
			// Si el alumno ESTÁ en condiciones de obtener el certificado, se muestra la historia académica para configuración.
			$this->set_config_ha($proceso_deteccion_egresado->get_config_ha());
			$this->set_pantalla('config_ha');
		} else {
			// Si el alumno NO está en condiciones de obtener el certificado, se muestra un resumen de los requisitos no cumplidos.
			$this->set_datos_resumen($proceso_deteccion_egresado->get_observaciones());
			$this->set_pantalla('resumen');
		}
	}

	function evt__cancelar_config_ha()
	{
		toba::memoria()->eliminar_dato('__config_ha__');
		$this->set_pantalla('edicion');
	}

	function evt__guardar_config_ha()
	{
		$this->cn()->desactivar_transacciones_anidadas();
		// Voy a ir a la misma función y dentro voy a validar el parametro egr_configurar_ha.
        list($nro_solicitud, $estado_requisitos) = $this->cn()->generar_solicitud();
        if ($nro_solicitud) {
			if ($estado_requisitos == tramite_certificacion::estado_requisitos_aceptado) {
				// Si el alumno está en condiciones de obtener el certificado se navega a "Actualizar Trámite de Certificado".
				echo toba_js::abrir();
				echo "document.location.href='" .
				toba::vinculador()->crear_vinculo(toba_proyecto::get_id(), '32000019', array('nro_solicitud' => $nro_solicitud)) . "'\n";
				echo toba_js::cerrar();
			} else { // El estado de requisitos es "Pendiente".
				$datos_alumno = $this->get_datos_alumno();
				echo toba_js::abrir();
				echo "document.location.href='" .
				toba::vinculador()->crear_vinculo(toba_proyecto::get_id(), '5000111', array('persona' => $datos_alumno['persona'])) . "'\n";
				echo toba_js::cerrar();
			}
		} else {
			// Si el alumno NO está en condiciones de obtener el certificado se muestra un resumen de los requisitos no cumplidos.
			toba::notificacion()->agregar('El alumno NO está en condiciones de obtener el certificado con la configuración de historia académica ingresada.', 'error');
		}
	}

	/*function evt__eliminar()
	{
		try {
			$this->cn()->desactivar_transacciones_anidadas();
			toba::db()->abrir_transaccion();

			$tramite = $this->cn()->entidad()->get_datos();
			guarani::act('act_tramites_certificacion')->eliminar_orientaciones_cumplidas($tramite['nro_solicitud']);
			guarani::act('act_tramites_certificacion')->eliminar_historia_academica($tramite['nro_solicitud']);
			guarani::act('act_tramites_certificacion')->eliminar_configuracion_ha($tramite['nro_solicitud']);

			$this->cn()->eliminar();

			toba::db()->cerrar_transaccion();

			toba::notificacion()->agregar($this->get_mensaje('eliminar_ok'), 'info');
			$this->set_pantalla('seleccion');
		} catch (guarani_error $e) {
			$e->notificar();
		} catch (toba_error $e) {
			toba::db()->abortar_transaccion();
			$this->cn()->resetear();
			$this->cn()->cargar($this->s__seleccion);
			toba::notificacion()->agregar($e->getMessage(), 'error');
			toba::logger()->error($e->getMessage());
		}
	}*/

	function es_titulo_final($certificado)
	{
		$datos = toba::consulta_php('co_certificados')->get_certificado($certificado);
		return $datos['titulo_nivel'] == tramite_certificacion::nivel_titulo_final;
	}

	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		echo "
		//---- Eventos ---------------------------------------------
		
		{$this->objeto_js}.evt__imprimir = function()
		{
			window.print();
			return false;
		}";

		try {
			if (guarani::parametros()->get_valor('egr_configurar_ha') == comunes::si) {
				echo "
				function config_ha_toggle(elemento)
				{
					var params = [];
					params['elemento'] = elemento;
					{$this->objeto_js}.ajax('config_ha_toggle', params, this, {$this->objeto_js}.respuesta_config_ha_toggle);

				}

				{$this->objeto_js}.respuesta_config_ha_toggle = function(respuesta) 
				{
					if (typeof respuesta == 'object') {
						var clase = 'ha_' + respuesta['elemento'];
						$('.' + clase).each(function() {
							this.innerHTML = respuesta['icono'];
						});
					}
				}
				";
			}
		} catch (guarani_error_params $e) {
			// Si no se puede determinar el valor del parámetro (por estar en una etapa temprana
			// de la operación), simplemente se ignora esta parte de la salida JavaScript
		}
	}

	//-----------------------------------------------------------------------------------
	//---- AJAX -------------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function ajax__config_ha_toggle($params, toba_ajax_respuesta $respuesta)
	{
		$elemento = $params['elemento'];
		if (toba::memoria()->existe_dato('__config_ha__')) {
			$config_ha = toba::memoria()->get_dato('__config_ha__');
			$config_ha[$elemento]['incluido_ha'] = !$config_ha[$elemento]['incluido_ha'];
		}

		toba::memoria()->set_dato('__config_ha__', $config_ha);

		$salida = array();
		$salida['elemento'] = $elemento;
		$salida['icono'] = $config_ha[$elemento]['incluido_ha'] ? toba_recurso::imagen_proyecto('check-si.png', true, null, null, 'Incluido en la historia académica') : toba_recurso::imagen_proyecto('check-no.png', true, null, null, 'NO incluido en la historia académica');

		$respuesta->set($salida);
	}

}