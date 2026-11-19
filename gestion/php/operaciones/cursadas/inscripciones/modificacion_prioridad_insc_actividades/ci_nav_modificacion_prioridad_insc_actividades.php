<?php
/**
 * 
 * 
 * @package 	Cursadas
 * @subpackage 	Inscripciones
 * 
 */
class ci_nav_modificacion_prioridad_insc_actividades extends ci_nav_con_preseleccion
{
	protected $s__preseleccion;
	protected $s__seleccion;
	
	// Mensajes
	protected $msg_eof_cuadro = '800_eof_cuadro_sinfiltro';
	protected $msg_filtro_vacio = '800_conf_filtrovacio';
	
	// Componente visual de información
	protected $ev_info;

	function set_preseleccion($seleccion)
	{
		$this->s__preseleccion = $seleccion;
	}

	function get_alumno_preseleccionado()
	{
		return $this->s__preseleccion['alumno'];
	}

	function get_actividad_seleccionada()
	{
		return $this->s__seleccion['elemento'];
	}

	//--------------------------------------------------------------------------
	//---- PANTALLA: Selección de alumno (preseleccion)
	//--------------------------------------------------------------------------

	function conf__preseleccion()
	{
		$this->set_concepto_preseleccion('Alumnos');
	}

	//--------------------------------------------------------------------------
	//---- cuadro_preseleccion -------------------------------------------------
	//--------------------------------------------------------------------------
	
	/**
	 * Redefinición de la operación para evitar traer el listado completo inicialmente.
	 */
	function conf__cuadro_preseleccion(toba_ei_cuadro $cuadro)
	{
		if (isset($this->s__datos_preseleccion)) {
			$datos = $this->cn()->get_listado_preseleccion($this->s__filtro_preseleccion);
			$cuadro->set_datos($datos);
			$msj = $this->get_msg_eof_preseleccion(isset($this->s__filtro_preseleccion));
			$cuadro->set_eof_mensaje($msj);
		} else {
			// Debe filtrar para obtener datos
			$cuadro->set_eof_mensaje(guarani_mensajes::get($this->msg_eof_cuadro));
		}
	}

	//--------------------------------------------------------------------------
	//---- PANTALLA: Selección de actividad (seleccion)
	//--------------------------------------------------------------------------
	
	//--------------------------------------------------------------------------
	//---- ev_info -------------------------------------------------------------
	//--------------------------------------------------------------------------

	function conf__seleccion()
	{
		// Se establecen los datos al elemento visual de información
		$datos = guarani::co('co_alumnos')->get_datos_basicos_alumno($this->get_alumno_preseleccionado());
		$this->ev_info()->set_datos($datos);
	}

	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();

			$this->ev_info->set_columnas_a_mostrar(array('apellido_y_nombre', 'documento_principal', 'legajo', 'propuesta_nombre', 'plan_nombre', 'plan_version_nombre', 'ubicacion_nombre'));

			$this->ev_info->set_columnas_fila(array('apellido_y_nombre', 'documento_principal', 'legajo'), 1);
			$this->ev_info->set_columnas_fila(array('propuesta_nombre', 'plan_nombre', 'plan_version_nombre', 'ubicacion_nombre'), 2);

			$this->ev_info->set_max_columnas_por_fila(4);

			$this->ev_info->set_etiqueta('apellido_y_nombre', 'Alumno');
			$this->ev_info->set_etiqueta('documento_principal', 'Identificación');
			$this->ev_info->set_etiqueta('legajo', 'Legajo');

			$this->ev_info->set_etiqueta('propuesta_nombre', 'Propuesta');
			$this->ev_info->set_etiqueta('plan_nombre', 'Plan');
			$this->ev_info->set_etiqueta('plan_version_nombre', 'Versión');
			$this->ev_info->set_etiqueta('ubicacion_nombre', 'Ubicación');
		}
		
		return $this->ev_info;
	}

	//--------------------------------------------------------------------------
	//---- cuadro --------------------------------------------------------------
	//--------------------------------------------------------------------------

	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		$datos = $this->cn()->get_listado_actividades_disp_cambio($this->get_alumno_preseleccionado());
		$cuadro->set_datos($datos);
		if (empty($datos)) {
			$this->indx_msj_eof = '800_eof_cuadro';
			if (!isset($this->s__filtro) && !$this->con_listado_inicial()) {
				$this->indx_msg_eof .= '_sinfiltro';
			}
			$cuadro->set_eof_mensaje($this->get_msg_eof());
		}
	}

	function evt__cuadro__seleccion($seleccion)
	{
		$this->s__seleccion = $seleccion;
		$condiciones = array('nro_transaccion' => $seleccion['nro_transaccion'], 'tipo' => inscripcion_cursada::tipo_preinscripcion, 'estado_preinscripcion' => inscripcion_cursada::estado_pendiente, 'fuera_de_termino' => comunes::no);
		$this->cn()->cargar($condiciones);
		$this->set_pantalla('edicion');
	}

	//--------------------------------------------------------------------------
	//---- JAVASCRIPT ----------------------------------------------------------
	//--------------------------------------------------------------------------

	function extender_objeto_js()
	{
		parent::extender_objeto_js();
		$msj_confirm_consulta = guarani_mensajes::get($this->msg_filtro_vacio, array('Alumnos'));
		$id_js_filtro = $this->dependencia('filtro_preseleccion')->get_id_objeto_js();
		// La confirmación de la consulta se realiza sólo si se está en la pantalla de selección (ya que ni el filtro ni su evento
		// Filtrar existen en la pantalla de edición).
		if ($this->get_id_pantalla() == 'preseleccion') {
			echo "
				//Chequea si ninguno de los campos (columnas) del filtro tienen estado, es decir el filtro es vacío, el usuario
				//debe confirmar la ejecución de la consulta.
				{$id_js_filtro}.evt__filtrar = function() {
					//Obtiene las filas y los campos de la variable interna que se mantiene en el filtro de columnas y valores (ei_filtro.js).
					var filas = this._filas;
					var campos = this._efs;
					var confirmar_cons = true;
					for (id_fila in filas) {
						var id_ef = this._filas[id_fila];
						if (campos[id_ef].tiene_estado()) {
							confirmar_cons = false;
							break;
						}
					}
					if (confirmar_cons) {
						return confirmar_consulta();
					}
				}
				
				function confirmar_consulta(proyecto, operacion, url, es_popup) {
					if (! es_popup || typeof(es_popup) == 'undefined') {
						resp = confirm('$msj_confirm_consulta');
						if (resp) {
							return true;
						} else {
							return false;
						}
					}
					return true;
				}
		";
		}
	}

	//--------------------------------------------------------------------------
	//---- Eventos -------------------------------------------------------------
	//--------------------------------------------------------------------------

	function evt__volver_seleccion()
	{
		unset($this->s__preseleccion);
		$this->set_pantalla('preseleccion');
	}

	function evt__guardar()
	{
		$this->cn()->entidad()->sincronizar();
		
		unset($this->s__preseleccion);
		unset($this->s__seleccion);
		$this->dep('editor')->unset_vars();
		$this->cn()->resetear();
		$this->set_pantalla('preseleccion');
		
		toba::notificacion()->agregar($this->get_mensaje('guardar_ok'), 'info');
	}

	function evt__cancelar()
	{
		unset($this->s__seleccion);
		$this->dep('editor')->unset_vars();
		$this->cn()->resetear();
		$this->set_pantalla('seleccion');
	}

}
?>