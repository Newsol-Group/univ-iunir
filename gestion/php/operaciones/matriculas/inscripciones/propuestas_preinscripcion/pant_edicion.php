<?php
class pant_edicion extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
        $this->controlador()->ev_info()->generar_html();
        echo "<br>";
        $this->controlador()->generar_boton('imprimir');
        if ($this->existe_evento('descargar_requisitos')) {
            $this->controlador()->generar_boton('descargar_requisitos');
        }
        echo "<br><br>";
        $this->dep('form_ml_inscripciones')->generar_html();
	}

}
?>