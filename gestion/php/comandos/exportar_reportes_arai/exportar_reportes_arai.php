<?php

try {
    
    // Valido la conexión con Araí Reportes
    validarConexion();
    
    // Elimina todo
    eliminarTodo();

    // Creo las imágenes
    crearImagenes();

    // Creo los reportes
    crearReportes();

    echo comunes::consola_tipo_msg_exito."Se exportaron todas las imagenes y reportes a SIU-Arai con exito".PHP_EOL;
    exit(0);

} catch (Throwable $e) {

	$mensaje = $e->getMessage();
	// Elimino saltos de líneas para que el error quede solo en una línea
	$mensaje = comunes::reemplazar_saltos_lineas_por_espacios($mensaje);
	echo comunes::consola_tipo_msg_error."Ocurrió el siguiente error: {$mensaje}".PHP_EOL;
	exit(1);

}

function validarConexion()
{
    // Valido la conexión con Araí Reportes
    if (!arai_reportes::isConexionOk()) {
        echo comunes::consola_tipo_msg_error."No pudo establecerse conexión con Araí-Reportes, revise el archivo 'instalacion/i__desarrollo/p__guarani/rest/arai_reportes/cliente.ini'.".PHP_EOL;
        exit(1);
    }
}

function eliminarTodo()
{
    $proyecto = arai_reportes::PROYECTO;
    $uri = "/reportes/$proyecto";
    echo comunes::consola_tipo_msg_info."Eliminando la carpeta '{$uri}'.".PHP_EOL;
    arai_reportes::eliminarCarpeta($uri);
    echo comunes::consola_tipo_msg_exito."Carpeta '{$uri}' eliminada.".PHP_EOL;
}

function crearImagenes()
{
    $imagenesParaCrear = arai_reportes::getImagenesParaCrear();

    // Recorro las imágenes a crear
    foreach ($imagenesParaCrear as $id => $path) {
        echo comunes::consola_tipo_msg_info."Creando la imagen '{$id}'.".PHP_EOL;
        $uriImagen = arai_reportes::agregarImagen($id, $path);
        echo comunes::consola_tipo_msg_exito."URI de imagen '{$id}': {$uriImagen}.".PHP_EOL;
    }
}

function crearReportes()
{
    list($reportesCore, $reportesPers) = arai_reportes::getReportesParaCrear();

    // Recorro los reportes a crear del core
    foreach ($reportesCore as $id => $path) {
        echo comunes::consola_tipo_msg_info."Creando el reporte '{$id}'.".PHP_EOL;
        $uriReporte = arai_reportes::agregarReporte($id, $path, false);
        echo comunes::consola_tipo_msg_exito."URI del reporte '{$id}': {$uriReporte}.".PHP_EOL;
    }

    // Recorro los reportes a crear de la personalización
    foreach ($reportesPers as $id => $path) {
        echo comunes::consola_tipo_msg_info."Creando el reporte '{$id}'.".PHP_EOL;
        $uriReporte = arai_reportes::agregarReporte($id, $path, true);
        echo comunes::consola_tipo_msg_exito."URI del reporte '{$id}': {$uriReporte}.".PHP_EOL;
    }
}