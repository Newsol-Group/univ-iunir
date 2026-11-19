<?php
class ci_seleccion_comision extends guarani_pers_ci
{
	protected $s__filtro;
	protected $s__datos;
	protected $indx_msg_eof = '800_eof_cuadro';

	function get_datos($filtro='')
	{
		return toba::consulta_php('co_comisiones')->get_listado($filtro);
	}
	
	//---- filtro -----------------------------------------------------------------------

	function conf__filtro_comisiones(toba_ei_filtro $filtro)
	{
		// Si el perfil de datos del usuario pertenece al dominio "Propuestas" es obligatorio filtrar los conceptos por ubicación.
		if (usuario::tiene_perfil_por_propuesta()) {
			$filtro->set_ubicacion_obligatoria();
		}
		if (isset($this->s__datos)) {
			$filtro->set_datos($this->s__datos);
		}
	}

	function evt__filtro_comisiones__filtrar($datos)
	{
		if (isset($datos)) {
			$this->s__datos = $datos;
			$this->s__filtro = $this->dep('filtro_comisiones')->get_sql_where();
		} else {
			unset($this->s__datos);
			$this->s__filtro = '';
		}
	}

	function evt__filtro_comisiones__cancelar()
	{
		unset($this->s__datos);
		unset($this->s__filtro);
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_comisiones(toba_ei_cuadro $cuadro)
	{
		$cuadro->desactivar_modo_clave_segura();
		/**
 		 *	Si no está seteado el filtro no se traen datos y se setea un mensaje en el cuadro que indica al usuario
		 *	que debe filtrar para obtener datos.
		*/
		if (!isset($this->s__filtro)) {
			$this->indx_msg_eof .= '_sinfiltro';
		} else {
			//El usuario presiono filtrar (ya sea con datos o con el filtro vacío).
			$datos = $this->get_datos($this->s__filtro);
			$cuadro->set_datos($datos);
		}
		$etiqueta = guarani_etiquetas::get_tabla('sga_comisiones');
		$etiqueta = $etiqueta->get_enf('plural');
		$cuadro->set_eof_mensaje(guarani_mensajes::get($this->indx_msg_eof, array($etiqueta)));
	}
	
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		parent::extender_objeto_js();
		echo "
		{$this->objeto_js}.evt__cancelar = function()
		{
			window.close();
		}
		";
	}
}
?>