<?php
namespace siu\operaciones\creditos;

use SIU\Chulupi\kernel;
use SIU\Chulupi\util\validador;
use siu\modelo\datos\catalogo;
use siu\operaciones\_comun\operaciones\reporte\controlador as controlador_reporte;

class controlador extends controlador_reporte
{
	function accion__detalle(){
		$anio = $this->validate_param('anio', 'get', validador::TIPO_INT);
		$per_lec = $_GET['per_lec'];
				
		$parametros = array(
			'nro_inscripcion' =>  kernel::persona()->get_nro_inscripcion(),
			'anio' =>  $anio,
			'periodo_lectivo' => $per_lec
		);
		
		$datos = catalogo::consultar('alumno', 'creditos', $parametros);		
		$clase = 'operaciones\creditos\pagelet_detalle';
		$pl = kernel::localizador()->instanciar($clase, 'items');		
        $this->vista()->add_pagelet($pl);
		
		/* url botón Volver */
		$operacion = kernel::ruteador()->get_id_operacion();
		$pl->data['url_back'] = kernel::vinculador()->crear($operacion);
		
		$pl->data['datos'] = $datos;
	}
}
?>