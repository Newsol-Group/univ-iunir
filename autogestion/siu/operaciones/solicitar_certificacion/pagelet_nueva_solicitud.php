<?php

namespace siu\operaciones\solicitar_certificacion;

use SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;

class pagelet_nueva_solicitud extends pagelet
{
	function get_nombre()
    {
        return 'nueva_solicitud';
    }

	/**
	 * Shortcut al modelo
	 * 
	 * @return \siu\modelo\transacciones\solicitar_certificacion
	 */
	protected function modelo()
	{
		return $this->controlador->modelo();
	}
	
    function prepare()
    {
		$operacion = kernel::ruteador()->get_id_operacion();
		
        $this->data['url_datos_contacto'] = kernel::vinculador()->crear('datos_censales', 'datos_contacto');
		$this->data['url_form'] = kernel::vinculador()->crear($operacion, 'nueva_solicitud');
		
		$this->data['persona']['apellido'] = kernel::persona()->get_solo_apellido();
		$this->data['persona']['nombres'] = kernel::persona()->get_solo_nombres();
		$this->data['persona']['fecha_nacimiento'] = kernel::persona()->get_fecha_nacimiento();
		$this->data['persona']['tipo_documento_desc_abreviada'] = kernel::persona()->get_tipo_documento_desc_abreviada();
		$this->data['persona']['nro_documento'] = kernel::persona()->get_nro_documento();
		$cuil = "";
		$cuil_persona = $this->modelo()->info__cuil_persona();
		if($cuil_persona) $cuil = \persona::formatearCUIT($cuil_persona['nro_documento']);
		$this->data['persona']['cuil'] = $cuil;
		
		$this->data['certificaciones_no_tramitadas'] = $this->modelo()->info__certificaciones_no_tramitadas();
		
		$this->data['colaciones'] = $this->modelo()->info__colaciones();
		
		$this->data['juramentos'] = $this->modelo()->info__juramentos();
		
		$this->data['datos_contactos'] = $this->modelo()->info__datos_contactos();
    }
	
	function armar_combo($datos, $valor, $descripcion, $valor_seleccionado = null, $opcional = false){
		
		$options = "";
		$combo_seleccione = kernel::traductor()->trans('solicitar_certificacion.combo_seleccione');
		if(is_null($valor_seleccionado) || $opcional) $options .= "<option value=''>$combo_seleccione</option>";
		
		foreach ($datos as $dato){
			$selected = "";
			if($dato[$valor] == $valor_seleccionado) $selected = "selected='selected'";
			$options .= "<option value='{$dato[$valor]}' $selected>{$dato[$descripcion]}</option>";
		}
		
		return $options;
	}
	
}
