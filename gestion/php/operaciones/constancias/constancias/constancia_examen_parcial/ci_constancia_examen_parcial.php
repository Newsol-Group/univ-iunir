<?php
/**
 *
 *
 * @package 	Constancias y Certificados
 * @subpackage 	Constancia Exámen Parcial
 * 
 */
class ci_constancia_examen_parcial extends ci_constancia 
{
	protected $s__datos_filtro;
	protected $s__filtrar;
	protected $indx_msj_eof = '800_eof_cuadro';
	
	/**
	 * Se extiende el validar datos del ci constancia genérico para verificar al Guardar, que se haya 
	 * seleccionado una Evaluación Parcial como parámetro de la Solicitud.
	 *
	 */
	function validar_datos()
	{
		/**
		 * El formato de los datos es un arreglo asociativo con único campo correspondiente a la Evaluación.
		 */
		if (empty($this->s__datos)) {
			$msj = guarani_mensajes::get('800_no_seleccion', array('una Evaluación Parcial'));
			throw guarani::error($msj);
		}
		parent::validar_datos();
	}
	
	//-----------------------------------------------------------------------------------
	//---- Auxiliares  ------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	// Se extiende la función del ci padre para resetear las variables propias.
	function limpiar_datos()
	{
		unset($this->s__datos_filtro);
		$this->s__filtrar=false;
		parent::limpiar_datos();	
	}
		
	/**
	 * A partir de los parámetros asociados a la solicitud de constancia (esto es cuando la misma está en 
	 * edición), se recuperan los datos mostrados en el formulario que se muestra al usuario para poder 
	 * modificarlos si lo desea.
	 *
	 */
	function get_datos_iniciales_form($params_solicitud)
	{
		$evaluacion = $params_solicitud['evaluacion'];
		$datos_evaluacion = toba::consulta_php('co_evaluaciones')->get_datos_evaluacion_comision($evaluacion);
		return $datos_evaluacion;
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA INICIAL  ------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	//---- Filtro sobre Actividades y Comisiones ----------------------------------------
	
	function conf__form_filtro_evaluacion_parcial(toba_ei_formulario $form)
	{
		if (isset($this->s__datos_filtro)) {
			$form->set_datos($this->s__datos_filtro);
			$this->s__filtrar = true;		
		} else {
			if (!empty($this->s__datos_iniciales)) {
				/**
				 * En este caso en la operación de Solicitar Constancias se pasan los parámetros asociados a la
				 * solicitud, para que el ci externo de la constancia complete los datos del formulario a partir
				 * de los mismos.
				 */
				$this->s__datos_filtro = $this->get_datos_iniciales_form($this->s__datos_iniciales);
				$this->s__filtrar = true;
				$form->set_datos($this->s__datos_filtro);
			} 
		}
		// La obligatoriedad del filtro por año académico se chequea por código, se setea el estilo de etiqueta.
		$form->ef('anio_academico')->set_estilo_etiqueta('ei-form-etiq-oblig');		
		$etiqueta = $form->ef('anio_academico')->get_etiqueta() . ' (*)';
		$form->ef('anio_academico')->set_etiqueta($etiqueta);
	}
	
	function evt__form_filtro_evaluacion_parcial__modificacion($datos)
	{
		if (isset($datos['anio_academico'])) {
			$this->s__datos_filtro = $datos;
		}
	}

	function evt__form_filtro_evaluacion_parcial__filtrar($datos)
	{
		if (!isset($datos['anio_academico'])) {
			$msj = guarani_mensajes::get('dato_faltante', array('Año Académico'));
			throw guarani::error($msj);
		} else {
			$this->s__datos_filtro = $datos;
			$this->s__filtrar = true;
			// Se resetea el valor de los datos de parámetros de la solicitud y datos iniciales si los hay.
			parent::limpiar_datos();
		} 
	}

	function evt__form_filtro_evaluacion_parcial__cancelar()
	{
		// Se limpian los datos y el filtro.
		unset($this->s__datos_filtro);
		$this->s__filtrar = false;
		// Se resetea el valor de los datos de parámetros de la solicitud y datos iniciales si los hay.
		parent::limpiar_datos();
	}
	
	//---- cuadro_actividades ----------------------------------------------------------
	
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
			if ($datos_cuadro[$fila]['evaluacion'] == $datos['evaluacion']) {
				$evento->set_check_activo(true);  
			} else {
				$evento->set_check_activo(false);
			}
		}
	}
	
	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		if ($this->s__filtrar) {
			$filtro = '';

			//seteo condicion de fecha debería listar evaluaciones cuya fecha sea menor o igual a la actual
			$filtro .=" sga_evaluaciones.fecha <= current_date ";
			// Filtros sobre las Comisiones y Actividades. El año académico es obligatorio.
 			if (isset($this->s__datos_filtro['anio_academico'])) {
 				$filtro .= "AND vw_periodos_lectivos.anio_academico = " . $this->s__datos_filtro['anio_academico'];
 			}
			if (isset($this->s__datos_filtro['periodo_lectivo'])) {
				$filtro .= " AND sga_comisiones.periodo_lectivo = " . $this->s__datos_filtro['periodo_lectivo'];
			}
			if (isset($this->s__datos_filtro['elemento'])) {
				$filtro .= " AND sga_comisiones.elemento = " . $this->s__datos_filtro['elemento']; 
			}
			if (isset($this->s__datos_filtro['comision'])) {
				$filtro .= " AND sga_comisiones.comision = " . $this->s__datos_filtro['comision']; 
			}
			if (isset($this->s__datos_filtro['fecha_desde'])) {
				$fecha_desde = $this->s__datos_filtro['fecha_desde'];
				$filtro .= " AND sga_evaluaciones.fecha >= DATE '$fecha_desde'";
			}
			if (isset($this->s__datos_filtro['fecha_hasta'])) {
				$fecha_hasta = $this->s__datos_filtro['fecha_hasta'];
				$filtro .= " AND sga_evaluaciones.fecha <= DATE '$fecha_hasta'";
			}
		
			// Se obtiene el alumno para el cual se está realizando la solicitud de constancia.
			$alumno = $this->get_alumno_solicitud();
			$evaluaciones = toba::consulta_php('co_evaluaciones')->get_evaluaciones_comision_alumno($alumno, $filtro);
			$cuadro->set_datos($evaluaciones);
			$this->indx_msj_eof .= '_filtrado';
		} else {
			$this->indx_msj_eof .= '_sinfiltro'; 
		}
		// Configuración del msj eof.
		$etiqueta = guarani_etiquetas::get_tabla('sga_evaluaciones');
		$etiqueta = $etiqueta->get_enf('plural') . ' Parciales para el Alumno ';
		$msj = guarani_mensajes::get($this->indx_msj_eof, array($etiqueta));
		$cuadro->set_eof_mensaje($msj);
	}
	
	function evt__cuadro__seleccion($seleccion)
	{
		$this->set_datos($seleccion);
	}

	function get_actividades_con_comision_propuesta($filtro_activ , $anio_academico)
	{	
		$propuesta = toba::consulta_php('co_alumnos')->get_alumno_propuesta($this->get_alumno_solicitud());
		
		$actividades_con_comision_alumno=toba::consulta_php('co_elementos')->get_actividades_con_comision($filtro_activ, $anio_academico,  $propuesta);
		return $actividades_con_comision_alumno;

	}	
}
?>
