<?php
namespace siu\operaciones\inscriptos_rechazados;

use SIU\Chulupi\kernel;
use siu\guarani;
use siu\modelo\datos\catalogo;
use SIU\Chulupi\util\validador;
use siu\operaciones\_comun\operaciones\reporte\controlador_con_filtro;

class controlador extends controlador_con_filtro
{
	protected $comision;
	protected $subcomision;

        /**
         * @return int Id de la comisión.
         */
        public function getComision()
        {
            return $this->comision;
        }

        /**
         * @return int Id de la Subcomisión.
         */
        public function getSubcomision()
        {
            return $this->subcomision;
        }

	function modelo()
	{
		return guarani::inscriptos_cursadas();
	}

	function accion__info_comision()
	{
		$hash = $this->validate_param(0, 'get', validador::TIPO_ALPHANUM);
		$data_comision = $this->decodificar_hash($hash, 'info_comision');
		$this->comision = $data_comision['comision'];
		
		$clase = 'operaciones\inscriptos_rechazados\pagelet_comision';
		$pl = kernel::localizador()->instanciar($clase, 'items');
		$this->vista()->add_pagelet($pl);

		/* url botón Volver */
		$operacion = kernel::ruteador()->get_id_operacion();
		$pl->data['url_back'] = kernel::vinculador()->crear($operacion);
		$pl->data['alumnos_comision'] = $this->get_info_comision();
		$pl->data['comision'] = $data_comision;
	}

	function get_lista_comisiones()
	{
		$filtros = isset($this->datos_filtro) ? $this->datos_filtro : array();
		return $this->codificador_modelo()->info__lista_comisiones_rech($filtros);
	}

	function get_info_comision()
	{
		$parametros = array('comision' => $this->comision);
		$datos = catalogo::consultar('docente', 'alum_rechazados_comision', $parametros);
		return $datos;
	}
	
	protected function instanciar_form_builder()
	{
		return kernel::localizador()->instanciar('operaciones\inscriptos_rechazados\reporte\builder_filtro_reporte');
	}
}