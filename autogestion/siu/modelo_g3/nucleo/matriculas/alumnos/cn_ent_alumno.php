<?php 
/**
 *
 *
 * @package Matrícula
 * @subpackage Personas/Alumnos
 */

class cn_ent_alumno extends cn_entidad
{		
	//------------------------------------------------------------------------
	//-- Alumno
	//------------------------------------------------------------------------

	function set_datos_alumno($datos)
	{
		$this->tabla('alumnos')->set($datos);
	}

	function set_alumno_plan_version($plan_version)
	{
		$this->tabla('alumnos')->set_columna_valor('plan_version', $plan_version);
	}

	function get_datos_alumno()
	{
		return $this->tabla('alumnos')->get();
	}
	
	function hay_cambio_calidad()
	{
		return $this->tabla('alumnos')->existe_cambio_en_campo('calidad');
	}

	//------------------------------------------------------------------------
	//-- Histórico de Cambios de Ubicación
	//------------------------------------------------------------------------

	//Obtiene los datos de todo el historial de cambios de ubicación correspondientes al alumno.
	//Utiliza un objeto toba_datos_busqueda para poder retornar los cambios de ubicación ordenados por fecha de inicio.
	function get_datos_alum_historial_ubicacion()
	{
		$datos_busq_historial_ubic = $this->tabla('alum_hist_ubicacion')->nueva_busqueda();
		$datos_busq_historial_ubic->set_columnas_orden(array('fecha' => SORT_DESC));
		return $datos_busq_historial_ubic->buscar_filas();		
	}
	
	/**
	 * Obtiene los datos del cambio de ubicacion del alumno correspondiente a la fila dada. 
	 * Si no se provee una fila, entonces obtiene los datos de la fila del historial de cambios de ubicación donde actualmente 
	 * se encuentra posicionado el cursor.
	 */
	function get_datos_cambio_ubicacion($id_fila=null)
	{
		if (isset($id_fila)) {
			return $this->tabla('alum_hist_ubicacion')->get_fila($id_fila);
		} elseif ($this->tabla('alum_hist_ubicacion')->hay_cursor()) {
			return $this->tabla('alum_hist_ubicacion')->get();
		}
	}

	/**
	 * Setea los datos en el cambio de ubicacion del alumno donde actualmente se encuentra posicionado el cursor.
	 * En caso de que se haya creado un nuevo Cambio de Ubicación (y no se esté editando uno anterior), debe cambiarse
	 * la ubicación actual del Alumno a la nueva ubicación.
	 */
	function set_datos_cambio_ubicacion($datos)
	{
		if ($this->tabla('alum_hist_ubicacion')->hay_cursor()) {
			if ($this->es_ubicacion_nueva()) {
				$this->tabla('alumnos')->set_columna_valor('ubicacion', $datos['ubicacion']);
			}
			/**
			 * Si el dato documento es nulo, se resetea la columna documento_numero que se trae por carga externa a partir 
			 * del mismo, ya que al borrar el estado del ef popup y dejar documento en null, esta columna no es considera 
			 * para la realización de la carga externa por lo que seguirá teniendo el valor anterior.
			 */
			if (is_null($datos['documento'])) {
				$datos['documento_numero'] = null;
			}
			$this->tabla('alum_hist_ubicacion')->set($datos);
		}
	}

	//Obtiene el id de la fila actual en la tabla de historial de ubicaciones del alumno.
	function get_cursor_historial_ubicaciones()
	{
		return $this->tabla('alum_hist_ubicacion')->get_cursor();
	}
	
	//Posiciona el cursor de la tabla de historial de ubicaciones en la fila dada.
	function set_cursor_historial_ubicaciones($id_fila)
	{
		$this->tabla('alum_hist_ubicacion')->set_cursor($id_fila);	
	}
	
	//Resetea el cursor de la tabla de historial de ubicaciones.
	function unset_cursor_historial_ubicaciones()
	{
		$this->tabla('alum_hist_ubicacion')->resetear_cursor();	
	}

	//Crea una nueva fila en la tabla de historial de ubicaciones y posiciona el cursor en la misma.
	function nuevo_cambio_ubicacion()
	{
		$cursor = $this->tabla('alum_hist_ubicacion')->nueva_fila();
     	$this->set_cursor_historial_ubicaciones($cursor);
	}
	
	//Elimina la fila actual de la tabla de historial de ubicaciones del alumno.
	function eliminar_cambio_ubicacion()
	{
		$cursor = $this->get_cursor_historial_ubicaciones();
		if (isset($cursor)) {
			$this->tabla('alum_hist_ubicacion')->eliminar_fila($cursor);
		}
	}
	
