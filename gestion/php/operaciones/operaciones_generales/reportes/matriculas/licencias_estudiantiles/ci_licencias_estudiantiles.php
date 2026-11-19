<?php
class ci_licencias_estudiantiles extends guarani_pers_ci
{
	protected $s__licencia;	
	protected $ev_info;
    protected $s__datos;
	
	function conf()
	{
		toba::memoria()->desactivar_reciclado();
		$parametros = toba::memoria()->get_parametros();
        $filtro = "sga_licencias.licencia = {$parametros['licencia']}";
		$this->s__datos = current(toba::consulta_php('co_licencias')->get_listado($filtro));		
		$vinculo = toba::vinculador()->crear_vinculo('guarani', '41000114', array('licencia_doc' => $this->s__datos['licencia_doc']));
		$this->s__datos['nombre_archivo'] = "<a href='$vinculo' target='_blank'>".$this->s__datos['nombre_archivo']."</a>";
        $this->ev_info()->set_datos($this->s__datos);
	}
	
	function ev_info()
	{
		if (! isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
            $this->ev_info->set_controlador($this);
            $this->ev_info->set_td_etiqueta_width('150px');
			$this->ev_info->set_columnas_a_mostrar(array('licencia', 'puede_rendir_examen_desc', 'es_licencia_financiera_desc', 'propuestas', 'tipo_licencia', 'desde', 'hasta',  'motivo_nombre', 'estado', 'observaciones', 'resolucion', 'nombre_archivo'));
			$this->ev_info->set_etiqueta('licencia', 'N° Trámite');
			$this->ev_info->set_etiqueta('puede_rendir_examen_desc', 'Permite rendir examen');
			$this->ev_info->set_etiqueta('es_licencia_financiera_desc', 'Licencia Financiera');
			$this->ev_info->set_etiqueta('propuestas', 'Propuestas');
			$this->ev_info->set_etiqueta('tipo_licencia', 'Tipo de Licencia');
			$this->ev_info->set_etiqueta('desde', 'Desde');
			$this->ev_info->set_etiqueta('hasta', 'Hasta');			
			$this->ev_info->set_etiqueta('motivo_nombre', 'Motivo');
			$this->ev_info->set_etiqueta('estado', 'Estado');
			$this->ev_info->set_etiqueta('resolucion', 'N° Resolución');
			$this->ev_info->set_etiqueta('observaciones', 'Observaciones');
			$this->ev_info->set_etiqueta('nombre_archivo', 'Documento Adjunto');
			
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