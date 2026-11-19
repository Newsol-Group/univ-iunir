<?php
class ci_busqueda_persona extends guarani_pers_ci
{
	protected $s__filtro;
	protected $s__datos;
	protected $indx_msg_eof = '';

	function conf()
	{
		$this->indx_msg_eof = '800_eof_cuadro';
		$this->dep('filtro')->set_sin_filtrado_inicial();		
	}
	
	function get_datos($filtro='')
	{
		return toba::consulta_php('co_personas')->get_listado($filtro);
	}
	
	//-----------------------------------------------------------------------------------
	//---- filtro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function conf__filtro(guarani_ei_filtro $filtro)
	{
		$this->filtro_configurar('filtro', false);
	}

	function evt__filtro__filtrar($datos)
	{
		$this->filtro_filtrar('filtro', $datos);
	}

	function evt__filtro__cancelar()
	{
		$this->filtro_limpiar('filtro');
	}
	
	//-----------------------------------------------------------------------------------
	//---- cuadro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		/**
 		 *	Si no está seteado el filtro entonces es la carga inicial del cuadro (el usuario no presionó filtrar).
		 *	No se traen datos (no se ejecuta la consulta) y se pone un mensaje en el cuadro que indica al usuario
		 *	que debe filtrar para obtener datos.
		*/
		$cuadro->desactivar_modo_clave_segura();
		$filtro = $this->get_filtro('filtro');
		if (!isset($filtro)) {
			$this->indx_msg_eof .= '_sinfiltro';
		} else {
			//El usuario presiono filtrar (ya sea con datos o con el filtro vacío).
			$datos = $this->get_datos($filtro);
			$cuadro->set_datos($datos);
			if (empty($datos)) {
				$this->indx_msg_eof .= '_filtrado';
			}
		}
		$etiquetas = guarani_etiquetas::get_tabla('mdp_personas');
		$etiqueta = $etiquetas->get_enf('plural');		
		$mje = guarani_mensajes::get($this->indx_msg_eof, array($etiqueta));
		$cuadro->set_eof_mensaje($mje);
	}
}
?>