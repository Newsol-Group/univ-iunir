<?php
class zona_planes extends guarani_zona_pers
{
	protected $ev_info;

	function cargar($id)
	{
		toba::memoria()->eliminar_dato('zona_plan');
		parent::cargar($id);
		toba::memoria()->set_dato('zona_plan',$id);
	}

	function resetear()
	{
		parent::resetear();
		toba::memoria()->eliminar_dato('zona_plan');
	}
	
	function cargar_info()
	{
		if( toba::memoria()->existe_dato('zona_plan') && 
			toba::memoria()->get_dato('zona_plan') != $this->editable_id ) {
			throw new guarani_error('Error interno');		
		}
		$datos = toba::consulta_php('co_planes')->get_datos_plan_version($this->editable_id);
		$this->editable_info = $datos[0];
		$this->editable_info['propuesta_nombre'] = guarani_texto::acortar_texto($this->editable_info['propuesta_nombre'], 39);
		$this->editable_info['plan_nombre'] = guarani_texto::acortar_texto($this->editable_info['plan_nombre'], 25);
		$datos = array(
			'Propuesta'=>'('.$this->editable_info['propuesta_codigo'].')&nbsp;'.$this->editable_info['propuesta_nombre'],
			'Plan'=>'('.$this->editable_info['plan_codigo'].") ".$this->editable_info['plan_nombre'].'&nbsp;&nbsp;(&nbsp;<i>'.$this->editable_info['plan_estado_descripcion'].'</i>&nbsp;)',
			'Versi&oacute;n'=>$this->editable_info['version'].'&nbsp;&nbsp;'.'(&nbsp;<i>'.$this->editable_info['version_estado_descripcion'].'</i>&nbsp)'
		);
		$this->ev_info = guarani::ev_info_datos($datos);//new ev_info($this->editable_id,$this->editable_info);
		$this->ev_info->set_columnas_fila(array('Propuesta'),1);
		$this->ev_info->set_columnas_fila(array('Plan', 'Versi&oacute;n'),2);
	}

	function generar_info_html() {
		if($this->ev_info) {
			$this->ev_info->generar_html();
			echo "<hr/>";
		}
	}
	
}
?>