<?php
/**
 *
 * 
 * @package 	Calendario
 * @subpackage 	-
 */
class ci_rep_periodos_inscripcion_por_propuesta extends ci_rep_sin_listado_inicial
{
	protected $s__filtro_propuesta;

	function get_tabla_principal()
	{
		return 'sga_periodos_inscripcion';
	}

	function conf__filtro(toba_ei_filtro $filtro)
	{
		parent::conf__filtro($filtro);
		$filtro->columna('propuesta')->set_condicion_fija('es_igual_a');
	}

	function evt__filtro__filtrar($datos)
	{
		$this->s__filtro = '';
		$this->s__filtro_propuesta = null;
		if (isset($datos)) {
			$this->s__datos = $datos;
			if (isset($datos['propuesta'])) {
				$this->s__filtro_propuesta = $datos['propuesta']['valor'];
				$this->dep('filtro')->columna('propuesta')->resetear_estado(); 
			}
			$this->s__filtro = $this->dep('filtro')->get_sql_where();
		} else {
			unset($this->s__datos_filtro);
		}
	}

	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		$cuadro->set_filtro_asociado($this->dep('filtro'));
		$cuadro->set_usar_ordenamiento_con_cortes();
		$this->msj_eof = '800_eof_cuadro';
		if (!isset($this->s__filtro)) {
			$this->indx_msg_eof .= '_sinfiltro';
		} else {
			$datos = toba::consulta_php('co_periodos_de_inscripcion')->get_periodos_inscripcion_propuesta($this->s__filtro, $this->s__filtro_propuesta);
			$cuadro->set_datos($datos);
		}
		$cuadro->set_eof_mensaje($this->get_msg_eof());
	}

	function get_datos($filtro){}

	function evt__aplanar()
	{
		$perfil_datos = toba::manejador_sesiones()->get_perfil_datos_activo();
		if (empty($perfil_datos)) {
			$aplanador = toba::cn('proceso_aplanar_periodo_inscripcion');
			$aplanador->procesar();
		} else {
			throw new toba_error(guarani_mensajes::get('800_aplanado_perfil_datos'));
		}
	}
}
?>