	/**
	 * Determina si la fila actual del historial de cambios de ubicación es nueva, es decir que el usuario está dando de 
	 * Alta un cambio de ubicación. Para esto chequea si la fila actual posee datos màs allà de la clave interna (en caso 
	 * de edición sí los habrá). Reemplaza al uso de la función es_nueva() del cn_entidad ya que en esta operación la 
	 * relación está cargada desde la preselección del Alumno.
	 */
	function es_ubicacion_nueva()
	{
		if ($this->tabla('alum_hist_ubicacion')->hay_cursor()) {
			$datos = $this->get_datos_cambio_ubicacion();
			return guarani_arreglos::count($datos) == 1;
		}
	}

	//------------------------------------------------------------------------
	//-- Histórico de Cambios de Calidad
	//------------------------------------------------------------------------

	//Obtiene los datos de todo el historial de cambios de calidad correspondientes al alumno.
	//Utiliza un objeto toba_datos_busqueda para poder retornar los cambios de ubicación ordenados por fecha de inicio.
	function get_datos_alum_historial_calidad()
	{
		$datos_busq_historial_calidad = $this->tabla('alum_hist_calidad')->nueva_busqueda();
		$datos_busq_historial_calidad->set_columnas_orden(array('fecha' => SORT_DESC));
		return $datos_busq_historial_calidad->buscar_filas();		
	}
	
	/**
	 * Obtiene los datos del cambio de calidad del alumno correspondiente a la fila dada. 
	 * Si no se provee una fila, entonces obtiene los datos de la fila del historial de cambios de calidad donde actualmente 
	 * se encuentra posicionado el cursor.
	 */
	function get_datos_cambio_calidad($id_fila=null)
	{
		if (isset($id_fila)) {
			return $this->tabla('alum_hist_calidad')->get_fila($id_fila);
		} elseif ($this->tabla('alum_hist_calidad')->hay_cursor()) {
			return $this->tabla('alum_hist_calidad')->get();
		}
	}

	/**
	 * Setea los datos en el cambio de calidad del alumno donde actualmente se encuentra posicionado el cursor.
	 * En caso de que se haya creado un nuevo Cambio de Calidad (y no se esté editando uno anterior), debe cambiarse
	 * la calidad actual del Alumno a la nueva calidad.
	 */
	function set_datos_cambio_calidad($datos)
	{
		if ($this->tabla('alum_hist_calidad')->hay_cursor()) {
			if ($this->es_calidad_nueva()) {
				$this->tabla('alumnos')->set_columna_valor('calidad', $datos['calidad']);
			}
			/**
			 * Si el dato documento es nulo, se resetea la columna documento_numero que se trae por carga externa a partir 
			 * del mismo, ya que al borrar el estado del ef popup y dejar documento en null, esta columna no es considera 
			 * para la realización de la carga externa por lo que seguirá teniendo el valor anterior.
			 */
			if (is_null($datos['documento'])) {
				$datos['documento_numero'] = null;
			}
			$this->tabla('alum_hist_calidad')->set($datos);
		}
	}

	//Obtiene el id de la fila actual en la tabla de historial de calidades del alumno.
	function get_cursor_historial_calidades()
	{
		return $this->tabla('alum_hist_calidad')->get_cursor();
	}
	
	//Posiciona el cursor de la tabla de historial de calidades en la fila dada.
	function set_cursor_historial_calidades($id_fila)
	{
		$this->tabla('alum_hist_calidad')->set_cursor($id_fila);	
	}
	
	//Resetea el cursor de la tabla de historial de calidades.
	function unset_cursor_historial_calidades()
	{
		$this->tabla('alum_hist_calidad')->resetear_cursor();	
	}

	//Crea una nueva fila en la tabla de historial de calidades y posiciona el cursor en la misma.
	function nuevo_cambio_calidad()
	{
		$cursor = $this->tabla('alum_hist_calidad')->nueva_fila();
     	$this->set_cursor_historial_calidades($cursor);
	}
	
	//Elimina la fila actual de la tabla de historial de calidades del alumno.
	function eliminar_cambio_calidad()
	{
		$cursor = $this->get_cursor_historial_calidades();
		if (isset($cursor)) {
			$this->tabla('alum_hist_calidad')->eliminar_fila($cursor);
		}
	}
	
	/**
	 * Determina si la fila actual del historial de cambios de calidad es nueva, es decir que el usuario está dando de 
	 * Alta un cambio de calidad. Para esto chequea si la fila actual posee datos màs allà de la clave interna (en caso 
	 * de edición sí los habrá). Reemplaza al uso de la función es_nueva() del cn_entidad ya que en esta operación la 
	 * relación está cargada desde la preselección del Alumno.
	 */
	function es_calidad_nueva()
	{
		if ($this->tabla('alum_hist_calidad')->hay_cursor()) {
			$datos = $this->get_datos_cambio_calidad();
			return guarani_arreglos::count($datos) == 1;
		}
	}

