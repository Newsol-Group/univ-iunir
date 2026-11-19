<?php
/**
 *
 *
 * @package     Requisitos
 * @subpackage     Requisitos de Ingreso
 * 
 */
class ci_rep_adeudan_requisitos extends ci_rep_sin_listado_inicial
{
	protected $s__filtro_requisitos = '';

	function get_tabla_principal()
	{
		return 'sga_requisitos_aspirante';
	}

	function evt__filtro__filtrar($datos)
	{
		$this->s__filtro_requisitos = "";
		if (isset($datos)) {
			$this->s__datos = $datos;
			if (isset($datos['requisito'])){
			if($datos['requisito']['condicion'] == 'es_distinto_de') {
				$this->s__filtro_requisitos = " sga_requisitos.requisito <> ". $datos['requisito']['valor'];
			} else {
				$this->s__filtro_requisitos = " sga_requisitos.requisito = ". $datos['requisito']['valor'];
			}
			
			}
			$this->dep('filtro')->columna('requisito')->resetear_estado();
			$this->s__filtro = $this->dep('filtro')->get_sql_where();
			} else {
			unset($this->s__datos);
			$this->s__filtro = "";
			$this->s__filtro_requisitos = "";
		}
	}

	function get_datos($filtro)
	{
		return toba::consulta_php('co_requisitos_ingreso')->get_alumnos_deudores($filtro);
	}

	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		$cuadro->set_filtro_asociado($this->dep('filtro'));
		$cuadro->set_usar_ordenamiento_con_cortes();
		/**
		 * 	Si no está seteado el filtro entonces es la carga inicial del cuadro (el usuario no presionó filtrar).
		 * 	No se traen datos (no se ejecuta la consulta) y se pone un mensaje en el cuadro que indica al usuario
		 * 	que debe filtrar para obtener datos.
		 */
		$this->msj_eof = '800_eof_cuadro';
		if (!isset($this->s__filtro)) {
			$this->indx_msg_eof .= '_sinfiltro';
		} else {
			//El usuario presiono filtrar (ya sea con datos o con el filtro vacío).
			$filtro['otros'] = $this->s__filtro;
			$filtro['requisitos'] = $this->s__filtro_requisitos;
			$datos = $this->get_datos($filtro);
			$cuadro->set_datos($datos);
		}
		$cuadro->set_eof_mensaje($this->get_msg_eof());
	}

}
?>