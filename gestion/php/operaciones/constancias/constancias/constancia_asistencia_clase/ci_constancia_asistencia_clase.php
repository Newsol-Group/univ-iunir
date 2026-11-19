<?php
class ci_constancia_asistencia_clase extends ci_constancia
{
	protected $s__datos_filtro;
	protected $s__filtrar;
	protected $indx_msj_eof = '800_eof_cuadro';

	// Se extiende la función del ci padre para resetear las variables propias.
	function limpiar_datos() 
	{
		unset($this->s__datos_filtro);
		$this->s__filtrar = false;
		parent::limpiar_datos();
	}
	
	function get_datos_iniciales_form($params_solicitud) 
	{
		$clase = $params_solicitud['clase'];
		$datos_clase = current(toba::consulta_php('co_clases')->get_clases_alumno_asistio($this->get_alumno_solicitud(), ' AND sga_clases.clase = '.$clase));
		return $datos_clase;
	}
	
	function get_inscripciones_comision_alumno($periodo_lectivo)
	{		
		$where = ' AND vw_comisiones.periodo_lectivo = '.$periodo_lectivo;
		return guarani::co('co_inscripciones_cursadas')->get_inscripciones_actividad_alumno($this->get_alumno_solicitud(), $where);
	}
	
	
	/**
	 * Se extiende el validar datos del ci constancia genérico para verificar al Guardar, que se haya 
	 * seleccionado un Curso como parámetro de la Solicitud.
	 *
	 */
	function validar_datos()
	{
		/**
		 * El formato de los datos es un arreglo asociativo con único campo correspondiente al Llamado Mesa.
		 */
		if (empty($this->s__datos)) {
			$msj = guarani_mensajes::get('800_no_seleccion', array('una clase'));
			throw guarani::error($msj);
		}
		parent::validar_datos();
	}

	function conf__cuadro(toba_ei_cuadro $cuadro) 
	{
		if ($this->s__filtrar) {			
			$filtro = '';			
			if (isset($this->s__datos_filtro['anio_academico'])) {
				$filtro .= " AND vw_comisiones.anio_academico = ". $this->s__datos_filtro['anio_academico']; 
			}
			if (isset($this->s__datos_filtro['periodo_lectivo'])) {
				$filtro .= " AND vw_comisiones.periodo_lectivo  = " . $this->s__datos_filtro['periodo_lectivo']; 
			}
			if (isset($this->s__datos_filtro['comision'])) {
				$filtro .= " AND vw_comisiones.comision = ". $this->s__datos_filtro['comision'];
			}
			if (isset($this->s__datos_filtro['elemento_codigo'])) {
				$filtro .= " AND vw_comisiones.elemento = ". quote($this->s__datos_filtro['elemento_codigo']);
			}
			if (isset($this->s__datos_filtro['fecha_desde'])) {
				$fecha_desde = $this->s__datos_filtro['fecha_desde'];
				$filtro .= " AND sga_clases.fecha >= DATE '$fecha_desde'";
			}
			if (isset($this->s__datos_filtro['fecha_hasta'])) {
				$fecha_hasta = $this->s__datos_filtro['fecha_hasta'];
				$filtro .= " AND sga_clases.fecha <= DATE '$fecha_hasta'";
			}
			
			$alumno = $this->get_alumno_solicitud();
			$this->indx_msj_eof .= '_filtrado';
			
			$clases_asistio = toba::consulta_php('co_clases')->get_clases_alumno_asistio($alumno, $filtro);
			$cuadro->set_datos($clases_asistio);
		} else {
			$this->indx_msj_eof .= '_sinfiltro';			
		}
		// Configuración del msj eof.
		$etiqueta = ' Clases para el Alumno ';
		$msj = guarani_mensajes::get($this->indx_msj_eof, array($etiqueta));
		$cuadro->set_eof_mensaje($msj);
	}
	
	/**
	 * Si están seteados los datos iniciales, se seleccionará la fila indicada por el llamado_mesa que es parámetro de la 
	 * solicitud de la constancia. Para esto es necesario setear el check activo del cuadro de selección diferida para dicha
	 * fila.
	*/
	function conf_evt__cuadro__seleccion(toba_evento_usuario $evento, $fila)
	{
		if (!empty($this->s__datos_iniciales)) {
			// Para el caso en que ya se configura con una selección en edición.
			$datos = $this->s__datos_iniciales;
			// Se pasa directamente el arreglo de datos iniciales porque el único parámetro es la evalución clave del cuadro.
			$this->set_datos($this->s__datos_iniciales);
		} elseif (!empty($this->s__datos)) {
			// Para el caso en que en un alta se ejecutan validaciones y salta una excepción.
			$datos = $this->s__datos;
		}
		if (isset($datos)) {
			$datos_cuadro = $this->dependencia('cuadro')->get_datos();
			if ($datos_cuadro[$fila]['clase'] == $datos['clase']) {
				$evento->set_check_activo(true);  
			} else {
				$evento->set_check_activo(false);
			}
		}
	}
	
	function evt__cuadro__seleccion($seleccion)
	{
		$this->set_datos($seleccion);
	}

	//-----------------------------------------------------------------------------------
	//---- form_filtro_aprobacion_curso -------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_filtro_asistencia_clase(guarani_pers_ei_formulario $form) 
	{
		if (isset($this->s__datos_filtro)) {
			$form->set_datos($this->s__datos_filtro);
			$this->s__filtrar = true;
		} else {
			if (!empty($this->s__datos_iniciales)) {				
				 /**
				 *  En este caso en la operación de Solicitar Constancias se pasan los parámetros asociados a la
				 * solicitud, para que el ci externo de la constancia complete los datos del formulario a partir
				 * de los mismos.
				 */				 
				$this->s__datos_filtro = $this->get_datos_iniciales_form($this->s__datos_iniciales);
				$this->s__filtrar = true;				
				$form->set_datos($this->s__datos_filtro);
			}		
		}
	}

	function evt__form_filtro_asistencia_clase__filtrar($datos) 
	{
		$this->s__datos_filtro = $datos;
		$this->s__filtrar = true;
		// Se resetea el valor de los datos de parámetros de la solicitud y datos iniciales si los hay.
		parent::limpiar_datos();
	}

	function evt__form_filtro_asistencia_clase__limpiar() 
	{		
		// Se limpian los datos y el filtro.
		unset($this->s__datos_filtro);
		$this->s__filtrar = false;
		// Se resetea el valor de los datos de parámetros de la solicitud y datos iniciales si los hay.
		parent::limpiar_datos();
	}
}

?>