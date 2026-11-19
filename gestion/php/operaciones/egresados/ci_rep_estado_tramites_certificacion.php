<?php
/**
 *
 * 
 * @package		Egresados
 * @subpackage 	Reportes
 * 
 */
class ci_rep_estado_tramites_certificacion extends ci_reporte 
{
	protected $s__tramite_seleccionado;

	//-----------------------------------------------------------------------------------
	//---- Funciones Auxiliares ---------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function get_tabla_principal()
	{
		return 'sga_certificados_otorg';
	}

	function get_datos($filtro)
	{
		return toba::consulta_php('co_tramites_certificacion')->get_listado($filtro);
	}
	
	function get_tramite_seleccionado()
	{
		return $this->s__tramite_seleccionado;
	}

	//-----------------------------------------------------------------------------------
	//---- filtro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__filtro__filtrar($datos)
	{
		if (isset($datos) && !empty($datos)) {
			$this->s__datos = $datos;
			$filtro_sin_estado = '';
			if (isset($datos['estado']) && ($datos['estado']['valor'] == -1)) {
				// Condición para el caso de que se deseen traer trámites sin estado asignado.
				$filtro_sin_estado .= $datos['estado']['condicion'] == 'es_igual_a' ? ' AND sga_certificados_otorg.estado IS NULL' : " AND sga_certificados_otorg.estado IS NOT NULL";
				$this->dep('filtro')->columna('estado')->resetear_estado();
			}
			$this->s__filtro = $this->dep('filtro')->get_sql_where() . $filtro_sin_estado;
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
		$this->s__tramite_seleccionado = $seleccion['nro_solicitud'];
		$this->set_pantalla('cambios_estado');
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__cancelar()
	{
		unset($this->s__tramite_seleccionado);
		$this->set_pantalla('tramites');
	}
}
?>