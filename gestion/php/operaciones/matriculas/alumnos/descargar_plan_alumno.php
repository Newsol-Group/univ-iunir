<?php
// Obtengo los parámetros que envió el CI.
    $parametrosFicha = toba::memoria()->get_parametros();  
    $pdf = new guarani_ficha_plan_estudio_alumno($parametrosFicha['alumno'], $parametrosFicha['plan_version'], $parametrosFicha['legajo']);
    // Genero el pdf
    $archivo = $pdf->generar_pdf();
    // Descargo el pdf
    arai_documentos::downloadFile($archivo, arai_documentos::CONTENT_DISPOSITION_ATTACHMENT, 'plan_estudios.pdf', true);