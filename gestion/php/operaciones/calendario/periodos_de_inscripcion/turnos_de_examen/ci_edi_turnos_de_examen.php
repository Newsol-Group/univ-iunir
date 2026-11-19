<?php
class ci_edi_turnos_de_examen extends guarani_pers_ci
{
	protected $s__turno;
	protected $s__edicion_actual=null;
	protected $ev_info; //Componente Visual de Información
	
	//-----------------------------------------------------------------------------------
	//---- Configuraciones --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function construir_arbol()
	{
		//Configuración del árbol
		$constructor = guarani::constructor_arbol();
		//$constructor->set_metodo_configurador($this,'configuracion_nodo');

		//Iconos de acciones
		$img_editar = toba_recurso::imagen_proyecto('acciones/editar.gif');
		$img_agregar = toba_recurso::imagen_proyecto('acciones/agregar.gif');
		$img_eliminar = toba_recurso::imagen_proyecto('acciones/borrar.gif');
		$img_nula = toba_recurso::imagen_proyecto('acciones/nulo.gif');

		//Iconos de conceptos
		$img_turno = toba_recurso::imagen_proyecto('turnos/turno.png');
		$img_llamado = toba_recurso::imagen_proyecto('turnos/llamado.png');
		$img_periodo = toba_recurso::imagen_proyecto('turnos/periodo_insc.png');
		$img_nivel = toba_recurso::imagen_proyecto('turnos/aplicacion.png');

		/* El nivel raíz se define únicamente cuando hay un nodo principal
		 * del cual se desprende todo el árbol y cuyos datos no se encuentran
		 * en la consulta que alimenta al árbol.
		 */

		//Nivel Turno
		$nivel = guarani::arbol_nivel('turno', 'turno_examen', 'nombre', 'Turno');
		$nivel->agregar_icono($img_turno, 'editar_turno', 'Editar el Turno');
		$nivel->agregar_utileria($img_editar, 'editar_turno', 'Editar el Turno');
		$nivel->agregar_utileria($img_eliminar, 'eliminar_turno', 'Eliminar el Turno',guarani_mensajes::get('800_confirmar_elimin', array('el Turno')));
		$nivel->agregar_utileria($img_agregar, 'agregar_llamado', 'Agregar un Llamado al Turno');
		$nivel->set_metodo_configurador($this, 'configuracion_nodo__turno');
		$constructor->set_nivel($nivel);

		//Nivel Llamado
		$nivel = guarani::arbol_nivel('llamado', 'llamado', 'llamado_descr', 'Llamado');
		$nivel->agregar_icono($img_llamado, 'editar_llamado', 'Editar el Llamado');
		$nivel->agregar_utileria($img_editar, 'editar_llamado', 'Editar el Llamado');
		$nivel->agregar_utileria($img_eliminar, 'eliminar_llamado', 'Eliminar el Llamado',guarani_mensajes::get('800_confirmar_elimin', array('el Llamado')));
		$nivel->agregar_utileria($img_agregar, 'agregar_periodo_insc', 'Agregar un Período de Inscripción');
		$nivel->set_metodo_configurador($this, 'configuracion_nodo__llamado');
		$constructor->set_nivel($nivel);

		//Nivel Período Insc.
		$nivel = guarani::arbol_nivel('periodo', 'periodo_inscripcion', 'periodo_insc_descr', 'Período de Inscripción');
		$nivel->agregar_icono($img_periodo, 'editar_periodo_insc', 'Editar el Período de Inscripción');
		$nivel->agregar_utileria($img_editar, 'editar_periodo_insc', 'Editar el Período de Inscripción');
		$nivel->agregar_utileria($img_eliminar, 'eliminar_periodo_insc', 'Eliminar el Período de Inscripción',guarani_mensajes::get('800CAL_confirma_eliminar_periodo'));
		$nivel->agregar_utileria($img_nula, '', '');
		$nivel->set_metodo_configurador($this, 'configuracion_nodo__periodo');
		$constructor->set_nivel($nivel);

		//Nivel Aplicación
		$nivel = guarani::arbol_nivel('aplicacion', 'id_aplicacion', 'aplicacion_descr', 'Nivel de aplicación');
		$nivel->agregar_icono($img_nivel, null, 'Nivel de Aplicación');
		$constructor->set_nivel($nivel);

		//Carga de datos
		$constructor->set_datos($this->get_datos_arbol());
		return $constructor;
	}

