<?php
/**
 *
 *
 * @package Operaciones Generales
 * @subpackage Busqueda Localidad
 */
class ci_seleccion_localidad extends guarani_pers_ci
{
	protected $s__filtro;
	protected $s__datos;
	protected $s__incluir_cod_postal = false;

	function set_inclusion_cod_postal($incluir)
	{
		$this->s__incluir_cod_postal = $incluir;
	}

	function get_datos($filtro)
	{
		return toba::consulta_php('co_mug')->get_listado_localidades($this->s__incluir_cod_postal, $filtro);
	}

	protected function get_msg_eof()
	{
		if (!empty($this->s__filtro)) {
			$indx_msg = '800_eof_cuadro_filtrado';
		} else {
			$indx_msg = '800_eof_cuadro_sinfiltro';
		}
		$etiqueta = 'Localidades';
		return guarani_mensajes::get($indx_msg, array($etiqueta));
	}
	
	//---- cuadro de Localidades ---------------------------------------------------------

	function conf__cuadro_localidades(toba_ei_cuadro $cuadro)
	{
		$cuadro->desactivar_modo_clave_segura();
		if (isset($this->s__filtro)) {
			$datos = $this->get_datos($this->s__filtro);
			$cuadro->set_datos($datos);
		}
		$cuadro->set_eof_mensaje($this->get_msg_eof());
	}

	//---- formulario de localidades ----------------------------------------------------

	function conf__form_localidades(toba_ei_formulario $form)
	{
		if (isset($this->s__datos)) {
			$form->set_datos($this->s__datos);
		}
	}

	function evt__form_localidades__filtrar($datos)
	{
		if (isset($datos)) {
			$this->s__datos = $datos;
			$filtro = "";
			if (isset($datos['pais'])) {
				$filtro .= " mug_paises.pais = {$datos['pais']}";
			}
			if (isset($datos['provincia'])) {
				$filtro .= " AND mug_provincias.provincia = {$datos['provincia']}";
			}
			if (isset($datos['localidad'])) {
				$filtro_condicion = new toba_filtro_condicion('contiene', 'ILIKE', '%', '%', '', '');
				$filtro .= ' AND '.$filtro_condicion->get_sql('f_limpiar_acentos(mug_localidades.nombre::varchar)', guarani_texto::limpiar_acentos($datos['localidad']));
			}
			$this->s__filtro = $filtro;
		} else {
			unset($this->s__datos);
			$this->s__filtro = "";
		}
	}

	function evt__form_localidades__cancelar()
	{
		unset($this->s__datos);
		unset($this->s__filtro);
	}

}
?>