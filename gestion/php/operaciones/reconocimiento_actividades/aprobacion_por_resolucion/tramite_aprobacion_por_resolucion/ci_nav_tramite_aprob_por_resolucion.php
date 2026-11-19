<?php
/**
 *
 * 
 * @package 	Reconocimiento de Actividades
 * @subpackage 	Aprobación por Resolución
 * 
 */
class ci_nav_tramite_aprob_por_resolucion extends ci_navegacion
{
	function get_tramite_aprobacion_seleccionado()
	{
		return current($this->s__seleccion);
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA DE SELECCIÓN 
	//-----------------------------------------------------------------------------------

	function conf__seleccion(toba_ei_pantalla $pantalla)
	{
		$this->cn()->resetear_atributos_parametros();
	}
	
	//-----------------------------------------------------------------------------------
	//---- filtro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function conf__filtro(toba_ei_filtro $filtro)
	{
		if (!isset($this->s__datos)) {
			$datos = array();
			$datos['estado'] = array('valor' => aprobacion_por_resolucion::estado_abierto, 'condicion' => 'es_igual_a');
			$this->s__datos = $datos;
			$this->evt__filtro__filtrar($datos);
		}
		$filtro->set_datos($this->s__datos);
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function conf__cuadro(toba_ei_cuadro $cuadro)
	{	
		$filtro = $this->dep('filtro')->get_sql_where();
		//El usuario presiono filtrar (ya sea con datos o con el filtro vacío).        
		$datos = $this->cn()->get_listado($filtro);
		$cuadro->set_datos($datos);
		
		$this->indx_msj_eof = '800_eof_cuadro';
		$cuadro->set_eof_mensaje($this->get_msg_eof());
	}
	
	function evt__cuadro__seleccion($seleccion)
	{
		$propuesta = $seleccion['propuesta'];
		unset($seleccion['propuesta']);
		parent::evt__cuadro__seleccion($seleccion);
		$this->cn()->entidad()->cargar_atributos_parametros($propuesta);
	}

	function conf_evt__cuadro__seleccion(toba_evento_usuario $evento, $fila)
	{
		// Si el trámite de aprobación por resolución no posee el estado abierto, 
		// entonces no se permite su selección para editarlo.
		$datos = $this->dependencia('cuadro')->get_datos();
		if ($datos[$fila]['estado_nombre'] != aprobacion_por_resolucion::estado_abierto_desc) {
			$evento->ocultar();
		} else {
			$evento->mostrar();
		}
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA DE CIERRE 
	//-----------------------------------------------------------------------------------

	//-----------------------------------------------------------------------------------
	//---- Configuraciones --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cierre_tramite(toba_ei_pantalla $pantalla)
	{
		$datos = toba::consulta_php('co_aprobacion_por_resolucion')->get_datos_tramite_aprobacion_por_resolucion(current($this->s__seleccion));
		if ($datos['estado'] == equivalencia::estado_abierto) {
			// Se setea el mensaje de confirmación para el evento de Cierre del Trámite de Aprobación
			$msj = guarani_mensajes::get('800EQU_confirmar_cierre');
			$this->evento('cerrar_tramite')->set_msg_confirmacion($msj);
		} else {
			// Se desactiva el evento para el cierre del trámite
			$this->evento('cerrar_tramite')->desactivar();
		}
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	/** 
	 * Se redefine el evento Guardar para pasar a la pantalla de Previsualización, Impresión y Cierre del Trámite. 
	 * El cn se resetea luego de configurar la pantalla de Cierre para poder obtener el Id del Trámite en caso de alta.
	 * De ser necesario, cierra el Trámite de Aprobación.
	 */
	function evt__guardar()
	{
		try {
			$this->cn()->guardar();

			// Recupera el id de trámite si no es el caso de una edición.
			if (!isset($this->s__seleccion)) {
				$datos_tramite = $this->cn()->entidad()->get_datos_generales();
				$this->s__seleccion = array('nro_tramite' => $datos_tramite['nro_tramite']);
			}

			// Cierra el Trámite de Aprobación por Resolución si tiene cierre automático.
			if (guarani::parametros()->get_valor('aprob_cerrar_tramite_automaticamente') == comunes::si) {
				toba::cn('manejador_cerrar_aprobacion')->cerrar_tramite_aprobacion(current($this->s__seleccion));
			}

			$this->cn()->resetear();
			$this->set_pantalla('cierre_tramite');
			$mje = guarani_mensajes::get('guardar_ok');
			toba::notificacion()->agregar($mje, comunes::error_informacion);
		} catch (guarani_error $e) {
			throw $e;
		}
	}

	// Cierra el Trámite de Aprobación por Resolución.
	function evt__cerrar_tramite()
	{
		try {
			toba::cn('manejador_cerrar_aprobacion')->cerrar_tramite_aprobacion(current($this->s__seleccion));

			// Se informa el éxito del cierre del Trámite de Aprobación.
			$mje = guarani_mensajes::get('800EQU_cierre_ok');
			toba::notificacion()->agregar($mje, comunes::error_informacion);
		} catch (guarani_error $e) {
			throw $e;
		}
	}

}
?>
