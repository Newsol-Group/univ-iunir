<?php
/**
 *
 * @package Exámenes
 * @subpackage Actas
 */
class ci_nav_mesas extends ci_navegacion
{
	// llamado Mesa seleccionado
	protected $s__llamado_mesa_entidad;
	protected $s__llamado_mesa;
	protected $s__mesa_examen;
	
	// Evaluación automática correspondiente al Llamado Mesa seleccionado.
	protected $s__evaluacion;
	
	// Pantalla
	protected $s__pantalla;
	
	// Mensajes
	protected $msg_sin_evaluaciones = '800_sin_evaluacion';
	
	// Datos del Acta para info
	protected $s__datos_mesa_llamados;
	protected $ev_info;

	//--------------------------------------------------------------------------
	//---- Configuraciones -----------------------------------------------------
	//--------------------------------------------------------------------------

	function conf()
	{
		parent::conf();
		$this->set_sin_listado_inicial();
		$this->s__pantalla = $this->get_id_pantalla();
		if ($this->s__pantalla == 'seleccion_mesa') {
			//Se setea el título del ci para la pantalla de Selección de Mesas.
			$this->set_titulo('Listado de Mesas');
		} elseif ($this->s__pantalla == 'edicion') {
			//Se limpia el título de las pantallas de selección.
//			$this->set_titulo('');
		}
	}
	
	function conf__seleccion_mesa(toba_ei_pantalla $pantalla)
	{
		$this->cn()->resetear_atributos_parametros();
	}

	function conf__edicion(toba_ei_pantalla $pantalla)
	{
		parent::conf__edicion($pantalla);
		$datos = $this->get_datos_ev_mesa();
		$this->ev_info()->set_datos($datos);
		$this->hay_cambios = $this->cn()->hay_cambios();
	}

