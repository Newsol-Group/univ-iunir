<?php
class cuadro_historial extends guarani_pers_ei_cuadro
{
	function extender_objeto_js()
	{
		echo "
		{$this->objeto_js}.evt__cerrar = function()
		{
			window.close();
		}";

	}
}