	function conf()
	{
		//Si está creando un nuevo turno
		if (!$this->get_turno_examen()) {
			$this->pantalla()->eliminar_dep('seleccion');
			$this->pantalla()->agregar_dep('edicion_turno');
			$this->controlador->pantalla()->agregar_evento('guardar');
		} 
		if ($this->s__edicion_actual) {
			$this->pantalla()->agregar_dep($this->s__edicion_actual);
			$this->controlador()->pantalla()->agregar_evento('limpiar');
			$this->controlador()->pantalla()->agregar_evento('guardar');
		} else {
			$this->dep('seleccion')->deseleccionar_nodos();
		}
	}

	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_style('width: 550px');
			$this->ev_info->set_columnas_a_mostrar(array('turno_descr', 'llamado_descr2'));
			$this->ev_info->set_etiqueta('turno_descr', 'Turno');
			$this->ev_info->set_etiqueta('llamado_descr2', 'Llamado');
		}
		return $this->ev_info;
	}

	function get_datos_arbol()
	{
		$datos_arbol = array();
		if (!$this->get_turno_examen()) return $datos_arbol;
		$condicion = 'sga_turnos_examen.turno_examen = '.toba::db()->quote($this->get_turno_examen());
		$datos_arbol = $this->cn()->get_turnos_llamados_periodos($condicion);
		return $datos_arbol;
	}

	function configuracion_nodo(guarani_nodo_basico $nodo)
	{
		
	}

	function configuracion_nodo__turno(guarani_nodo_basico $nodo)
	{
		//Si tiene mesas no permitir eliminar (algun llamado con mesas)
		$seleccion = $this->controlador->get_seleccion();
		if (isset($seleccion['cant_mesas']) && ($seleccion['cant_mesas'] > 0)) $nodo->eliminar_evento('eliminar_turno');
		//Si no es editable eliminar los eventos de eliminacion y de agregar
		if (turno_de_examen::esta_finalizado($nodo->get_id())) {
			$nodo->eliminar_evento('eliminar_turno');
			$nodo->eliminar_evento('agregar_llamado');
		}
	}

	function configuracion_nodo__llamado(guarani_nodo_basico $nodo)
	{
		//Si no es editable eliminar los eventos de eliminacion y de agregar
		if (turno_de_examen::llamado_esta_finalizado($nodo->get_id())) {
			$nodo->eliminar_evento('eliminar_llamado');
			$nodo->eliminar_evento('agregar_periodo_insc');
		}
		//Si tiene mesas no permitir eliminar
		$datos_llamado = $this->cn()->co()->get_datos_llamado($nodo->get_id());
		if ($datos_llamado['cant_mesas'] > 0 ) $nodo->eliminar_evento('eliminar_llamado');
	}

	function configuracion_nodo__periodo(guarani_nodo_basico $nodo)
	{
		$llamado = $nodo->get_padre()->get_id();
		//Si no es editable eliminar los eventos de eliminacion y de agregar
		if (turno_de_examen::llamado_esta_finalizado($llamado)) {
			$nodo->eliminar_evento('eliminar_periodo_insc');
		}
	}

	function get_turno_examen()
	{
		if (!isset($this->s__turno) || !$this->s__turno) {
			$this->s__turno = $this->cn()->get_turno_id();
		}
		return $this->s__turno;
	}

	function set_turno_examen($turno)
	{
		$this->s__turno = $turno;
		$this->s__edicion_actual=null;
		$this->dep('seleccion')->deseleccionar_nodos();
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos  ARBOL ---------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function nodo__turno__editar_turno($turno)
	{
		$this->actualizar_datos();
		$this->cn()->cargar_turno($turno);
		$this->s__edicion_actual = 'edicion_turno';
	}

	function nodo__turno__eliminar_turno($turno)
	{
		$this->actualizar_datos();
		$this->cn()->eliminar_llamados_turno($turno);
		$this->cn()->cargar_turno($turno);
		$this->controlador->evt__eliminar(true);
		unset($this->s__turno);
	}

	function nodo__turno__agregar_llamado($turno)
	{
		$this->actualizar_datos();
		$this->cn()->agregar_llamado_a_turno($turno);
		$this->s__edicion_actual = 'edicion_llamado';
	}

	function nodo__llamado__editar_llamado($llamado)
	{
		$this->actualizar_datos();
		$this->cn()->cargar_llamado($llamado);
		$this->s__edicion_actual = 'edicion_llamado';
	}

	function nodo__llamado__eliminar_llamado($llamado)
	{
		$this->actualizar_datos();
		$this->cn()->eliminar_llamado($llamado);
	}

	function nodo__llamado__agregar_periodo_insc($llamado)
	{
		$this->actualizar_datos();
		$this->cn()->agregar_periodo_insc_a_llamado($llamado);
		$this->s__edicion_actual = 'edicion_periodo';
	}

	function nodo__periodo__editar_periodo_insc($periodo)
	{
		$this->actualizar_datos();
		$this->cn()->cargar_periodo($periodo);
		$this->s__edicion_actual = 'edicion_periodo';
	}

	function nodo__periodo__eliminar_periodo_insc($periodo)
	{
		$this->actualizar_datos();
		$this->cn()->eliminar_periodo($periodo);
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos CI ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function actualizar_datos()
	{
		if ($this->cn()->entidad_cargada()) {
			if ($this->cn()->entidad()->es_editable()) {
				$this->controlador->evt__guardar();
			}
			$this->evt__cancelar();
		}
	}

	function set_hubo_cambios()
	{
		$this->controlador()->set_hubo_cambios();
	}

	function evt__cancelar()
	{
		$this->cn()->resetear();
		$this->s__edicion_actual=null;
		$this->dep('seleccion')->deseleccionar_nodos();
		$this->dep('edicion_llamado')->evt__filtro__cancelar();
	}

}