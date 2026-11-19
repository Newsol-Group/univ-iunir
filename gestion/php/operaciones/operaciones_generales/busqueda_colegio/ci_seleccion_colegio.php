<?php
class ci_seleccion_colegio extends guarani_pers_ci
{
	protected $msj_eof = '800_eof_cuadro';
	
	function get_datos($filtro)
	{
		return toba::consulta_php('co_instituciones')->get_listado_colegios($filtro);
	} 	
	
	//---- filtro_colegios -------------------------------------------------------------------

	function conf__filtro_colegios(guarani_ei_filtro $filtro)
	{
		$etiquetas = guarani_etiquetas::get_tabla('sga_colegios_secundarios');
		$filtro->set_etiquetas($etiquetas);
		$filtro->set_sin_filtrado_inicial();
		$filtro->columna('pais')->get_ef()->set_estado_defecto(54);
		$this->filtro_configurar('filtro_colegios', false);
	}

	function evt__filtro_colegios__filtrar($datos)
	{
		$this->filtro_filtrar('filtro_colegios', $datos);
	}

	function evt__filtro_colegios__cancelar()
	{
		$this->filtro_limpiar('filtro_colegios');
	}

	//---- cuadro_alumnos -------------------------------------------------------------------

	function conf__cuadro_colegios(toba_ei_cuadro $cuadro)
	{
		$cuadro->desactivar_modo_clave_segura();

		$filtro = $this->get_filtro('filtro_colegios');
		if (!is_null($filtro)) {
			$datos = $this->get_datos($filtro);
			$cuadro->set_datos($datos);	
			$etiquetas = guarani_etiquetas::get_tabla('sga_colegios_secundarios');
			$etiqueta = $etiquetas->get_enf('plural');
			$indice = $this->msj_eof . '_filtrado';
			$mje = guarani_mensajes::get($indice, array($etiqueta));
		} else {
			$indice = $this->msj_eof . '_sinfiltro';
			$mje = guarani_mensajes::get($indice);
		}

		$cuadro->set_eof_mensaje($mje);
	}
}
?>