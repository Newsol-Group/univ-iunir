<?php
class ci_rep_observaciones extends guarani_pers_ci
{
    protected $ev_info;
    protected $s__datos;
    
    function conf()
	{
		toba::memoria()->desactivar_reciclado();
		$parametros = toba::memoria()->get_parametros();
        $filtro = "sga_bitacora.bitacora = {$parametros['bitacora']}";
		$this->s__datos = current(toba::consulta_php('co_personas')->get_comentarios_bitacora($filtro));
        $this->ev_info()->set_datos($this->s__datos);        
	}
    
	function ev_info()
	{
		if (! isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
            $this->ev_info->set_controlador($this);
            $this->ev_info->set_td_etiqueta_width('100px');
			$this->ev_info->set_columnas_a_mostrar(array('fecha_formato_visual', 'titulo', 'seguimiento_nombre', 'docente_nombre', 'persona_nombre', 'observaciones'));
			$this->ev_info->set_etiqueta('fecha_formato_visual', 'Fecha');
			$this->ev_info->set_etiqueta('titulo', 'Título');
			$this->ev_info->set_etiqueta('seguimiento_nombre', 'Seguimiento');
			$this->ev_info->set_etiqueta('docente_nombre', 'Referente');
			$this->ev_info->set_etiqueta('persona_nombre', 'Cargado Por');
			$this->ev_info->set_etiqueta('observaciones', 'Observaciones');
		}
		return $this->ev_info;
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