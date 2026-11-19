<?php 
/**
 *
 *
 * @package Matriculas
 * @subpackage Alumnos
 */
class ci_rep_residencia_visa_vencida extends ci_reporte
{
	protected $msg_eof_cuadro = '800_eof_cuadro_sinfiltro';
	
	function get_tabla_principal()
	{
		return 'mdp_personas';
	}
	
	function get_datos($filtro)
	{
		return toba::consulta_php('co_alumnos')->get_alumnos_residencia_visa_vencida($filtro);
	}
	
	//---- cuadro -----------------------------------------------------------------------

	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		if (isset($this->s__datos)) {
			$tipo = $this->dep('filtro')->columna('tipo')->get_estado();
			if ($tipo['valor'] == persona::visa) {
				$columnas = array('tipo_residencia_nombre', 'otorgamiento_residencia', 'vencimiento_residencia', 'residencia_cupo');
			} else {
				$columnas = array('tipo_visa_nombre', 'otorgamiento_visa', 'vencimiento_visa');
			}
			$datos = $this->get_datos($this->s__filtro);
			$cuadro->eliminar_columnas($columnas);
			$cuadro->set_datos($datos);
			$cuadro->set_eof_mensaje($this->get_msg_eof());
		} else {
			$cuadro->set_eof_mensaje(guarani_mensajes::get($this->msg_eof_cuadro));
		}
		$cuadro->set_filtro_asociado($this->dep('filtro'));
	}
	
	//---- filtro -----------------------------------------------------------------------
	
	function conf__filtro(toba_ei_filtro $filtro)
	{
		parent::conf__filtro($filtro);
		$this->dep('filtro')->columna('fecha_vencimiento')->set_condicion_fija('desde', true);
		$this->dep('filtro')->columna('tipo')->set_condicion_fija('es_igual_a', true);		
	}
	
	function evt__filtro__filtrar($datos)
	{
		if (isset($datos)) {
			$this->s__datos = $datos;
			
			if ($datos['tipo']['valor'] == persona::visa) {
				$filtro = "mdp_personas_extranjeros.vencimiento_visa < '{$datos['fecha_vencimiento']['valor']}' ";
			} else {
				$filtro = "mdp_personas_extranjeros.vencimiento_residencia < '{$datos['fecha_vencimiento']['valor']}' ";
			}
			
			$campo_tipo	= ($datos['tipo']['valor'] == persona::visa) ? 'tipo_visa' : 'tipo_residencia';
			
			$filtro .= "AND mdp_personas_extranjeros.$campo_tipo IS NOT NULL";
			
			$this->s__filtro = $filtro;
		} else {
			unset($this->s__datos);
			$this->s__filtro = '';
		}
	}
	
}
?>