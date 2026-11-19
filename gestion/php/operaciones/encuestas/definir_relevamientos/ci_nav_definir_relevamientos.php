<?php
/**
 * 
 * @package Encuestas
 */
class ci_nav_definir_relevamientos extends ci_navegacion
{
	//--------------------------------------------------------------------------
	//---- filtro --------------------------------------------------------------
	//--------------------------------------------------------------------------

	function conf__filtro(toba_ei_filtro $filtro)
	{
		parent::conf__filtro($filtro);
		$filtro->columna('formularios')->set_condicion_fija('contiene');
	}

	function evt__filtro__filtrar($datos)
	{
		if (isset($datos)) {
			$this->s__datos = $datos;
			$filtro_formularios = '';
			if (isset($datos['formularios'])) {
                            
                                $expresion_filtro_gde_encuestas_nombre = guarani_ei_filtro::get_expresion('gde_encuestas.nombre', $datos['formularios']['valor'], $datos['formularios']['condicion_cs'], $datos['formularios']['condicion_as']);
				$filtro_formularios .= " AND EXISTS (SELECT * FROM gde_encuestas WHERE gde_grupos.encuesta = gde_encuestas.encuesta AND $expresion_filtro_gde_encuestas_nombre)";
				$this->dep('filtro')->columna('formularios')->resetear_estado();
			}
			$this->s__filtro = "{$this->dep('filtro')->get_sql_where()} $filtro_formularios";
		} else {
			unset($this->s__datos);
			$this->s__filtro = '';
		}
	}

	//--------------------------------------------------------------------------
	//---- Eventos -------------------------------------------------------------
	//--------------------------------------------------------------------------

	function evt__eliminar()
	{
		try {
			$this->cn()->desactivar_transacciones_anidadas();
			toba::db()->abrir_transaccion();

			$habilitacion = $this->cn()->entidad()->get_columna_habilitacion('habilitacion');
			guarani::act('act_encuestas')->eliminar_items($habilitacion);

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
	}

	function evt__guardar()
	{
		try {
			$this->cn()->desactivar_transacciones_anidadas();
			toba::db()->abrir_transaccion();

			$tipo_relevamiento = $this->cn()->entidad()->get_columna_habilitacion('tipo_relevamiento');
			if ($tipo_relevamiento == encuesta::tipo_relevamiento_becas && !$this->dep('editor')->dep('editor_datos_censales')->existe_dato_censal_obligatorio()) {
				$mje = guarani_mensajes::get('800ENC_dato_censal_obligatorio');
				throw guarani::error($mje);
			}

			$this->cn()->guardar();

			$habilitacion = $this->cn()->entidad()->get_columna_habilitacion('habilitacion');
			guarani::act('act_encuestas')->generar_items($habilitacion);

			if ($tipo_relevamiento == encuesta::tipo_relevamiento_becas) {
				$datos_censales_config = $this->dep('editor')->dep('editor_datos_censales')->get_datos_censales();
				guarani::act('act_encuestas')->eliminar_datos_censales_config($habilitacion);
				guarani::act('act_encuestas')->insertar_datos_censales_config($habilitacion, $datos_censales_config);
			}

			toba::db()->cerrar_transaccion();

			$this->evt__cancelar();
			toba::notificacion()->agregar($this->get_mensaje('guardar_ok'), 'info');
		} catch (toba_error $e) {
			toba::db()->abortar_transaccion();
			throw $e;
		}
	}

	function evt__cancelar()
	{
		$this->dep('editor')->dep('editor_datos_censales')->resetear_variables();
		parent::evt__cancelar();
	}

	function evt__cuadro__sincronizar($seleccion)
	{
		$this->cn()->sincronizar($seleccion);
	}

	function conf_evt__cuadro__sincronizar(toba_evento_usuario $evento, $fila)
	{
		$datos = $this->dep('cuadro')->get_datos();
		// Por ahora solo se pueden crear, no modificar
		if ($this->cn()->es_habilitacion_sincronizada($datos[$fila])) { 
			$evento->anular();
		}
	}
    
    function evt__cuadro__recalcular($seleccion)
	{        
        $this->cn()->recalcular($seleccion['habilitacion']);
	}

	function conf_evt__cuadro__copiar(toba_evento_usuario $evento, $fila)
	{
		$datos = $this->dep('cuadro')->get_datos();
		$mensaje = guarani_mensajes::get('800_aviso_copiar', array($datos[$fila]['titulo']));
		$evento->set_msg_confirmacion($mensaje);
	}

	function evt__cuadro__copiar($seleccion)
	{
		$id_nueva_habilitacion = $this->cn()->copiar_habilitacion($seleccion['habilitacion']);
		$condicion = array('habilitacion' => $id_nueva_habilitacion, 'fecha_desde' => guarani_fecha::get_fecha_desplazada(guarani_fecha::get_hoy(), 1, 'dia'), 'kolla_sincronizado' => comunes::no);
		$this->evt__cuadro__seleccion($condicion);
	}
}