	//------------------------------------------------------------------------
	//-- Histórico de Cambios de Modalidad
	//------------------------------------------------------------------------

	//Obtiene los datos de todo el historial de cambios de modalidad correspondientes al alumno.
	//Utiliza un objeto toba_datos_busqueda para poder retornar los cambios de modalidad ordenados por fecha de inicio.
	function get_datos_alum_historial_modalidad()
	{
		$datos_busq_historial_modalidad = $this->tabla('alum_hist_modalidad')->nueva_busqueda();
		$datos_busq_historial_modalidad->set_columnas_orden(array('fecha' => SORT_DESC));
		return $datos_busq_historial_modalidad->buscar_filas();		
	}
	
	/**
	 * Obtiene los datos del cambio de modalidad del alumno correspondiente a la fila dada. 
	 * Si no se provee una fila, entonces obtiene los datos de la fila del historial de cambios de modalidad donde 
	 * actualmente se encuentra posicionado el cursor.
	 */
	function get_datos_cambio_modalidad($id_fila=null)
	{
		if (isset($id_fila)) {
			return $this->tabla('alum_hist_modalidad')->get_fila($id_fila);
		} elseif ($this->tabla('alum_hist_modalidad')->hay_cursor()) {
			return $this->tabla('alum_hist_modalidad')->get();
		}
	}

	/**
	 * Setea los datos en el cambio de modalidad del alumno donde actualmente se encuentra posicionado el cursor.
	 * En caso de que se haya creado un nuevo Cambio de Modalidad (y no se esté editando uno anterior), debe cambiarse
	 * la modalidad actual del Alumno a la nueva modalidad, y el plan_version del cambio de modalidad al plan_version
	 * actualmente asociado al Alumno.
	 */
	function set_datos_cambio_modalidad($datos)
	{
		if ($this->tabla('alum_hist_modalidad')->hay_cursor()) {
			if ($this->es_modalidad_nueva()) {
				$this->tabla('alumnos')->set_columna_valor('modalidad', $datos['modalidad']);
				$alumno_datos = $this->get_datos_alumno();
				$datos['plan_version'] = $alumno_datos['plan_version'];
			}
			/**
			 * Si el dato documento es nulo, se resetea la columna documento_numero que se trae por carga externa a partir 
			 * del mismo, ya que al borrar el estado del ef popup y dejar documento en null, esta columna no es considera 
			 * para la realización de la carga externa por lo que seguirá teniendo el valor anterior.
			 */
			if (is_null($datos['documento'])) {
				$datos['documento_numero'] = null;
			}
			$this->tabla('alum_hist_modalidad')->set($datos);
		}
	}

	//Obtiene el id de la fila actual en la tabla de historial de modalidades del alumno.
	function get_cursor_historial_modalidades()
	{
		return $this->tabla('alum_hist_modalidad')->get_cursor();
	}
	
	//Posiciona el cursor de la tabla de historial de modalidades en la fila dada.
	function set_cursor_historial_modalidades($id_fila)
	{
		$this->tabla('alum_hist_modalidad')->set_cursor($id_fila);	
	}
	
	//Resetea el cursor de la tabla de historial de modalidades.
	function unset_cursor_historial_modalidades()
	{
		$this->tabla('alum_hist_modalidad')->resetear_cursor();	
	}

	//Crea una nueva fila en la tabla de historial de modalidades y posiciona el cursor en la misma.
	function nuevo_cambio_modalidad()
	{
		$cursor = $this->tabla('alum_hist_modalidad')->nueva_fila();
     	$this->set_cursor_historial_modalidades($cursor);
	}
	
	//Elimina la fila actual de la tabla de historial de modalidades del alumno.
	function eliminar_cambio_modalidad()
	{
		$cursor = $this->get_cursor_historial_modalidades();
		if (isset($cursor)) {
			$this->tabla('alum_hist_modalidad')->eliminar_fila($cursor);
		}
	}
	
	/**
	 * Determina si la fila actual del historial de cambios de modalidad es nueva, es decir que el usuario está dando de 
	 * Alta un cambio de modalidad. Para esto chequea si la fila actual posee datos màs allà de la clave interna (en caso 
	 * de edición sí los habrá). Reemplaza al uso de la función es_nueva() del cn_entidad ya que en esta operación la 
	 * relación está cargada desde la preselección del Alumno.
	 */
	function es_modalidad_nueva()
	{
		if ($this->tabla('alum_hist_modalidad')->hay_cursor()) {
			$datos = $this->get_datos_cambio_modalidad();
			return guarani_arreglos::count($datos) == 1;
		}
	}

	//------------------------------------------------------------------------
	//-- Histórico de Cambios de Plan
	//------------------------------------------------------------------------

	function nuevo_cambio_plan($datos)
	{
		$this->tabla('alum_hist_planes')->nueva_fila($datos);
	}
}
?>