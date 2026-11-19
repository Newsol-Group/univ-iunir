<?php
class form_historial extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		echo $this->get_html_alineado(array('nombre_requisito'),2);
	}
}

?>