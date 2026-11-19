<?php
class cuadro_seleccion_multiple_sin_todos extends cuadro_seleccion_multiple
{
	function ini() {
		parent::ini();
		$this->set_sin_evt_todos_ninguno();
	}
}
?>
