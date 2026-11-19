<?php
/**
 *
 *
 * @package Propuestas Formativas
 * @subpackage Actividades
 */
class ci_nav_optativas_de_actividades_genericas extends ci_navegacion
{
	protected $indx_msj_eof = '800_eof_cuadro';

	// Filtro sobre actividades asociadas.
	protected $s__datos_filtro_optativas_asociadas;
	protected $s__filtro_optativas_asociadas;

	// Filtro sobre actividades a agregar.
	protected $s__datos_filtro_optativas_a_agregar;
	protected $s__filtro_optativas_a_agregar;

	//Materia Genérica
	protected $s__datos_mat_generica;

	//Selección Actividad
	protected $s__seleccion_optativa;
	protected $seleccion_actividades_temp;

	//Elementos de Visualización
	protected $ev_info_generica;

	function get_mat_generica()
	{
		return current($this->s__seleccion);
	}

	function get_datos_mat_generica()
	{
		return $this->s__datos_mat_generica;
	}

	function get_optativa_seleccionada()
	{
		return $this->s__seleccion_optativa;
	}

	function ini()
	{
		//Se setea el hecho de no volver al guardar en el ci padre.
		parent::ini();
		$this->controlador()->set_no_volver_al_guardar();
	}

	function conf__edicion(toba_ei_pantalla $pantalla)
	{
		parent::conf__edicion($pantalla);
		$datos = $this->get_datos_ev_mat_gen();
		$this->ev_info_generica()->set_datos($datos);
		/**
		 * Se setea el mensaje de confirmación del evento aplicar_cambios, para confirmar el agregado de optativas.
		 * Este evento estará activo sólo si la genérica seleccionada tiene optativas en la tabla auxiliar sin
		 * confirmar, y además no se han realizado nuevos cambios desde que se la seleccionó en la primer pantalla.
		 */
		$datos_seleccion = $this->cn()->entidad()->get_datos_materia();
		$optativas_temporales = toba::consulta_php('co_elementos')->get_optativas_temporales_materia_generica($datos_seleccion['elemento_revision']);
		if (! empty($optativas_temporales) && ! $this->cn()->entidad()->hay_cambios()) {
			$this->evento('aplicar_cambios')->set_msg_confirmacion(guarani_mensajes::get('800PRO_confirmar_optativas_plan'));
		} else {
			$this->evento('aplicar_cambios')->desactivar();
		}
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA SELECCION -----------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		parent::conf__cuadro($cuadro);
		$indx_msj = $this->indx_msj_eof;
		if (isset($this->s__filtro) && ! empty($this->s__filtro)) {
			$indx_msj .= '_filtrado';
		}
		$cuadro->set_eof_mensaje(guarani_mensajes::get($indx_msj, array('Materias Genéricas')));
	}

	function evt__cuadro__seleccion($seleccion)
	{
		if (toba::consulta_php('co_planes')->generica_en_planes_no_visibles($seleccion['elemento'])) {
			$mje = guarani_mensajes::get('800PRO_generica_planes_no_visibles');
			throw guarani::error($mje);
		}
		parent::evt__cuadro__seleccion($seleccion);
	}

