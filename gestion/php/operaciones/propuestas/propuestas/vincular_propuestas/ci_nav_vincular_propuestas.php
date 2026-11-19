<?php
/**
 * 
 * @package Propuestas Formativas
 */
class ci_nav_vincular_propuestas extends ci_navegacion
{
	protected $s__anio_academico;

	protected $s__tipo_relacion;

	protected $s__filtro_planes;
	protected $s__datos_filtro_planes;

	protected $s__plan;

	protected $ev_info;
	protected $ev_info_propuesta;

	//-----------------------------------------------------------------------------------
	//---- Configuraciones --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__seleccion_plan(toba_ei_pantalla $pantalla)
	{
		$pantalla->eliminar_dep('filtro_planes');
		$pantalla->eliminar_dep('cuadro_planes');
		if (! empty($this->s__tipo_relacion) && $this->s__tipo_relacion == propuesta::relacion_tipo_planes) {
			$pantalla->agregar_dep('filtro_planes');
			$pantalla->agregar_dep('cuadro_planes');
		}
		$this->ev_info_propuesta()->set_datos($this->s__seleccion);
	}

	function conf__seleccion_grupo(toba_ei_pantalla $pantalla)
	{
		$this->dep('selector_grupo')->deseleccionar_nodos();
		$this->ev_info()->set_datos($this->get_datos_propuesta_plan());
	}

	function conf__edicion(toba_ei_pantalla $pantalla)
	{
		$this->ev_info()->set_datos($this->get_datos_propuesta_plan());
		$this->hay_cambios = $this->cn()->entidad()->dep('datos')->hay_cambios();
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro -----------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		if (! isset($this->s__filtro)) {
			$msj_eof = guarani_mensajes::get($this->indx_msg_eof.'_sinfiltro');
		} else {
			$datos = $this->cn()->get_listado_propuestas($this->s__filtro);
			$cuadro->set_datos($datos);

			$indx_msj_eof = (!empty($this->s__filtro)) ? $this->indx_msg_eof.'_filtrado' : $this->indx_msg_eof;
			$etiqueta = guarani_etiquetas::get_tabla('sga_propuestas');
			$msj_eof = guarani_mensajes::get($indx_msj_eof, array($etiqueta->get_enf('plural')));
		}
		$cuadro->set_eof_mensaje($msj_eof);
	}

	function evt__cuadro__seleccion($seleccion)
	{
		$this->s__seleccion = $seleccion;
		$this->set_pantalla('seleccion_plan');
	}

	//-----------------------------------------------------------------------------------
	//---- form_tipo_relacion -----------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_tipo_relacion(toba_ei_formulario $form)
	{
		$datos = array();
		if (isset($this->s__tipo_relacion)) {
			$datos['tipo_relacion'] = $this->s__tipo_relacion;
		}
		if (isset($this->s__anio_academico)) {
			$datos['anio_academico'] = $this->s__anio_academico;
		}
		$form->set_datos($datos);
	}

	function evt__form_tipo_relacion__modificacion($datos)
	{
		$this->s__tipo_relacion = $datos['tipo_relacion'];
		$this->s__anio_academico = $datos['anio_academico'];
		if ($datos['tipo_relacion'] == propuesta::relacion_tipo_propuestas) {
			$this->seleccionar_propuesta_plan();
			$this->set_pantalla('seleccion_grupo');
		}
	}

	//-----------------------------------------------------------------------------------
	//---- filtro_planes ----------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__filtro_planes(toba_ei_filtro $filtro)
	{
		$this->filtro_configurar('filtro_planes', false);
	}

	function evt__filtro_planes__filtrar($datos)
	{
		$this->filtro_filtrar('filtro_planes', $datos);
	}

