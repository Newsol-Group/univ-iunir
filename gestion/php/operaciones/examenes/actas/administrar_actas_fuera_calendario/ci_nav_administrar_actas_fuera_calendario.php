<?php
/**
 *
 *
 * @package		Examenes
 * @subpackage	Actas
 * 
 */
class ci_nav_administrar_actas_fuera_calendario extends ci_navegacion
{
	protected $s__filtro_actividades;
	protected $s__datos_filtro_actividades;
	
	protected $s__seleccion_actividad;
	protected $s__datos_actividad;
	
	protected $s__id_acta;
	protected $s__datos_resumen;
	
	protected $ev_info;
	protected $ev_info_resumen;

	//-----------------------------------------------------------------------------------
	//---- Auxiliares  ------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function get_indx_msg_eof()
	{
		return $this->indx_msg_eof;
	}

	function get_acta_seleccionada()
	{
		return current($this->s__seleccion);
	}

	function get_acta_generada()
	{
		return $this->s__id_acta;
	}

	function get_llamado_mesa_entidad_acta_generada()
	{
		return $this->s__datos_resumen['llamado_mesa_entidad'];
	}

	function get_actividad_acta()
	{
		if (isset($this->s__datos_actividad)) {
			return $this->s__datos_actividad['elemento'];
		}
	}

	function get_escala_notas_defecto()
	{
		if (isset($this->s__datos_actividad)) {
			return $this->s__datos_actividad['escala_nota_examen'];
		}
	}

	function resetear_variables_actividad()
	{
		unset($this->s__filtro_actividades);
		unset($this->s__datos_filtro_actividades);
		unset($this->s__seleccion_actividad);
		unset($this->s__datos_actividad);
	}

	function resetear_variables_acta()
	{
		unset($this->s__id_acta);
		unset($this->s__datos_resumen);
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA SELECCION ACTA 
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
		parent::conf__filtro($filtro);
		$filtro->columna('actividad')->set_condicion_fija('contiene');
	}
	
	//-----------------------------------------------------------------------------------
	//---- cuadro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function evt__cuadro__seleccion($seleccion)
	{
		parent::evt__cuadro__seleccion($seleccion);
		$this->cn()->cargar_atributos_parametros_acta();
	}


	//-----------------------------------------------------------------------------------
	//---- PANTALLA SELECCION ACTIVIDAD 
	//-----------------------------------------------------------------------------------

	function conf__seleccion_actividad(toba_ei_pantalla $pantalla)
	{
		$this->cn()->resetear_atributos_parametros();
	}
	
	//-----------------------------------------------------------------------------------
	//---- Filtro sobre Actividades -----------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__filtro_actividades(guarani_ei_filtro $filtro)
	{
		$filtro->columna('actividad')->set_condicion_fija('contiene');
		if (isset($this->s__datos_filtro_actividades)) {
			$filtro->set_datos($this->s__datos_filtro_actividades);
		}
	}

	function evt__filtro_actividades__filtrar($datos)
	{
		if (isset($datos) && !empty($datos)) {
			$this->s__datos_filtro_actividades = $datos;
			$this->s__filtro_actividades = $this->dep('filtro_actividades')->get_sql_where();
		} else {
			unset($this->s__datos_filtro_actividades);
			$this->s__filtro_actividades = '';
		}
	}

