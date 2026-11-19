<?php
class pant_detalle_evaluaciones extends guarani_ei_pantalla
{
    function generar_layout()
    {
        $this->controlador()->ev_info()->generar_html();
        parent::generar_layout();
    }

}
?>