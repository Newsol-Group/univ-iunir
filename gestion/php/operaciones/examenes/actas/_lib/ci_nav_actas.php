<?php
/**
 *
 * @package Exámenes
 * @subpackage Actas
 */
class ci_nav_actas extends ci_nav_mesas_actas
{
	function ini()
	{
		
	}

	function conf()
	{
		parent::conf();
		$this->set_sin_listado_inicial();
		$this->s__pantalla = $this->get_id_pantalla();
		if ($this->s__pantalla == 'seleccion_acta') {
			$this->set_titulo('Listado de Actas');
		} else {
			$this->set_titulo('Previsualización de datos');
		}
	}

	function get_datos_acta()
	{
		return $this->s__datos_acta;
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA SELECCIÓN ACTAS -----------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__seleccion_acta(toba_ei_pantalla $pantalla)
	{
		// Redefinición: no se requieren los datos de la mesa.
	}

	//-----------------------------------------------------------------------------------
	//---- filtro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__filtro(toba_ei_filtro $filtro)
	{
		// Si el perfil de datos del usuario pertenece al dominio "Propuestas" es obligatorio filtrar los conceptos por ubicación.
		if (usuario::tiene_perfil_por_propuesta()) {
			$filtro->set_ubicacion_obligatoria();
		}
		if (isset($this->s__datos)) {
			$filtro->set_datos($this->s__datos);
		}
		$filtro->columna('anio_academico')->set_condicion_fija('es_igual_a');
		$filtro->columna('turno_examen')->set_condicion_fija('es_igual_a');
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro_actas -----------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_actas(toba_ei_cuadro $cuadro)
	{
		$etiqueta = guarani_etiquetas::get_tabla('sga_actas');
		$msj = $this->get_msg_eof($etiqueta->get_enf('plural'));
		$cuadro->set_eof_mensaje($msj);

		if ($this->con_listado_inicial || isset($this->s__filtro)) {
			$datos = $this->cn()->get_listado($this->get_filtro_condicion());
			$datos = $this->formatear_semaforo($datos);
			$cuadro->set_datos($datos);
		}
	}

	function seleccionar_acta($seleccion)
	{
		$this->s__acta_seleccionada = $seleccion;
		$this->cn()->cargar_atributos_parametros($this->get_mesa_acta_seleccionada());
		$this->set_pantalla('edicion');
	}

	function seleccionar_mesa($pantalla, $redirigido_a_carga_de_notas = false)
	{
		if ($redirigido_a_carga_de_notas) {
			parent::seleccionar_mesa($pantalla);
			return;
		}

		$datos_mesa['mesa_examen'] = $this->get_mesa_acta_seleccionada();
		$datos_mesa['entidad'] = $this->get_llamado_mesa();
		$datos_mesa['llamado_mesa'] = $this->get_llamado();
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

	//-----------------------------------------------------------------------------------
	//---- PANTALLA EDICIÓN -------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__edicion(toba_ei_pantalla $pantalla)
	{
		$datos = $this->get_datos_ev();
		$datos['icono_info_escalas_notas'] = $this->get_html_icono_info_escalas_notas();
		$this->ev_info()->set_datos($datos);
	}

	function get_datos_ev()
	{
		$filtro = 'sga_actas.id_acta = ' . $this->get_acta_seleccionada();
		$this->s__datos_acta = current(toba::consulta_php('co_actas')->get_listado_actas_examen_con_sin_mesas($filtro));
		return $this->s__datos_acta;
	}

	//-----------------------------------------------------------------------------------
	//---- Configuraciones --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_controlador($this);
			$this->ev_info->set_columnas_no_imprimible(array('acta_referencia'));
			$this->ev_info->set_columnas_a_mostrar(array('nro_acta', 'anio_academico', 'turno_nombre', 'mesa_nombre', 'tipo_acta_descr', 'llamado_nombre', 'libro_nombre', 'fecha_formato_visual', 'cod_nombre_actividad', 'icono_info_escalas_notas', 'ubicacion_nombre'));
			$this->ev_info->set_columnas_fila(array('nro_acta', 'anio_academico', 'turno_nombre'), 1);
			$this->ev_info->set_max_columnas_por_fila(2);
			$this->ev_info->set_etiqueta('nro_acta', 'Acta');
			$this->ev_info->set_etiqueta('anio_academico', 'Año Académico');
			$this->ev_info->set_etiqueta('turno_nombre', 'Turno');
			$this->ev_info->set_etiqueta('mesa_nombre', 'Mesa');
			$this->ev_info->set_etiqueta('tipo_acta_descr', 'Tipo');
			$this->ev_info->set_etiqueta('llamado_nombre', 'Llamado');
			$this->ev_info->set_etiqueta('libro_nombre', 'Libro');
			$this->ev_info->set_etiqueta('fecha_formato_visual', 'Fecha');
			$this->ev_info->set_etiqueta('cod_nombre_actividad', 'Actividad');
			$this->ev_info->set_etiqueta('icono_info_escalas_notas', 'Escalas de Notas');
			$this->ev_info->set_etiqueta('ubicacion_nombre', 'Ubicación');
		}
		return $this->ev_info;
	}

	//-----------------------------------------------------------------------------------
	//---- Operaciones auxiliares -------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	/**
	 * Retorna el HTML de un vínculo (con imagen) a la operación que muestra el detalle de escalas de notas para un acta.
	 */
	function get_html_icono_info_escalas_notas()
	{
		$vinculo = toba::vinculador()->crear_vinculo(toba_proyecto::get_id(), '38000089', array('mesa_examen' => $this->get_mesa_acta_seleccionada()), array('celda_memoria' => 'escala_notas'));
		$javascript = "abrir_popup('info','$vinculo',{'width':'450','height':'630','scrollbars':'yes'})";

		$salida = "<a class='icono-utileria' href='#' onclick=\"$javascript\">";
		$salida .= toba_recurso::imagen_toba('info_chico.gif', true, null, null, 'Ver detalles de escalas');
		$salida .= '</a>';

		return $salida;
	}

}
?>