<?php
class pant_operaciones extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->dep('form_datos_acciones')->generar_html();
		echo '<hr />';
		$this->dep('cuadro_op_asociadas')->generar_html();
		echo '<hr />';
		$this->dep('filtro_operaciones')->generar_html();
		echo '<hr />';
		$this->dep('cuadro_op_disp')->generar_botones();		
		$this->dep('cuadro_op_disp')->evento('agregar')->set_en_botonera(false);
		$this->dep('cuadro_op_disp')->generar_html();
	}

}

?>