	function evt__filtro_planes__cancelar()
	{
		$this->filtro_limpiar('filtro_planes');
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro_planes ----------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_planes(toba_ei_cuadro $cuadro)
	{
		$datos = $this->cn()->get_listado_planes_propuesta($this->s__seleccion['propuesta'], $this->s__filtro_planes);
		$cuadro->set_datos($datos);
		$etiqueta = guarani_etiquetas::get_tabla('sga_planes');
		$cuadro->set_eof_mensaje(guarani_mensajes::get($this->indx_msg_eof, array($etiqueta->get_enf('plural'))));
	}

	function evt__cuadro_planes__seleccion($seleccion)
	{
		$this->s__plan = $seleccion;
		$this->seleccionar_propuesta_plan();
		$this->set_pantalla('seleccion_grupo');
	}

	//-----------------------------------------------------------------------------------
	//---- Arbol ------------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function construir_arbol()
	{
		// Configuración del árbol.
		$constructor = guarani::constructor_arbol();

		// Iconos de acciones.
		$img_editar = toba_recurso::imagen_proyecto('acciones/editar.gif');
		$img_eliminar = toba_recurso::imagen_proyecto('acciones/borrar.gif');

		// Iconos de conceptos.
		$img_plan = toba_recurso::imagen_proyecto('optativas/elemento_plan_version.png');

		// Nivel Grupo.
		$nivel = guarani::arbol_nivel('relacion_grupo', 'relacion_grupo', 'relacion_grupo_nombre', 'Grupo');
		$nivel->agregar_utileria($img_editar, 'seleccionar_grupo', 'Editar el Grupo');
		$msj_elim = guarani_mensajes::get($this->indx_msg_confirmar_eliminar, array('el Grupo'));
		$nivel->agregar_utileria($img_eliminar, 'eliminar_grupo', 'Eliminar el Grupo', $msj_elim);
		$constructor->set_nivel($nivel);

		// Nivel Plan.
		$nivel = guarani::arbol_nivel('relacion_plan', 'relacion_plan', 'relacion_plan_nombre', 'Plan', 'Grupo');
		$nivel->agregar_icono($img_plan);
		$msj_elim = guarani_mensajes::get($this->indx_msg_confirmar_eliminar, array('el Plan asociado al Grupo'));
		$nivel->agregar_utileria($img_eliminar, 'eliminar_plan', 'Eliminar el Plan', $msj_elim);
		$nivel->set_metodo_configurador($this, 'configuracion_nodo__relacion_plan');
		$constructor->set_nivel($nivel);

		// Carga de datos.
		$constructor->set_datos($this->get_datos_arbol());
		return $constructor;
	}

	function get_datos_arbol()
	{
		$filtro = 'sga_propuestas_relacion.propuesta = ' . toba::db()->quote($this->s__seleccion['propuesta']);
		$filtro .= empty($this->s__plan) ? ' AND sga_propuestas_relacion.plan IS NULL' : ' AND sga_propuestas_relacion.plan = ' . toba::db()->quote($this->s__plan['plan']);
		$filtro .= ' AND sga_propuestas_relacion.anio_academico = ' . toba::db()->quote($this->s__anio_academico);
		return $this->cn()->get_listado($filtro);
	}

	function nodo__relacion_grupo__seleccionar_grupo($relacion_grupo)
	{
		$id_grupo = $this->cn()->entidad()->get_id_grupo(array('relacion_grupo' => $relacion_grupo));
		$this->cn()->entidad()->seleccionar_grupo($id_grupo);
		$this->set_pantalla('edicion');
	}

	function nodo__relacion_grupo__eliminar_grupo($relacion_grupo)
	{
		$id_grupo = $this->cn()->entidad()->get_id_grupo(array('relacion_grupo' => $relacion_grupo));
		$this->cn()->entidad()->seleccionar_grupo($id_grupo);
		$datos_grupo = $this->cn()->entidad()->get_datos_grupo_seleccionado();
		$this->cn()->entidad()->eliminar_grupo_seleccionado();
		$this->cn()->entidad()->sincronizar();
		$cant_grupos_por_relacion = toba::consulta_php('co_propuestas_relaciones')->get_cant_grupos_por_relacion($datos_grupo['relacion']);
		if ($cant_grupos_por_relacion == 0) {
			$this->cn()->entidad()->eliminar_relacion();
		}
	}

	function configuracion_nodo__relacion_plan(guarani_nodo_basico $nodo)
	{
		// Plan no eliminable.
		$id_relacion_plan = $nodo->get_id();
		$cant_planes_por_relacion = toba::consulta_php('co_propuestas_relaciones')->get_cant_planes_por_relacion($id_relacion_plan);
		if ($cant_planes_por_relacion == 1) {
			$nodo->eliminar_evento('eliminar_plan');
		}
	}

	function nodo__relacion_plan__eliminar_plan($relacion_plan)
	{
		$id_plan = $this->cn()->entidad()->get_id_plan(array('relacion_plan' => $relacion_plan));
		$this->cn()->entidad()->eliminar_plan($id_plan);
		$this->cn()->entidad()->sincronizar();
	}

	//-----------------------------------------------------------------------------------
	//---- ev_info ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function ev_info_propuesta()
	{
		if (!isset($this->ev_info_propuesta)) {
			$this->ev_info_propuesta = guarani::ev_info_datos();
			$this->ev_info_propuesta->set_columnas_a_mostrar(array('nombre', 'tipo_descripcion'));
			$this->ev_info_propuesta->set_columnas_fila(array('nombre', 'tipo_descripcion'), 1);
			$this->ev_info_propuesta->set_max_columnas_por_fila(2);
			$this->ev_info_propuesta->set_td_etiqueta_width('70px');
			$this->ev_info_propuesta->set_etiqueta('nombre', 'Propuesta');
			$this->ev_info_propuesta->set_etiqueta('tipo_descripcion', 'Tipo');
		}
		return $this->ev_info_propuesta;
	}

	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_columnas_a_mostrar(array('nombre', 'plan_nombre', 'tipo_descripcion'));
			$this->ev_info->set_columnas_fila(array('nombre', 'plan_nombre', 'tipo_descripcion'), 1);
			$this->ev_info->set_max_columnas_por_fila(3);
			$this->ev_info->set_td_etiqueta_width('70px');
			$this->ev_info->set_etiqueta('nombre', 'Propuesta');
			$this->ev_info->set_etiqueta('plan_nombre', 'Plan');
			$this->ev_info->set_etiqueta('tipo_descripcion', 'Tipo');
		}
		return $this->ev_info;
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__agregar()
	{
		$this->cn()->entidad()->nuevo_grupo(array());
		$this->set_pantalla('edicion');
	}

