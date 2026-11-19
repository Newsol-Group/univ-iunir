<?php
class guarani_tp_popup extends toba_tp_popup
{
	protected function comienzo_cuerpo_basico()
	{
		$tp_basico = new guarani_tp_basico();
		$tp_basico->comienzo_cuerpo_basico();
	}
}
?>