<?php

$parametros = toba::memoria()->get_parametros();

if ($parametros['origen'] == preinscripcion::origen_autogestion) {
    $req_digitales = toba::consulta_php('co_requisitos')->get_requisitos_digitales_preinscripcion($parametros['id']);
} else {
    //Son los requisitos digitales que envio el aspirante desde preinscripcion.
    //Faltaba saber el tipo_documento_digital de c/u
    $req_digitales = toba::consulta_php('co_preinscripciones')->get_requisitos_digitales_preinscripcion($parametros['id'], $parametros['fuente_de_datos']);
    $requisitos = array_column($req_digitales, 'requisito');
    //Busco para los requisitos de esa persona, los tipo_documento_Digital
    $condicion = guarani_sql::armar_condicion_in($requisitos, 'sga_requisitos', 'requisito');
    
    $req = toba::consulta_php('co_requisitos')->get_listado($condicion);
    $aux = [];
    foreach ($req as $reg) {
        $aux[$reg['requisito']] = $reg['tipo_documento_digital'];
    }
}
$directorio = toba::proyecto()->get_www_temp();
$nombreZip = 'Requisitos.zip';
$archivoZip = $directorio['path'].'/'.$nombreZip;

$zip = new ZipArchive();
$zip->open($archivoZip, ZIPARCHIVE::CREATE);

//Recorro los archivos digitales que tiene cargada la persona.
$archivosAborrar = [];
$archivosAborrar[] = $archivoZip;
foreach($req_digitales as $req) {
    //Sino esta seteado tipo_documento_digital es porque es una preinscripcion y 
    //tengo que setearlo.
    if (isset($reg['tipo_documento_digital'])) {
        $req['tipo_documento_digital'] = $aux[$req['requisito']];
    }
    
    $nombre = $directorio['path'].'/'.md5($req['id_documento']);
    $archivo = guarani_cliente_rdi::get_contenido_documento($req['id_documento'], $req['tipo_documento_digital']);
    file_put_contents($nombre, $archivo['contents']);
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
	$mime_type = finfo_file($finfo, $nombre);
	finfo_close($finfo);
	$ext = guarani_archivos::mime2ext($mime_type);    
	$nombre_descarga = $nombre.'.' . $ext;
    rename($nombre, $nombre_descarga);    
    $zip->addFile($nombre_descarga, basename($nombre_descarga));
    $archivosAborrar[] = $nombre_descarga;    
}
$zip->close();

header('Content-type: application/zip');
header('Content-Description: File Transfer');
header("Content-Disposition: attachment; filename=\"$nombreZip\"");
header('Content-Transfer-Encoding: binary');
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: no-cache');
header('Content-Length: ' . filesize($archivoZip));
ob_clean();
flush();
readfile($archivoZip);
//Borro los archivos que agregue al zip.
foreach ($archivosAborrar as $archivo) {
    unlink($archivo);
}