	function evt__filtro_actividades__cancelar()
	{
		unset($this->s__datos_filtro_actividades);
		unset($this->s__filtro_actividades);
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro_actividades ----------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_actividades(toba_ei_cuadro $cuadro)
	{
		// Setea el mensaje eof del cuadro de actividades.
		// Recupera el índice del mensaje de eof del cuadro del ci de navegación.
		$indx_msj = $this->indx_msg_eof;
		if (!isset($this->s__filtro_actividades)) {
			$indx_msj .= '_sinfiltro';
			$cuadro->set_eof_mensaje(guarani_mensajes::get($indx_msj));
		} else {
			$indx_msj = $this->indx_msg_eof . '_filtrado';
			$cuadro->set_eof_mensaje(guarani_mensajes::get($indx_msj, array('Actividades')));
			$actividades = $this->cn()->get_listado_actividades($this->s__filtro_actividades);
			foreach ($actividades as $clave => $valor) {
				$actividades[$clave]['nombre_html'] = $valor['nombre'];
				if ($valor['tiene_nombres_alternativos']) {
					$vinculo = toba::vinculador()->get_url(toba_proyecto::get_id(), '32000043', array('actividad' => $valor['elemento']), array('celda_memoria' => 'celda_nombres_alternativos'));
					$javascript = "abrir_popup('info','$vinculo',{'width':'650','height':'420'})";
					$salida = "<a class='icono-utileria' href='#' onclick=\"$javascript\">";
					$salida .= toba_recurso::imagen_toba('info_chico.gif', true, null, null, 'Ver nombres alternativos');
					$salida .= '</a>';
					$actividades[$clave]['nombre_html'] = "{$valor['nombre']} $salida";
				}
			}
			$cuadro->set_datos($actividades);
		}
	}

	function evt__cuadro_actividades__seleccion($seleccion)
	{
		$this->s__seleccion_actividad = current($seleccion);
		parent::evt__agregar();
		$this->cn()->cargar_atributos_parametros_actividad($seleccion['elemento']);
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__agregar_acta()
	{
		// Se pasa a la segunda pantalla para seleccionar una Actividad a la cual se asociará el Acta.
		$this->set_pantalla('seleccion_actividad');
	}

	/**
	 * Cancela la selección de una Actividad para agregar un Acta.
	 */
	function evt__cancelar_seleccion_actividad()
	{
		$this->resetear_variables_actividad();
		$this->set_pantalla('seleccion');
	}

	function evt__cancelar()
	{
		$this->resetear_variables_actividad();
		$this->resetear_variables_acta();
		$this->dependencia('editor')->resetear_variables();
		parent::evt__cancelar();
	}

	function evt__generar()
	{
		try {
			$this->s__id_acta = $this->cn()->generar_acta();
			$this->cn()->resetear();
			$this->cn()->cargar(array('id_acta' => $this->s__id_acta));
			$this->dependencia('editor')->set_pantalla('pant_alumnos');
		} catch (guarani_error $e) {
			throw $e;
		}
	}

	function evt__guardar()
	{
		$alumnos_detalles = $this->dependencia('editor')->get_alumnos_asociados();
		if (empty($alumnos_detalles)) {
			$msj = guarani_mensajes::get('800_sin_seleccion_alumnos');
			throw guarani::error($msj);
		}
		$this->cn()->hay_cambios();
		$this->resetear_variables_actividad();
		$this->resetear_variables_acta();
		$this->dependencia('editor')->resetear_variables();
		parent::evt__guardar();
	}

	//-----------------------------------------------------------------------------------
	//---- Configuraciones --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__edicion(toba_ei_pantalla $pantalla)
	{
		parent::conf__edicion($pantalla);
		// Datos elemento visual
		$datos = $this->get_datos_ev_actividad();
		$this->ev_info()->set_datos($datos);
		// Elimina el evt Generar o Guardar según corresponda a si se está creando o editando un Acta.
		if ($this->cn()->entidad_es_nueva()) {
			$pantalla->eliminar_evento('guardar');
		} else {
			$pantalla->eliminar_evento('generar');
		}
	}

	function conf__informacion(toba_ei_pantalla $pantalla)
	{
		// Datos elemento visual
		$datos = $this->get_datos_acta_info();
		$this->ev_info_resumen()->set_datos($datos);

		// Se modifica la etiqueta e imagen del botón
		$this->evento('cancelar')->set_etiqueta('Ir al inicio');
		$this->evento('cancelar')->set_imagen('/home.png');
	}

	//-----------------------------------------------------------------------------------
	//---- Elementos Visuales -----------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function get_datos_acta_info()
	{
		if (!isset($this->s__datos_resumen)) {
			$datos_acta = toba::consulta_php('co_actas')->get_datos_basicos_acta_examen($this->s__id_acta);
			$this->s__datos_resumen = toba::consulta_php('co_mesas_de_examen')->get_datos_llamado_mesa($datos_acta['llamado_mesa']);
			$this->s__datos_resumen['cod_nombre_actividad'] = $this->s__datos_actividad['actividad_descr'];
			$this->s__datos_resumen['nro_acta'] = $datos_acta['nro_acta'];
		}
		return $this->s__datos_resumen;
	}

	/**
	 * Trae los datos de la Actividad seleccionada para crear el Acta, en el caso de un Alta, o de la Actividad asociada
	 * al Acta en edición, en caso contrario.
	 */
	function get_datos_ev_actividad()
	{
		if (!isset($this->s__datos_actividad)) {
			if (isset($this->s__seleccion_actividad)) {
				$datos = toba::consulta_php('co_elementos')->get_datos_basicos_actividad($this->s__seleccion_actividad);
			} else {
				$id_acta = current($this->s__seleccion);
				$datos = toba::consulta_php('co_actas')->get_datos_actividad_mesa_acta_examen($id_acta);
			}
			$this->s__datos_actividad = $datos;
		}
		return $this->s__datos_actividad;
	}

	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_style('border-style: solid; border-width: 1px; border-color: #DDD;');
			$this->ev_info->set_style_fila('height: 30px;');
			$this->ev_info->set_columnas_a_mostrar(array('actividad_descr', 'tipo_actividad_nombre'));
			$this->ev_info->set_columnas_fila(array('actividad_descr', 'tipo_actividad_nombre'), 1);
			$this->ev_info->set_max_columnas_por_fila(2);
			$this->ev_info->set_etiqueta('actividad_descr', 'Actividad');
			$this->ev_info->set_etiqueta('tipo_actividad_nombre', 'Tipo');

			$this->ev_info->set_td_etiqueta_width('20px');
			$this->ev_info->set_td_valores_width('200px');
		}
		return $this->ev_info;
	}

