<?php
/**
 *
 *
 * @package 	Constancias y Certificados
 * @subpackage 	Constancia Exámen
 * 
 */
class ci_constancia_examen extends ci_constancia 
{
	protected $s__datos_filtro;
	protected $s__filtrar;
	protected $indx_msj_eof = '800_eof_cuadro';
	
	/**
	 * Se extiende el validar datos del ci constancia genérico para verificar al Guardar, que se haya 
	 * seleccionado un Llamado de Mesa de Exámen como parámetro de la Solicitud.
	 *
	 */
	function validar_datos()
	{
		/**
		 * El formato de los datos es un arreglo asociativo con único campo correspondiente al Llamado Mesa.
		 */
		if (empty($this->s__datos)) {
			$msj = guarani_mensajes::get('800_no_seleccion', array('un Llamado de Mesa de Exámen'));
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
	
	// Funcion utilizada para obtener los datos a cargar en el combo de anios_academicos
	function get_anios_academicos()
	{
		return guarani::co('co_anios_academicos')->get_anios_academicos();
	}

	// Funcion utilizada para obtener los datos a cargar en el combo de actividades
	function get_actividades_anio_turno($filtro_activ='', $anio_academico)
	{
		$anio_academico = toba::db()->quote($anio_academico);
		$where = "vw_mesas_examen.anio_academico = $anio_academico";
		$filtro = "	sga_elementos.elemento 
						IN (SELECT	vw_mesas_examen.mesa_examen_elemento
							FROM	vw_mesas_examen
							WHERE	$where)
						";
		if (isset($filtro_activ) && trim($filtro_activ) != '') {
			$filtro_activ = guarani_texto::limpiar_acentos($filtro_activ);
			$filtro_condicion = new toba_filtro_condicion('contiene', 'ILIKE', '%', '%', '', '');
			$filtro .= ' AND ('.$filtro_condicion->get_sql('f_limpiar_acentos(sga_elementos.nombre::varchar)', $filtro_activ);
			$filtro .= ' OR '.$filtro_condicion->get_sql('f_limpiar_acentos(sga_elementos.codigo::varchar)', $filtro_activ).')';
		}
		return guarani::co('co_elementos')->get_listado_actividades($filtro);
	}
		
	/**
	 * A partir de los parámetros asociados a la solicitud de constancia (esto es cuando la misma está en edición), 
	 * se recuperan los datos mostrados en el form o filtro que se muestra al usuario para poder modificar los
	 * parámetros de solicitud si lo desea.
	 *
	 */
	function get_datos_iniciales_form($params_solicitud)
	{
		$llamado = $params_solicitud['llamado_mesa'];
		$datos_llamado = toba::consulta_php('co_mesas_de_examen')->get_datos_llamado_mesa($llamado);
		return $datos_llamado;
	}
	
	//-----------------------------------------------------------------------------------
	//---- PANTALLA INICIAL  ------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	//---- formulario de filtro sobre mesas y actividades -------------------------------
	
	function conf__form_filtro_llamados_mesas(toba_ei_formulario $form)
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
	
	function evt__form_filtro_llamados_mesas__modificacion($datos)
	{
		if (isset($datos['anio_academico'])) {
			$this->s__datos_filtro = $datos;
		}
	}

	function evt__form_filtro_llamados_mesas__filtrar($datos)
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

	function evt__form_filtro_llamados_mesas__cancelar()
	{
		// Se limpian los datos y el filtro (condición where) del formulario.
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
			// Se pasa directamente el arreglo de datos iniciales porque el único parámetro es el llamado mesa clave del cuadro.
			$this->set_datos($this->s__datos_iniciales);
		} elseif (!empty($this->s__datos)) {
			// Para el caso en que en un alta se ejecutan validaciones y salta una excepción.
			$datos = $this->s__datos;
		}
		if (isset($datos)) {
			$datos_cuadro = $this->dependencia('cuadro')->get_datos();
			if ($datos_cuadro[$fila]['llamado_mesa'] == $datos['llamado_mesa']) {
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
			// Filtros sobre las Mesas. El año académico es obligatorio.
 			if (isset($this->s__datos_filtro['anio_academico'])) {
 				$filtro .= "sga_periodos.anio_academico = " . $this->s__datos_filtro['anio_academico'];
 			}
			if (isset($this->s__datos_filtro['turno_examen'])) {
				$filtro .= " AND sga_turnos_examen.turno_examen = " . $this->s__datos_filtro['turno_examen'];
			}
			if (isset($this->s__datos_filtro['actividad'])) {
				$filtro .= " AND sga_mesas_examen.elemento = " . $this->s__datos_filtro['actividad']; 
			}
			if (isset($this->s__datos_filtro['fecha_desde'])) {
				$fecha_desde = $this->s__datos_filtro['fecha_desde'];
				$filtro .= " AND sga_llamados_mesa.fecha >= DATE '$fecha_desde'";
			}
			if (isset($this->s__datos_filtro['fecha_hasta'])) {
				$fecha_hasta = $this->s__datos_filtro['fecha_hasta'];
				$filtro .= " AND sga_llamados_mesa.fecha <= DATE '$fecha_hasta'";
			}
			/**
			 * FILTRO SOBRE EL ALUMNO PARA EL CUAL SE ESTÁ SOLICITANDO LA CONSTANCIA:
			 * Se listan los llamados con Acta de examen cerrada, y en cuya acta el alumno solicitante
			 * tiene una nota cargada, excluyendo los ausentes.
			 */
			$alumno = $this->get_alumno_solicitud();
			$filtro_alumno = " AND sga_llamados_mesa.llamado_mesa IN (SELECT	vw_actas.llamado_mesa
																	  FROM	vw_actas
																	  WHERE	vw_actas.alumno = $alumno
																	  AND	vw_actas.resultado <> '". escalas_notas::resultado_ausente ."')";
			$filtro .= $filtro_alumno;
			$llamados_mesa = toba::consulta_php('co_mesas_de_examen')->get_listado($filtro);
			$cuadro->set_datos($llamados_mesa);
			$this->indx_msj_eof .= '_filtrado';
		} else {
			$this->indx_msj_eof .= '_sinfiltro'; 
		}
		
		// Configuración del msj eof.
		$etiqueta = guarani_etiquetas::get_tabla('sga_llamados_mesa');
		$msj = $etiqueta->get_enf('plural') . ' donde el Alumno seleccionado tenga Nota cargada';
		$cuadro->set_eof_mensaje(guarani_mensajes::get($this->indx_msj_eof, array($msj)));
	}
	
	function evt__cuadro__seleccion($seleccion)
	{
		$this->set_datos($seleccion);
	}	
}
?>