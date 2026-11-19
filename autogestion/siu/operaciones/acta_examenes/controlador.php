<?php
namespace siu\operaciones\acta_examenes;

use SIU\Chulupi\kernel;
use SIU\Chulupi\util\validador;
use siu\modelo\datos\catalogo;
use siu\operaciones\_comun\operaciones\reporte\controlador as controlador_reporte;

class controlador extends controlador_reporte
{
    protected $acta;
    protected $tipo_acta;
    
    function accion__info_acta()
    {
        $this->acta = $this->validate_param('acta', 'get', validador::TIPO_ALPHANUM);
        $this->tipo_acta = $this->validate_param('tipo_acta', 'get', validador::TIPO_ALPHANUM);
        
        
        $clase = 'operaciones\acta_examenes\pagelet_acta';
		$pl = kernel::localizador()->instanciar($clase, 'items');
        $this->vista()->add_pagelet($pl);
        
		/* url botón Volver */
		$operacion = kernel::ruteador()->get_id_operacion();
		$pl->data['url_back'] = kernel::vinculador()->crear($operacion);
		
        $pl->data['alumnos'] = $this->get_info_acta();
    }
    
    function get_info_acta(){
        $parametros = array('tipo_acta' => $this->tipo_acta, 'acta' => $this->acta);
		$datos = catalogo::consultar('docente', 'detalle_acta_examen', $parametros);
        return $datos;
    }
    
    function accion__filtrar(){
		$filtros = array();
        $filtros['anio_academico'] = $this->get_param('filtro_anio', 'get');
        $filtros['turno'] = $this->get_param('filtro_turno_examen', 'get');
        $filtros['nombre'] = $this->get_param('filtro_nombre_materia', 'get');
        $pagelet = $this->vista()->pagelet('items');
		$pagelet->set_filtros($filtros);
        kernel::renderer()->add($pagelet);
    }
    
    function accion__volver(){
        $pagelet = $this->vista()->pagelet('items');
        kernel::renderer()->add($pagelet);
    }
}
?>