	function conf_evt__cuadro__seleccion(toba_evento_usuario $evento, $fila)
	{
		// Sólo se permite editar las materias genéricas activas.
		$datos = $this->dep('cuadro')->get_datos();
		if ($datos[$fila]['estado_desc'] == elemento::estado_activo_desc) {
			$evento->mostrar();
		} else {
			$evento->ocultar();
		}
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA SELECCION OPTATIVA --------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__filtro_optativas_asociadas(guarani_ei_filtro $filtro)
	{
		if (isset($this->s__datos_filtro_optativas_asociadas)) {
			$filtro->set_datos($this->s__datos_filtro_optativas_asociadas);
		} else {
			$filtro->colapsar();
		}
		$filtro->columna('nombre')->set_condicion_fija('contiene');
		$filtro->columna('codigo')->set_condicion_fija('contiene');
	}

	function evt__filtro_optativas_asociadas__filtrar($datos)
	{
		if (isset($datos) && !empty($datos)) {
			$this->s__datos_filtro_optativas_asociadas = $datos;
			$this->s__filtro_optativas_asociadas = $this->dep('filtro_optativas_asociadas')->get_sql_where();
		} else {
			unset($this->s__datos_filtro_optativas_asociadas);
			$this->s__filtro_optativas_asociadas = '';
		}
	}

	function evt__filtro_optativas_asociadas__cancelar()
	{
		unset($this->s__datos_filtro_optativas_asociadas);
		unset($this->s__filtro_optativas_asociadas);
	}

	//---- Cuadro de Actividades Asociadas ----------------------------------------------

	function conf__cuadro_optativas_asociadas(toba_ei_cuadro $cuadro)
	{
		/**
		 * Las optativas asociadas a la materia genérica serán aquellas que ya están confirmadas (sga_elementos_comp)
		 * y aquellas que están asociadas temporalmente (sga_elementos_comp_aux) obtenidas del DT.
		 */
		$mat_gen = current($this->s__seleccion);
		$filtro = 'generica.elemento = '.toba::db()->quote($mat_gen);
		if (!empty($this->s__filtro_optativas_asociadas)) {
			$filtro .= 'AND'.$this->s__filtro_optativas_asociadas;
		}
		$optativas_confirmadas = toba::consulta_php('co_elementos')->get_materias_genericas_con_sus_componentes($filtro);
		$optativas_temporales = $this->cn()->entidad()->get_optativas_generica();
		// Se recorren las optativas temporales para agregarle los datos.
		$filtro = $this->get_filtro_elemento_revision();
		foreach ($optativas_temporales as $key => $optativa) {
			$datos_optativa = toba::consulta_php('co_elementos')->get_datos_basicos_elemento_revision($optativa['elemento_hijo'], $filtro);
			if (empty($datos_optativa)) {
				unset($optativas_temporales[$key]);
			} else {
				$optativas_temporales[$key]['revision_materia_optativa'] = $optativa['elemento_hijo'];
				$optativas_temporales[$key]['codigo_materia_optativa'] = $datos_optativa['codigo'];
				$optativas_temporales[$key]['nombre_materia_optativa'] = $datos_optativa['nombre'];
				$optativas_temporales[$key]['puntaje_materia_optativa'] = $optativa['puntaje'];
				$optativas_temporales[$key]['optativa_tipo'] = $datos_optativa['entidad_subtipo_nombre'];
				$optativas_temporales[$key]['desc_estado_materia_optativa'] = $datos_optativa['estado_desc'];
				$optativas_temporales[$key]['optativa_confirmada'] = comunes::no;
			}
		}
		$datos_cuadro = array_merge($optativas_temporales, $optativas_confirmadas);
		$cuadro->set_datos($datos_cuadro);
		$cuadro->set_eof_mensaje(guarani_mensajes::get($this->indx_msj_eof, array('Materias Optativas asociadas')));
	}

	function get_filtro_elemento_revision()
	{
		$filtro_nombre = '';
		$filtro_condicion = new toba_filtro_condicion('contiene', 'ILIKE', '%', '%', '', '');
		if (isset($this->s__datos_filtro_optativas_asociadas['nombre'])) {
			$nombre = guarani_texto::limpiar_acentos($this->s__datos_filtro_optativas_asociadas['nombre']['valor']);
			$filtro_nombre = $filtro_condicion->get_sql('f_limpiar_acentos(vw_elementos.nombre::varchar)', $nombre);
		}
		$filtro_codigo = '';
		if (isset($this->s__datos_filtro_optativas_asociadas['codigo'])) {
			$codigo = guarani_texto::limpiar_acentos($this->s__datos_filtro_optativas_asociadas['codigo']['valor']);
			$filtro_codigo = empty($filtro_nombre) ? '' : ' AND ';
			$filtro_codigo .= $filtro_condicion->get_sql('f_limpiar_acentos(vw_elementos.codigo::varchar)', $codigo);
		}
		return $filtro_nombre.$filtro_codigo;
	}

	function conf_evt__cuadro_optativas_asociadas__eliminar(toba_evento_usuario $evento, $fila)
	{
		// Sólo se permite eliminar optativas temporales asociadas a la materia genérica (no las ya confirmadas).
		$datos = $this->dep('cuadro_optativas_asociadas')->get_datos();
		if ($datos[$fila]['optativa_confirmada'] == comunes::si) {
			$evento->ocultar();
		} else {
			$evento->mostrar();
		}
	}

	function evt__cuadro_optativas_asociadas__seleccion($seleccion)
	{
		$this->s__seleccion_optativa = $seleccion['revision_materia_optativa'];
		$this->cn()->seleccionar_optativa($this->s__seleccion_optativa, $seleccion['optativa_confirmada']);
		$this->set_pantalla('edicion_optativas');
	}

	function evt__cuadro_optativas_asociadas__eliminar($seleccion)
	{
		$this->cn()->entidad()->eliminar_optativa($seleccion);
	}

	//---- Filtro sobre actividades -----------------------------------------------------

	function conf__filtro_optativas_a_agregar(guarani_ei_filtro $filtro)
	{
		if (isset($this->s__datos_filtro_optativas_a_agregar)) {
			$filtro->set_datos($this->s__datos_filtro_optativas_a_agregar);
		}
	}

	function evt__filtro_optativas_a_agregar__filtrar($datos)
	{
		if (isset($datos) && !empty($datos)) {
			$this->s__datos_filtro_optativas_a_agregar = $datos;
			$this->s__filtro_optativas_a_agregar = $this->dep('filtro_optativas_a_agregar')->get_sql_where();
		} else {
			unset($this->s__datos_filtro_optativas_a_agregar);
			$this->s__filtro_optativas_a_agregar = '';
		}
	}

	function evt__filtro_optativas_a_agregar__cancelar()
	{
		unset($this->s__datos_filtro_optativas_a_agregar);
		unset($this->s__filtro_optativas_a_agregar);
	}

	//---- Cuadro de Actividades a Asociar ----------------------------------------------

	function conf__cuadro_optativas_a_agregar(toba_ei_cuadro $cuadro)
	{
		// Recupera las actividades que no estén ya asociadas a la materia genérica seleccionada, según el filtro dado.

		$indx_msj = $this->indx_msj_eof;
		if (isset($this->s__filtro_optativas_a_agregar) && ! empty($this->s__filtro_optativas_a_agregar)) {
			$indx_msj .= '_filtrado';
		}
		$cuadro->set_eof_mensaje(guarani_mensajes::get($indx_msj, array('Materias Optativas para asociar')));

		// Se excluyen aquellas actividades ya asociadas a la materia genérica.
		$mat_gen = current($this->s__seleccion);
		$optativas_confirmadas = toba::consulta_php('co_elementos')->get_optativas_materia_generica($mat_gen);
		$optativas_temporales = $this->cn()->entidad()->get_optativas_generica();
		// Se recorren las optativas temporales para unificar las claves de elemento_revision con las optativas confirmadas.
		foreach ($optativas_temporales as $key => $optativa) {
			$optativas_temporales[$key]['revision_materia_optativa'] = $optativa['elemento_hijo'];
		}
		$optativas_asociadas = array_merge($optativas_confirmadas, $optativas_temporales);
		$cond_not_in = guarani_sql::armar_condicion_not_in($optativas_asociadas, 'sga_elementos_revision', 'elemento_revision', 'revision_materia_optativa');
		$filtro = (isset($this->s__filtro_optativas_a_agregar) && ! empty($this->s__filtro_optativas_a_agregar)) ? $cond_not_in . ' AND ' . $this->s__filtro_optativas_a_agregar : $cond_not_in;
		$actividades = toba::consulta_php('co_elementos')->get_actividades($filtro, false);
		$cuadro->set_datos($actividades);
	}

	function evt__cuadro_optativas_a_agregar__seleccion($seleccion)
	{
		$this->seleccion_actividades_temp = $seleccion;
	}

	function evt__cuadro_optativas_a_agregar__agregar()
	{
		if (!empty($this->seleccion_actividades_temp)) {
			foreach ($this->seleccion_actividades_temp as $actividad) {
				$this->cn()->entidad()->agregar_optativa($actividad);
			}
		}
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA EDICION OPTATIVA ----------------------------------------------------
	//-----------------------------------------------------------------------------------

	/**
	 * Se define la configuración de la pantalla de edición de optativas para que se puedan tener en cuenta los 
	 * cambios en el datos relación cuando se ejecuta el evento Guardar. En este caso la edición se da tanto en 
	 * la pantalla de selección de optativas, como al editar sus atributos.
	 */
	function conf__edicion_optativas(toba_ei_pantalla $pantalla)
	{
		if ($this->cn()->entidad_cargada()) {
			if ($this->cn()->entidad()->hay_cambios()) {
				parent::set_hubo_cambios();
			}
		}
		
		// El volver estará habilitado solo si no hay un nodo actualmente en edición en el árbol.
		$nodo_en_edicion = $this->dep('editor_optativas')->get_edicion_actual();
		if (is_null($nodo_en_edicion)) {
			$this->evento('cancelar_optativa')->activar();
		} else {
			$this->evento('cancelar_optativa')->desactivar();
		}
	}

	//-----------------------------------------------------------------------------------
	//---- Elementos de visualización ---------------------------------------------------
	//-----------------------------------------------------------------------------------

	function get_datos_ev_mat_gen()
	{
		if (!isset($this->s__datos_mat_generica)) {
			$datos_seleccion = $this->cn()->entidad()->get_datos_materia();
			$this->s__datos_mat_generica = toba::consulta_php('co_elementos')->get_datos_basicos_elemento_revision($datos_seleccion['elemento_revision']);
		}
		return $this->s__datos_mat_generica;
	}

	function ev_info_generica()
	{
		if (!isset($this->ev_info_generica)) {
			$this->ev_info_generica = guarani::ev_info_datos();
			$this->ev_info_generica->set_controlador($this);
			$this->ev_info_generica->set_columnas_a_mostrar(array('nombre', 'estado_desc'));
			$this->ev_info_generica->set_columnas_fila(array('nombre', 'estado_desc'), 1);
			$this->ev_info_generica->set_max_columnas_por_fila(2);
			$this->ev_info_generica->set_etiqueta('nombre', 'Actividad');
			$this->ev_info_generica->set_etiqueta('estado_desc', 'Estado');
			$this->ev_info_generica->set_td_etiqueta_width('70px');
		}
		return $this->ev_info_generica;
	}

	//-----------------------------------------------------------------------------------
	//---- EVENTOS DEL CI ---------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	//Se redefine el evento Volver del ci de navegación para que limpie la variable de sesión que mantiene el componente en edición.
	function evt__cancelar()
	{
		unset($this->s__datos_mat_generica);
		unset($this->s__seleccion_optativa);
		unset($this->s__filtro_optativas_asociadas);
		unset($this->s__datos_filtro_optativas_asociadas);
		unset($this->s__filtro_optativas_a_agregar);
		unset($this->s__datos_filtro_optativas_a_agregar);
		parent::evt__cancelar();
	}

	//Se limpia la variable de sesión que mantiene el componente en edición, y se vuelve la pantalla de selección de Optativas.
	function evt__cancelar_optativa()
	{
		$this->dep('editor_optativas')->limpiar_edicion();
		$this->set_pantalla('edicion');
	}

	// Se confirman las optativas que se asociaron temporalmente a la materia genérica seleccionada.
	function evt__aplicar_cambios()
	{
		$this->cn()->confirmar_optativas(current($this->s__seleccion));
		$this->evt__cancelar();
	}
}
?>