	function evt__guardar()
	{
		$datos = $this->cn()->entidad()->get_datos_planes();
		if (empty($datos)) {
			$mje = guarani_mensajes::get('800_no_seleccion', array('plan'));
			throw guarani::error($mje);
		}
		$this->cn()->desactivar_transacciones_anidadas();
		try {
			toba::db()->abrir_transaccion();

			$this->cn()->guardar();

			$relacion = $this->cn()->entidad()->get_columna_relacion('relacion');
			// Se registra una "foto" de la relación entre propuestas.
			guarani::act('act_propuestas_relaciones')->cambios_relacion_propuestas($relacion);

			toba::db()->cerrar_transaccion();

			$this->evt__cancelar();

			toba::notificacion()->agregar($this->get_mensaje('guardar_ok'), 'info');
		} catch (toba_error $e) {
			toba::db()->abortar_transaccion();
			throw $e;
		}
	}

	function evt__cancelar_seleccion_propuesta()
	{
		unset($this->s__seleccion);
		unset($this->s__tipo_relacion);
		unset($this->s__anio_academico);
		$this->set_pantalla('seleccion_propuesta');
	}

	function evt__cancelar_seleccion_plan()
	{
		unset($this->s__plan);
		unset($this->s__tipo_relacion);
		unset($this->s__anio_academico);
		$this->cn()->resetear();
		$this->set_pantalla('seleccion_plan');
	}

	function evt__cancelar()
	{
		$this->cn()->resetear();
		$this->seleccionar_propuesta_plan();
		$this->set_pantalla('seleccion_grupo');
	}

	//-----------------------------------------------------------------------------------
	//---- Auxiliares -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function get_propuesta()
	{
		return $this->s__seleccion['propuesta'];
	}

	function get_plan()
	{
		return ($this->s__plan['plan'] ?? null);
	}

	function get_datos_propuesta_plan()
	{
		$datos = $this->s__seleccion;
		if (! empty($this->s__plan)) {
			$datos['plan_nombre'] = $this->s__plan['nombre'];
		}
		return $datos;
	}

	function seleccionar_propuesta_plan()
	{
		$relacion = array('propuesta' => $this->s__seleccion['propuesta'], 'anio_academico' => $this->s__anio_academico);
		if (isset($this->s__plan)) {
			$relacion['plan'] = $this->s__plan['plan'];
		}
		$this->cn()->cargar($relacion);

		if ($this->cn()->entidad()->es_nueva()) {
			$this->cn()->entidad()->set_datos_relacion($relacion);
		}
	}

	function hay_historial()
	{
		$cant_relaciones = toba::consulta_php('co_propuestas_relaciones')->get_cant_relaciones_por_propuesta_plan($this->get_propuesta(), $this->get_plan());
		return ($cant_relaciones > 0);
	}

	function get_anios_academicos_mayores_o_iguales_calendario($tipo_relacion)
	{
		return toba::consulta_php('co_anios_academicos')->get_anios_academicos_mayores_o_iguales(guarani_fecha::get_hoy_parte('anio'));
	}
}