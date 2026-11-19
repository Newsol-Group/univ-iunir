<?php
/**
 *
 * 
 * @package Exámenes
 * @subpackage Actas
 * 
 */
class ci_nav_cerrar_acta extends ci_nav_actas_zona
{
	function ini()
	{
		$volvio_imp = toba::memoria()->get_parametro('volvio_imp');
		$volvio_edit = toba::memoria()->get_parametro('volvio_edit');
		if (isset($volvio_imp) || isset($volvio_edit)) {
			$this->set_pantalla('edicion');
		}

		//---- Se carga la zona si corresponde ---------------------------------
		if (toba::zona()->cargada() && !$this->cn()->entidad_cargada()) {
			$id_acta = toba::zona()->get_editable();
			$where = "sga_actas.id_acta = " . toba::db()->quote($id_acta);
			$datos = current($this->cn()->get_listado($where));
			$seleccion = array(
				'id_acta' => $datos['id_acta'],
				'mesa_examen' => $datos['mesa_examen'],
				'llamado_mesa_entidad' => $datos['llamado_mesa_entidad'],
				'evaluacion' => $datos['evaluacion'],
				'llamado_mesa' => $datos['llamado_mesa']
			);
			$this->seleccionar_acta($seleccion);
		}
	}

	function conf()
	{
		parent::conf();
		//Se setea el aviso de la deshabilitación del evento de selección de Actas en la pantalla correspondiente.
		if ($this->s__pantalla == 'seleccion_acta') {
			$this->pantalla()->set_descripcion('Sólo pueden seleccionarse Actas Abiertas');
		}
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA SELECCION 
	//-----------------------------------------------------------------------------------
	
	//-----------------------------------------------------------------------------------
	//---- filtro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__filtro(toba_ei_filtro $filtro)
	{
		parent::conf__filtro($filtro);
		$filtro->columna('codigo')->set_condicion_fija('contiene');
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA EDICION -------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	//-----------------------------------------------------------------------------------
	//---- cuadro_actas -----------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf_evt__cuadro_actas__seleccion_acta(toba_evento_usuario $evento, $fila)
	{
		// Si el acta no está Abierta no se permite seleccionarla para agregar o eliminar alumnos de la misma.
		$datos = $this->dep('cuadro_actas')->get_datos();
		if ($datos[$fila]['estado'] != acta::estado_abierta) {
			$evento->ocultar();
		} else {
			$evento->mostrar();
		}
	}

	function evt__cuadro_actas__seleccion_acta($seleccion)
	{
		toba::zona()->cargar($seleccion['id_acta']);
		parent::evt__cuadro_actas__seleccion_acta($seleccion);
	}

	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_controlador($this);
			$this->ev_info->set_columnas_no_imprimible(array('acta_referencia'));
			$this->ev_info->set_columnas_a_mostrar(array('nro_acta', 'version', 'anio_academico', 'turno_nombre', 'mesa_nombre', 'llamado_nombre', 'cod_nombre_actividad', 'icono_info_escalas_notas', 'ubicacion_nombre'));
			$this->ev_info->set_max_columnas_por_fila(2);
			$this->ev_info->set_etiqueta('nro_acta', 'Acta');
			$this->ev_info->set_etiqueta('version', 'Código de Verificación');
			$this->ev_info->set_etiqueta('anio_academico', 'Año Académico');
			$this->ev_info->set_etiqueta('turno_nombre', 'Turno');
			$this->ev_info->set_etiqueta('mesa_nombre', 'Mesa');
			$this->ev_info->set_etiqueta('llamado_nombre', 'Llamado');
			$this->ev_info->set_etiqueta('cod_nombre_actividad', 'Actividad');
			$this->ev_info->set_etiqueta('icono_info_escalas_notas', 'Escalas de Notas');
			$this->ev_info->set_etiqueta('ubicacion_nombre', 'Ubicación');
		}
		return $this->ev_info;
	}

}
?>