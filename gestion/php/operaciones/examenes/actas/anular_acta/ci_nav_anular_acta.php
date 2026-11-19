<?php
/**
 *
 * @package Exámenes
 * @subpackage Actas
 */
class ci_nav_anular_acta extends ci_nav_actas_zona
{
	//-----------------------------------------------------------------------------------
	//---- Configuraciones --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf()
	{
		parent::conf();
		// Se setea el aviso de la deshabilitación del evento de selección de Actas en la pantalla correspondiente.
		if ($this->s__pantalla == 'seleccion_acta') {
			$this->pantalla()->set_descripcion('Sólo pueden seleccionarse Actas Abiertas');
		}
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA SELECCION ACTAS 
	//-----------------------------------------------------------------------------------
	
	//-----------------------------------------------------------------------------------
	//---- cuadro_actas -----------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function get_listado_actas()
	{
		$mesa = toba::db()->quote($this->get_mesa_examen());
		$estado_abierta = toba::db()->quote(acta::estado_abierta);
		$filtro = " sga_mesas_examen.mesa_examen = $mesa AND";
		$filtro .= " sga_actas.estado = $estado_abierta";
		return toba::consulta_php('co_actas')->get_listado_actas_examen($filtro);
	}

	//-----------------------------------------------------------------------------------
	//---- filtro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__filtro(toba_ei_filtro $filtro)
	{
		parent::conf__filtro($filtro);
		$filtro->columna('codigo')->set_condicion_fija('contiene');
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA EDICION 
	//-----------------------------------------------------------------------------------

	function conf__edicion(toba_ei_pantalla $pantalla)
	{
		parent::conf__edicion($pantalla);
		$this->ev_info()->ocultar_columnas(array('observaciones', 'fecha_formato_visual'));
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA INFORMACION 
	//-----------------------------------------------------------------------------------

	function conf__informacion(toba_ei_pantalla $pantalla)
	{
		$datos = $this->get_datos_ev();
		$this->ev_info_resumen()->set_datos($datos);
	}

	//-----------------------------------------------------------------------------------
	//---- ev_info ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function ev_info_resumen()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_controlador($this);
			$this->ev_info->set_style('border-style: solid; border-width: 1px; border-color: #DDD;');
			$this->ev_info->set_style_fila('height: 30px;');
			$this->ev_info->set_columnas_a_mostrar(array('nro_acta', 'libro_nombre', 'anio_academico', 'turno_nombre', 'mesa_nombre', 'llamado_nombre', 'fecha_formato_visual', 'ubicacion_nombre', 'cod_nombre_actividad'));
			$this->ev_info->set_columnas_fila(array('nro_acta', 'libro_nombre', 'anio_academico'), 1);
			$this->ev_info->set_max_columnas_por_fila(3);
			$this->ev_info->set_etiqueta('nro_acta', 'Acta Nro.');
			$this->ev_info->set_etiqueta('libro_nombre', 'Libro');
			$this->ev_info->set_etiqueta('anio_academico', 'Año Académico');
			$this->ev_info->set_etiqueta('turno_nombre', 'Turno');
			$this->ev_info->set_etiqueta('mesa_nombre', 'Mesa');
			$this->ev_info->set_etiqueta('llamado_nombre', 'Llamado');
			$this->ev_info->set_etiqueta('fecha_formato_visual', 'Fecha');
			$this->ev_info->set_etiqueta('ubicacion_nombre', 'Ubicación');
			$this->ev_info->set_etiqueta('cod_nombre_actividad', 'Actividad');
		}
		return $this->ev_info;
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__cancelar_preseleccion()
	{
		if ($this->s__pantalla == 'informacion') {
			if ((guarani::parametros()->get_valor('exa_imprimir_acta_anulada')) == comunes::si) {
				$mensaje = 'Se debe imprimir el acta recientemente anulada.';
				throw guarani::error($mensaje);
			}
			$this->cn()->resetear();
		}
		$this->resetear_variables();
		toba::zona()->resetear();
		$this->set_pantalla('seleccion_acta');
	}

	function evt__anular()
	{
		//Se realizan todas las acciones para anular el acta seleccionada
		$observaciones = $this->dep('editor')->get_observaciones();
		$this->cn()->anular_acta($this->get_acta_seleccionada(), $this->get_evaluacion(), $observaciones);

		// Se resetea la zona
		toba::zona()->resetear();

		//Se resetea el cn
		$this->cn()->resetear();

		//Seteo la pantalla que informa del éxito en la anulación del acta
		$this->set_pantalla('informacion');
	}

}
?>