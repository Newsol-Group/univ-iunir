<?php
class cuadro_salida_ev_info extends guarani_pers_ei_cuadro
{
	protected $_ev_info_asociado;

	function set_ev_info_asociado($ev_info)
	{
		$this->_ev_info_asociado = $ev_info;
	}

	function hay_ev_info_asociado()
	{
		return $this->_ev_info_asociado ? true : false;
	}

	function generar_inicio()
	{
		if ($this->hay_ev_info_asociado() && $this->_tipo_salida == 'excel') {
			$metodo_vista = 'vista_'.$this->_tipo_salida;
			$salida = $this->_salida->get_resultado_generacion();
			$this->_ev_info_asociado->$metodo_vista($salida);
		}
		parent::generar_inicio();
	}
}
?>