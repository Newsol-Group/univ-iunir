<?php
    //Obtengo los parametros que envio el ci.
    $parametrosFicha = toba::memoria()->get_parametros();    
    $pdf = new guarani_ficha_preinscripcion($parametrosFicha['id'], $parametrosFicha['fuente_de_datos'], $parametrosFicha['origen']);
    $pdf->get_comprobante();