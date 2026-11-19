<?php
/**
 *
 *
 * @package Cursadas
 * @subpackage Comisiones
 */
class ci_nav_carga_notas extends ci_navegacion
{
	// Mensajes
	protected $msg_sin_evaluaciones = '800_sin_evaluacion';

	// Comisión seleccionada y su evaluación asociada.
	protected $s__comision_seleccionada;
	protected $s__datos_comision;
	protected $s__evaluacion;

	// Componente Visual de Información.
	protected $ev_info;

	function ini()
	{
		if (toba::zona()->cargada() && !$this->cn()->entidad_cargada()) {
			$this->seleccionar(array('entidad' => toba::zona()->get_editable()));
		}
	}

	function conf__seleccion_alumnos()
	{
		$this->ev_info()->set_datos($this->s__datos_comision);
		$this->hay_cambios = $this->cn()->hay_cambios_seleccion_alumnos();
	}

	function conf__edicion(toba_ei_pantalla $pantalla)
	{
		$this->hay_cambios = $this->cn()->hay_cambios();
	}

	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_columnas_a_mostrar(array('nombre', 'nombre_turno', 'nombre_modalidad', 'anio_academico_periodo', 'nombre_periodo', 'codigo_y_nombre_actividad', 'nombre_ubicacion'));
			$this->ev_info->set_columnas_fila(array('nombre', 'nombre_turno', 'nombre_modalidad'), 1);
			$this->ev_info->set_columnas_fila(array('anio_academico_periodo', 'nombre_periodo'), 2);
			$this->ev_info->set_columnas_fila(array('codigo_y_nombre_actividad', 'nombre_ubicacion'), 3);
			$this->ev_info->set_max_columnas_por_fila(3);
			$this->ev_info->set_etiqueta('nombre', 'Comisión');
			$this->ev_info->set_etiqueta('nombre_turno', 'Turno');
			$this->ev_info->set_etiqueta('nombre_modalidad', 'Modalidad');
			$this->ev_info->set_etiqueta('anio_academico_periodo', 'Año Académico');
			$this->ev_info->set_etiqueta('nombre_periodo', 'Período Lectivo');
			$this->ev_info->set_etiqueta('codigo_y_nombre_actividad', 'Actividad');
			$this->ev_info->set_etiqueta('nombre_ubicacion', 'Ubicación');
		}
		return $this->ev_info;
	}

	//-----------------------------------------------------------------------------------
	//---- operaciones auxiliares -------------------------------------------------------
	//-----------------------------------------------------------------------------------

	/**
	 * Retorna la entidad de la comisión seleccionada. 
	 * 
	 */
	function get_comision_seleccionada()
	{
		return $this->s__comision_seleccionada['entidad'];
	}

	function get_datos_comision()
	{
		return $this->s__datos_comision;
	}

	function get_evaluacion()
	{
		return $this->s__evaluacion;
	}

	function seleccionar($seleccion)
	{
		$this->s__comision_seleccionada = $seleccion;

		// Se obtienen los datos de la comisión seleccionada a partir de su código de entidad.
		$filtro = "sga_comisiones.entidad = {$this->get_comision_seleccionada()}";
		$select = "	sga_periodos_lectivos.fecha_inicio_dictado, 
					sga_periodos_lectivos.fecha_fin_dictado,
					f_instancias_comision_escala_notas(sga_comisiones.comision, true) as instancias_escalas";
		$this->s__datos_comision = current(toba::consulta_php('co_comisiones')->get_listado($filtro, $select));

		// Se busca la evaluación asociada a la comisión seleccionada.
		$where = "	sga_evaluaciones.entidad = {$this->get_comision_seleccionada()} AND
					sga_evaluaciones_tipos.automatica = '".comunes::si."' AND
					sga_evaluaciones_tipos.aplica_a = '".acta::evaluacion_tipo_aplica_comision."'";
		$datos = current(toba::consulta_php('co_evaluaciones')->get_listado($where));

		if (empty($datos)) {
			// Si no existe la evaluación => mensaje de error.
			$mensaje = guarani_mensajes::get($this->msg_sin_evaluaciones);
			throw guarani::error($mensaje);
		} else {
			$this->s__evaluacion = $datos['evaluacion'];
			$this->cn()->cargar(array('evaluacion' => $this->s__evaluacion));
			
			// Carga de atributos de parámetros
			$this->cn()->cargar_atributos_parametros($this->s__datos_comision['comision']);
			
			$this->set_pantalla('seleccion_alumnos');
		}
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA: seleccion_comision
	//-----------------------------------------------------------------------------------

	function conf__seleccion_comision(toba_ei_pantalla $pantalla)
	{
		$this->cn()->resetear_atributos_parametros();
	}
	
	//-----------------------------------------------------------------------------------
	//---- cuadro_comision --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_comision(toba_ei_cuadro $cuadro)
	{
		if (!isset($this->s__filtro)) {
			$msg_eof = guarani_mensajes::get($this->indx_msg_eof.'_sinfiltro');
		} else {
			$msg_eof = $this->get_msg_eof('Comisiones');
			$filtro_activas = "sga_comisiones.estado = '" . comunes::estado_activo . "'";
			$filtro = empty($this->s__filtro) ? $filtro_activas : $this->s__filtro . ' AND ' . $filtro_activas;

			$datos = toba::consulta_php('co_comisiones')->get_listado($filtro);
			$cuadro->set_datos($datos);
		}
		$cuadro->set_eof_mensaje($msg_eof);
	}

	function evt__cuadro_comision__seleccion($seleccion)
	{
		$this->seleccionar($seleccion);
		toba::zona()->cargar($this->get_comision_seleccionada());
	}

	//-----------------------------------------------------------------------------------
	//---- filtro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__filtro(toba_ei_filtro $filtro)
	{
		parent::conf__filtro($filtro);
		$filtro->columna('anio_academico')->set_condicion_fija('es_igual_a');
		$filtro->columna('periodos_nombre')->set_condicion_fija('es_igual_a');
		$filtro->columna('actividad')->set_condicion_fija('contiene');
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__cancelar_alumnos()
	{
		toba::zona()->resetear();
		$this->dep('selector_alumnos')->resetear_vars();
		$this->resetear_vars();
		$this->set_pantalla('seleccion_comision');
	}

	function evt__cancelar()
	{
		$this->set_pantalla('seleccion_alumnos');
	}

	/**
	 * Resetea las variables de sesión de este CI
	 *
	 */
	function resetear_vars()
	{
		unset($this->s__comision_seleccionada);
		unset($this->s__datos_comision);
		unset($this->s__evaluacion);
	}

	function evt__notas()
	{
		$this->set_pantalla('edicion');
	}

	function evt__guardar()
	{
		// Sincronización de las notas cargadas, actualización de la versión de actas (si existen).
		$condiciones = array('agregado' => true);
		$this->cn()->sincronizar_carga_notas($condiciones);
		
		toba::notificacion()->info('Se guardó correctamente.');		
		
	}

	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		$msj_confirmacion = guarani_mensajes::get('800_abandono_edicion');
		$msj_sin_cambios = guarani_mensajes::get('800_no_hubo_cambios');
		$msj_sin_seleccion_alumnos = guarani_mensajes::get('800_sin_seleccion_alumnos');

		$pantalla = $this->get_id_pantalla();
		switch ($pantalla) {
			case 'seleccion_alumnos':
				if ($this->hay_cambios) {
					$confirmar = 'var confirmar = true;';
				} else {
					$confirmar = 'var confirmar = false;';
				}
				echo "	{$this->objeto_js}.evt__notas = function() {
							$confirmar
							if (! confirmar) {
								alert('$msj_sin_seleccion_alumnos');
								return false;
							}
							return true;
						}
					";
				break;
			case 'edicion':
				if ($this->hay_cambios) {
					$confirmar = 'var confirmar = true;';
				} else {
					$confirmar = 'var confirmar = toba.hay_cambios();';
				}
				echo "	{$this->objeto_js}.evt__guardar = function() {
							$confirmar
							if (! confirmar) {
								alert('$msj_sin_cambios');
								return false;
							}
							return true;
						}
						{$this->objeto_js}.evt__cancelar = function() {
							$confirmar
							if (confirmar) {
								resp = confirm('$msj_confirmacion');
								if (! resp) {
									return false;
								}
							}
							return true;
						}
						toba.set_callback_menu({$this->objeto_js}.evt__cancelar);
					";
				break;
		}
	}

}
?>