	function ev_info_resumen()
	{
		if (!isset($this->ev_info_resumen)) {
			$this->ev_info_resumen = guarani::ev_info_datos();
			$this->ev_info_resumen->set_style('border-style: solid; border-width: 1px; border-color: #DDD;');
			$this->ev_info_resumen->set_style_fila('height: 30px;');
			$this->ev_info_resumen->set_columnas_a_mostrar(array('nro_acta', 'anio_academico', 'fecha_formato_visual', 'ubicacion_nombre', 'cod_nombre_actividad'));
			$this->ev_info_resumen->set_columnas_fila(array('nro_acta', 'anio_academico'));
			$this->ev_info_resumen->set_columnas_fila(array('fecha_formato_visual', 'ubicacion_nombre'));
			$this->ev_info_resumen->set_columnas_fila(array('cod_nombre_actividad'));
			$this->ev_info_resumen->set_max_columnas_por_fila(2);
			$this->ev_info_resumen->set_etiqueta('anio_academico', 'Año Académico');
			$this->ev_info_resumen->set_etiqueta('fecha_formato_visual', 'Fecha');
			$this->ev_info_resumen->set_etiqueta('cod_nombre_actividad', 'Actividad');
			$this->ev_info_resumen->set_etiqueta('ubicacion_nombre', 'Ubicación');
			$this->ev_info_resumen->set_etiqueta('nro_acta', 'Acta');

			$this->ev_info_resumen->set_td_etiqueta_width('70px');
			$this->ev_info_resumen->set_td_valores_width('200px');
		}
		return $this->ev_info_resumen;
	}

}
?>