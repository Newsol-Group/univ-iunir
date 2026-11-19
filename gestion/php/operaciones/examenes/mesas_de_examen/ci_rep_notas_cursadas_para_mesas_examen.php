<?php
/**
 *
 * @package 	Exámenes
 * @subpackage 	Reportes
 * 
 */
class ci_rep_notas_cursadas_para_mesas_examen extends ci_rep_sin_listado_inicial
{
	protected $s__mesa_seleccionada;
	protected $s__llamado_mesa_seleccionado;
	
	protected $ev_info;
	
	protected $s__filtro_detalles;
	protected $s__datos_detalles;
	
	function get_tabla_principal()
	{
		return 'sga_mesas_examen';
	}

	function get_datos($filtro)
	{
		return toba::consulta_php('co_mesas_de_examen')->get_listado($filtro);
	}
	
	//-----------------------------------------------------------------------------------
	//---- filtro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function conf__filtro(toba_ei_filtro $filtro)
	{
		parent::conf__filtro($filtro);
		$filtro->columna('instancia')->set_condicion_fija('es_igual_a');
		$filtro->columna('propuesta')->set_condicion_fija('es_igual_a');
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
												AND sga_mesas_examen_instancias.instancia = '{$datos['instancia']['valor']}')";
				$this->dep('filtro')->columna('instancia')->resetear_estado();
			}
			
			if (isset($datos['propuesta'])) {
				$filtro .= " AND EXISTS (SELECT * FROM sga_mesas_examen_propuestas WHERE sga_mesas_examen_propuestas.mesa_examen = sga_mesas_examen.mesa_examen AND sga_mesas_examen_propuestas.propuesta = '{$datos['propuesta']['valor']}')";
				$this->dep('filtro')->columna('propuesta')->resetear_estado();
			}
			
			$this->s__filtro = "{$this->dep('filtro')->get_sql_where()} $filtro";
		} else {
			unset($this->s__datos);
			$this->s__filtro = '';
		}
	}
	
	//-----------------------------------------------------------------------------------
	//---- cuadro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__cuadro__seleccion($seleccion)
	{
		$this->s__mesa_seleccionada = $seleccion['mesa_examen'];
		$this->s__llamado_mesa_seleccionado = $seleccion['llamado_mesa'];
		$this->set_pantalla('detalle');
	}
	
	//-----------------------------------------------------------------------------------
	//---- ev_info ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_controlador($this);
			$this->ev_info->set_columnas_a_mostrar(array('anio_academico', 'codigo_nombre_actividad', 'nombre_turno_examen', 'nombre', 'nombre_llamado', 'fecha_formato_visual', 'nombre_ubicacion', 'instancias_nombre'));
			
			$this->ev_info->set_columnas_fila(array('anio_academico', 'codigo_nombre_actividad'), 1);
			$this->ev_info->set_columnas_fila(array('nombre_turno_examen', 'nombre', 'nombre_llamado'), 2);
			$this->ev_info->set_columnas_fila(array('fecha_formato_visual', 'nombre_ubicacion', 'instancias_nombre'), 3);
			
			$this->ev_info->set_etiqueta('anio_academico', 'Año Académico');
			$this->ev_info->set_etiqueta('codigo_nombre_actividad', 'Actividad');
			
			$this->ev_info->set_etiqueta('nombre_turno_examen', 'Turno');
			$this->ev_info->set_etiqueta('nombre', 'Mesa');
			$this->ev_info->set_etiqueta('nombre_llamado', 'Llamado');
			
			$this->ev_info->set_etiqueta('fecha_formato_visual', 'Fecha');
			$this->ev_info->set_etiqueta('nombre_ubicacion', 'Ubicación');
			$this->ev_info->set_etiqueta('instancias_nombre', 'Instancia');
			
		}
		return $this->ev_info;
	}
	
	function conf__detalle()
	{
		if (isset($this->s__mesa_seleccionada)) {
			$where = "sga_mesas_examen.mesa_examen = '$this->s__mesa_seleccionada'
					  AND sga_llamados_mesa.llamado_mesa = '$this->s__llamado_mesa_seleccionado'";
			$datos = $this->get_datos($where);
			$this->ev_info()->set_datos($datos[0]);
		}
	}
	

	//-----------------------------------------------------------------------------------
	//---- filtro_detalles --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

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
		$this->s__filtro_detalles = '';
	}
	
	function get_instancias_mesa()
	{
		$mesa = toba::db()->quote($this->s__mesa_seleccionada);
		$filtro = "sga_mesas_examen.mesa_examen = $mesa";
		$datos_instancias = toba::consulta_php('co_mesas_de_examen')->get_instancias_mesas_examen($filtro);
		
		$filtro = guarani_sql::armar_condicion_in($datos_instancias, 'sga_instancias', 'instancia', 'instancia');		
		return toba::consulta_php('co_mesas_de_examen')->get_instancias_aplicables_a_mesa($filtro);
	}
	
	
	//-----------------------------------------------------------------------------------
	//---- cuadro_detalles --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function get_msg_eof_detalles()
	{
		$indx_msg = '800_eof_cuadro';
		if ($this->s__filtro_detalles != '') {
			$indx_msg = $indx_msg . '_filtrado';
		}
		$etiquetas = guarani_etiquetas::get_tabla('sga_insc_examen');
		return guarani_mensajes::get($indx_msg, array($etiquetas->get_enf('plural')));
	}
	
	function conf__cuadro_detalles(guarani_ei_cuadro $cuadro)
	{
		$cuadro->set_filtro_asociado($this->dep('filtro_detalles'));		
		if (isset($this->s__mesa_seleccionada)) {
			$datos = toba::consulta_php('co_inscripciones_examenes')->get_resultados_cursada_inscriptos_mesa($this->s__mesa_seleccionada, $this->s__llamado_mesa_seleccionado, $this->s__filtro_detalles);
			$cuadro->set_datos($datos);
		}
		$cuadro->set_eof_mensaje($this->get_msg_eof_detalles());
	}
	
	
	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__volver()
	{
		unset($this->s__mesa_seleccionada);
		unset($this->s__llamado_mesa_seleccionado);
		unset($this->s__filtro_detalles);
		unset($this->s__datos_detalles);
		
		$this->set_pantalla('listado');
	}

}
?>