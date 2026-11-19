<?php

class pant_inicial extends guarani_pers_ei_pantalla {

    function generar_layout() {
        $this->controlador()->ev_info_resumen()->generar_html();
    }

}

?>