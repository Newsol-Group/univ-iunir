<?php

namespace siu\operaciones\inicio_docente;

use siu\guarani;
use siu\extension_kernel\controlador_g3w2;
use SIU\Chulupi\kernel;
use siu\modelo\entidades\parametro;
use siu\modelo\_comun\comunes;

class controlador extends controlador_g3w2
{
	function get_clase_vista()
	{
		return 'vista';
	}

	/**
	 * @return \siu\modelo\transacciones\zona_clases
	 */
	function modelo()
	{
		return guarani::zona_clases();
	}

	function accion__index()
	{
		$this->vista()->agregar_a_contexto('persona', kernel::persona()->get_nombre());
	}

	function info_encuestas_pendientes()
	{
		return kernel::persona()->get_encuestas_pendientes();
	}

	function get_comisiones($comision)
	{        
		$comisiones = $this->codificador_modelo()->info__comision_links($comision);
        //Mando como parametro la clase para seleccionar el combo
		$comisiones->link('LINK_ASISTENCIA', 'asistencias', 'index', ['1' => $comision['clase']]);
        
		$nuevo = [];
		foreach ($comisiones as $key => $fila) {
			$planes = aplanar_matriz(\toba::consulta_php('co_comisiones')->get_planes_comision($fila['comision']), 'plan');
			$registra_asistencia = parametro::get_valor('asistencia_registrar_asistencia', $planes);
			$fila['LINK_ASISTENCIA'] = ($registra_asistencia == comunes::no ) ? '': $fila['LINK_ASISTENCIA'] ;
			$nuevo[$fila['comision']] = $fila;
		}
		return $nuevo;
	}
        function get_mesa_examen($mesa_examen)
	{
		
		$mesas = $this->codificador_modelo()->info__zona_examenes_links($mesa_examen);
		$mesas->link('LINK', 'zona_examenes', 'home');
               
		return $mesas;
	}

}