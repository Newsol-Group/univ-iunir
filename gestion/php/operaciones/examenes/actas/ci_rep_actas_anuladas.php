<?php
/**
 *
 * @package 	Exámenes
 * @subpackage 	Reportes
 * 
 */
class ci_rep_actas_anuladas extends ci_rep_sin_listado_inicial
{
	protected $s__acta_seleccionada;
	protected $ev_info;

	protected $s__filtro_detalles;
	protected $s__datos_detalles;

	function get_tabla_principal()
	{
		return 'sga_actas';
	}

	function get_datos($filtro)
	{
		return toba::consulta_php('co_actas')->get_listado_actas_examen_anuladas($filtro);
	}
	
	//---- filtro -----------------------------------------------------------------------

	function conf__filtro(toba_ei_filtro $filtro)
	{
		parent::conf__filtro($filtro);
		$filtro->columna('instancia')->set_condicion_fija('es_igual_a');
		$filtro->columna('actividad')->set_condicion_fija('contiene');
	}
	
	function evt__filtro__filtrar($datos)
	{
		if (isset($datos)) {
			$this->s__datos = $datos;
			$filtro = '';
			
			if (isset($datos['instancia'])) {
				$filtro = " AND EXISTS (SELECT 	* 
										FROM 	sga_mesas_examen_instancias 
										WHERE 	sga_mesas_examen_instancias.mesa_examen = sga_mesas_examen.mesa_examen
												AND sga_mesas_examen_instancias.instancia = '{$datos['instancia']['valor']}')
										";
				$this->dep('filtro')->columna('instancia')->resetear_estado();
			}
			
			$this->s__filtro = "{$this->dep('filtro')->get_sql_where()} $filtro";
		} else {
			unset($this->s__datos);
			$this->s__filtro = '';
		}
	}

	//---- cuadro -----------------------------------------------------------------------

	function evt__cuadro__seleccion($seleccion)
	{
		$this->s__acta_seleccionada = $seleccion['id_acta'];
		$this->set_pantalla('detalle');
	}

	//---- ev_info ----------------------------------------------------------------------

	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_controlador($this);
			$this->ev_info->set_columnas_a_mostrar(array('nro_acta', 'anio_academico', 'turno_nombre', 'codigo_nombre_actividad', 'tipo_acta_descr', 'ubicacion_nombre', 'fecha_formato_visual', 'mesa_nombre', 'llamado_nombre', 'instancias_nombre'));
			
			$this->ev_info->set_columnas_fila(array('nro_acta', 'anio_academico', 'turno_nombre'), 1);
			$this->ev_info->set_columnas_fila(array('instancias_nombre', 'tipo_acta_descr', 'ubicacion_nombre'), 2);
			$this->ev_info->set_columnas_fila(array('fecha_formato_visual', 'mesa_nombre', 'llamado_nombre'), 3);
			$this->ev_info->set_columnas_fila(array('codigo_nombre_actividad'), 4);
			
			$this->ev_info->set_etiqueta('nro_acta', 'Acta Nro.');
			$this->ev_info->set_etiqueta('anio_academico', 'Año Académico');
			$this->ev_info->set_etiqueta('turno_nombre', 'Turno');
			
			$this->ev_info->set_etiqueta('instancias_nombre', 'Instancia');
			$this->ev_info->set_etiqueta('tipo_acta_descr', 'Tipo');
			$this->ev_info->set_etiqueta('ubicacion_nombre', 'Ubicación');
			
			$this->ev_info->set_etiqueta('fecha_formato_visual', 'Fecha');
			$this->ev_info->set_etiqueta('mesa_nombre', 'Mesa');
			$this->ev_info->set_etiqueta('llamado_nombre', 'Llamado');			
			
			$this->ev_info->set_etiqueta('codigo_nombre_actividad', 'Actividad');
		}
		return $this->ev_info;
	}
	
	function conf__detalle()
	{
		if (isset($this->s__acta_seleccionada)) {
			$where = "sga_actas.id_acta = '$this->s__acta_seleccionada'";
			$datos = toba::consulta_php('co_actas')->get_listado_actas_examen_anuladas($where);
			$this->ev_info()->set_datos($datos[0]);
		}
	}

	//---- filtro_detalles --------------------------------------------------------------

	function conf__filtro_detalles(guarani_ei_filtro $filtro)
	{
		if (isset($this->s__datos_detalles)) {
			$filtro->set_datos($this->s__datos_detalles);
		}
	}

	function evt__filtro_detalles__filtrar($datos)
	{
		if (isset($datos)) {
			$this->s__datos_detalles = $datos;
			$this->s__filtro_detalles = $this->dep('filtro_detalles')->get_sql_where();
		} else {
			unset($this->s__datos_detalles);
			$this->s__filtro_detalles = '';
		}
	}

	function evt__filtro_detalles__cancelar()
	{
		unset($this->s__datos_detalles);
		unset($this->s__filtro_detalles);
	}

	//---- cuadro_detalles --------------------------------------------------------------

	function conf__cuadro_detalles(toba_ei_cuadro $cuadro)
	{
		if (isset($this->s__acta_seleccionada)) {
			$where = " sga_actas_detalle.id_acta = '$this->s__acta_seleccionada'";
			if (!empty($this->s__filtro_detalles)) {
				$where .= ' AND '.$this->s__filtro_detalles;
			}

			$datos = toba::consulta_php('co_actas')->get_detalles_acta($where);
			$cuadro->set_datos($datos);
			
			if (empty($datos)) {
				$indx_msg = '800_eof_cuadro';
				if (!empty($this->s__datos_detalles)) {
					$indx_msg .= '_filtrado';
				}
				$msj = guarani_mensajes::get($indx_msg, array('Alumnos'));
				$cuadro->set_eof_mensaje($msj);
			}
		}
		$cuadro->set_filtro_asociado($this->dep('filtro_detalles'));
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__volver()
	{
		unset($this->s__acta_seleccionada);
		unset($this->s__filtro_detalles);
		unset($this->s__datos_detalles);

		$this->set_pantalla('listado');
	}
	
	function cuadro() 
	{
		return $this->dep('cuadro_detalles');
	}
}
?>