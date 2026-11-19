<?php
namespace siu\operaciones\acta_promociones;

use SIU\Chulupi\kernel;
use siu\operaciones\_comun\operaciones\reporte\controlador as controlador_reporte;
use SIU\Chulupi\util\validador;
use siu\modelo\datos\catalogo;

class controlador extends controlador_reporte
{
    protected $acta;
    
    function accion__info_acta()
    {        
        $this->acta = $this->validate_param('accion', 'get', validador::TIPO_ALPHANUM);        
        
        $clase = 'operaciones\acta_promociones\pagelet_acta';
		$pl = kernel::localizador()->instanciar($clase, 'items');
        $this->vista()->add_pagelet($pl);
        
		/* url botón Volver */
		$operacion = kernel::ruteador()->get_id_operacion();
		$pl->data['url_back'] = kernel::vinculador()->crear($operacion);
		
        $pl->data['alumnos'] = $this->get_info_acta();
        
    }
    
    function get_info_acta(){        
        $parametros = array('acta' => $this->acta);        
		$datos = catalogo::consultar('docente', 'detalle_acta_promociones', $parametros);        
        return $datos;
    }

    function accion__filtrar(){		
		$filtros = array();
        $filtros['anio_academico'] = $this->get_param('filtro_anio', 'get');
        $filtros['periodo_lectivo'] = $this->get_param('filtro_per_lectivo', 'get');
        $filtros['nombre'] = $this->get_param('filtro_nombre_materia', 'get');
        $pagelet = $this->vista()->pagelet('items');        
		$pagelet->set_filtros($filtros);
        kernel::renderer()->add($pagelet);
    }
}
?>