	function get_datos_ev_mesa()
	{
		if (!isset($this->s__datos_mesa_llamados)) {
			$llamado_mesa = $this->get_llamado_mesa();
			$this->s__datos_mesa_llamados = toba::consulta_php('co_mesas_de_examen')->get_datos_mesa_llamados($llamado_mesa);
			$this->s__datos_mesa_llamados['cod_nombre_actividad'] = $this->s__datos_mesa_llamados['codigo'] . ' - ' . $this->s__datos_mesa_llamados['nombre_actividad'];
		}
		return $this->s__datos_mesa_llamados;
	}

	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_controlador($this);
			$this->ev_info->set_columnas_a_mostrar(array('anio_academico', 'nombre_turno_examen', 'nombre', 'nombre_llamado', 'fecha_formato_visual', 'cod_nombre_actividad', 'nombre_ubicacion', 'libro_nombre', 'nro_acta', 'observaciones', 'acta_referencia', 'tipo_acta_descr', 'icono_info_escalas_notas'));
			$this->ev_info->set_columnas_fila(array('anio_academico', 'nombre_turno_examen', 'nombre'), 1);
			$this->ev_info->set_max_columnas_por_fila(2);
			$this->ev_info->set_etiqueta('anio_academico', 'Año Académico');
			$this->ev_info->set_etiqueta('nombre_turno_examen', 'Turno');
			$this->ev_info->set_etiqueta('nombre', 'Mesa');
			$this->ev_info->set_etiqueta('nombre_llamado', 'Llamado');
			$this->ev_info->set_etiqueta('fecha_formato_visual', 'Fecha');
			$this->ev_info->set_etiqueta('cod_nombre_actividad', 'Actividad');
			$this->ev_info->set_etiqueta('nombre_ubicacion', 'Ubicación');
			$this->ev_info->set_etiqueta('nro_acta', 'Acta');
			$this->ev_info->set_etiqueta('libro_nombre', 'Libro');
			$this->ev_info->set_etiqueta('observaciones', 'Observaciones');
			$this->ev_info->set_etiqueta('acta_referencia', 'Acta Original Nro');
			$this->ev_info->set_etiqueta('tipo_acta_descr', 'Tipo');
			$this->ev_info->set_etiqueta('icono_info_escalas_notas', 'Escalas de Notas');
		}
		return $this->ev_info;
	}

	function set_sin_listado_inicial()
	{
		$this->con_listado_inicial = false;
		$this->dep('filtro')->set_sin_filtrado_inicial();
	}

	//--------------------------------------------------------------------------
	//---- cuadro --------------------------------------------------------------
	//--------------------------------------------------------------------------

	function get_msg_eof($etiqueta = null)
	{
		$indx_msg = $this->indx_msg_eof;

		if (!isset($this->s__filtro) && !$this->con_listado_inicial) {
			$indx_msg = $this->indx_msg_eof . '_sinfiltro';
		} elseif (isset($this->s__filtro) && $this->s__filtro != '') {
			$indx_msg = $this->indx_msg_eof . '_filtrado';
		}

		return guarani_mensajes::get($indx_msg, array($etiqueta));
	}

	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		$etiqueta = guarani_etiquetas::get_tabla('sga_mesas_examen');
		$indx_msj = $this->get_msg_eof($etiqueta->get_enf('plural'));
		$cuadro->set_eof_mensaje($indx_msj);

		if ($this->con_listado_inicial || isset($this->s__filtro)) {
			$datos = $this->cn()->get_listado($this->get_filtro_condicion());
			$cuadro->set_datos($datos);
		}
	}

	function evt__cuadro__seleccion($seleccion)
	{
		$this->seleccionar($seleccion);
	}

	function seleccionar($seleccion)
	{
		$this->setear_datos_mesa($seleccion);
		$this->seleccionar_mesa('edicion');
	}

	function get_mesa_seleccionada()
	{
		return $this->s__llamado_mesa_entidad;
	}

	//--------------------------------------------------------------------------
	//---- filtro --------------------------------------------------------------
	//--------------------------------------------------------------------------

	function conf__filtro(toba_ei_filtro $filtro)
	{
		parent::conf__filtro($filtro);
		$filtro->columna('anio_academico')->set_condicion_fija('es_igual_a');
		$filtro->columna('turno_examen')->set_condicion_fija('es_igual_a');
		$filtro->columna('llamado')->set_condicion_fija('es_igual_a');
	}

	//--------------------------------------------------------------------------
	//---- Auxiliares  ---------------------------------------------------------
	//--------------------------------------------------------------------------

	protected function get_datos_evaluacion_mesa()
	{
		// Se busca la evaluación asociada a la comisión seleccionada.
		$where = "	sga_evaluaciones.entidad = $this->s__llamado_mesa_entidad AND
					sga_evaluaciones_tipos.automatica = '" . comunes::si . "' AND
					sga_evaluaciones_tipos.aplica_a = '" . acta::evaluacion_tipo_aplica_mesa . "'";

		return current(toba::consulta_php('co_evaluaciones')->get_listado($where));
	}

	function setear_datos_mesa($seleccion)
	{
		$this->s__llamado_mesa_entidad = $seleccion['entidad'];
		$datos_mesa = toba::consulta_php('co_mesas_de_examen')->get_datos_llamado_mesa_entidad($this->s__llamado_mesa_entidad);
		$datos_mesa['entidad'] = $this->s__llamado_mesa_entidad;
		$this->s__llamado_mesa = $datos_mesa['llamado_mesa'];
		$this->s__mesa_examen = $datos_mesa['mesa_examen'];
	}

	function seleccionar_mesa($pantalla)
	{
		// Carga de atributos de parámetros
		$this->cn()->cargar_atributos_parametros($this->s__mesa_examen);
		
		$datos_mesa['mesa_examen'] = $this->s__mesa_examen;
		$datos_mesa['entidad'] = $this->s__llamado_mesa_entidad;
		$datos_mesa['llamado_mesa'] = $this->s__llamado_mesa;

		$evaluacion = $this->get_datos_evaluacion_mesa();
		if (empty($evaluacion)) {
			// Si no existe la evaluación => mensaje de error.
			$mensaje = guarani_mensajes::get($this->msg_sin_evaluaciones);
			throw guarani::error($mensaje);
		} else {
			// Cargo el cn y seteo la pantalla correspondiente
			$this->s__evaluacion = $evaluacion['evaluacion'];
			$datos_mesa['evaluacion'] = $evaluacion['evaluacion'];
			if (isset($this->s__acta_seleccionada)) {
				$datos_mesa['acta'] = $this->s__acta_seleccionada;
			}
			$id = array('entidad' => $datos_mesa['entidad']);
			$this->cn()->cargar($id, $datos_mesa);
			$this->set_pantalla($pantalla);
		}
	}

	function get_llamado_mesa_entidad()
	{
		return $this->s__llamado_mesa_entidad;
	}

	function get_mesa_examen()
	{
		return $this->s__mesa_examen;
	}

	function get_llamado_mesa()
	{
		return $this->s__llamado_mesa;
	}

	//--------------------------------------------------------------------------
	//---- Eventos -------------------------------------------------------------
	//--------------------------------------------------------------------------
	
	/**
	 * Evento utilizado en caso de que haya dos pantallas de selección: de mesa y de acta. En este caso al cancelar, 
	 * deben resetearse sólo las variables locales y volver la pantalla de selección de mesas.
	 */
	function evt__cancelar_preseleccion()
	{
		$this->resetear_variables();
		$this->set_pantalla('seleccion_mesa');
	}

	/**
	 * Evento utilizado en caso de que sólo haya una pantalla de selección de mesa. En este caso al cancelar, deben resetearse
	 * las variables locales, el cn y las variables de edición, y volver la pantalla de selección de mesas.
	 */
	function evt__cancelar()
	{
		$this->resetear_variables();
		$this->cn()->resetear();
		$this->dependencia('editor')->resetear_variables();
		$this->set_pantalla('seleccion_mesa');
	}

	function resetear_variables()
	{
		unset($this->s__llamado_mesa_entidad);
		unset($this->s__llamado_mesa);
		unset($this->s__mesa_examen);
		unset($this->s__datos_mesa_llamados);
		unset($this->s__evaluacion);
		unset($this->s__pantalla);
	}

}
?>