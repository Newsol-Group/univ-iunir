<?php
namespace siu\modelo\transacciones;

use SIU\Chulupi\kernel;
use siu\modelo\entidades\rs_consulta;
use toba;
use siu\modelo\datos\catalogo;

class zona_clases extends zona_comisiones
{

	//---------------------------------------------
	//	INFO
	//---------------------------------------------

	function info__clases($seleccion)
	{
		$clases_no_anuladas = $this->get_listado_clases_asistencia($seleccion['comision'], $seleccion['subcomision']);
		$clases['sin_dictar'] = array();
		$clases['dictadas'] = array();
		$clases['anuladas'] = $this->get_listado_clases_anuladas($seleccion['comision'], $seleccion['subcomision']);
        
		foreach ($clases_no_anuladas as $clase) {
			if (fecha_es_menor_a_fecha_actual($clase['fecha_clase'], true)) {
				$clases['dictadas'][] = $clase;
			} else {
				$clases['sin_dictar'][] = $clase;
			}
		}
		return $clases;
	}

	/**
	 * retorna un rs para poder hacer y validar links automaticamente
	 * @param $seleccion
	 * @return \siu\modelo\entidades\rs_consulta
	 */
	function info__comision_links($seleccion){
		$parametros = array('id' => $seleccion['id']);

		$rs = new rs_consulta('zona_clases', 'subcomision_entidad', $parametros);
		$rs->set_columna_id('id');
		$rs->agregar_entidad('comision');
		return $rs;
	}



	function get_listado_clases_asistencia($comision, $subcomision=null)
	{
		if (!is_null($subcomision)) {
			$clases = toba::consulta_php('co_clases')->get_clases_subcomision_con_presentes_ausentes($subcomision);
		} else {
			$clases = toba::consulta_php('co_clases')->get_clases_comision_con_presentes_ausentes($comision);
		}
        return $clases;
	}

	protected function get_listado_clases_anuladas($comision, $subcomision=null)
	{
		if (!is_null($subcomision)) {
			return toba::consulta_php('co_clases')->get_clases_anuladas_subcomision($subcomision);
		} else {
			return toba::consulta_php('co_clases')->get_clases_anuladas_comision($comision);
		}
	}

    function get_propuesta($comision)
	{
		$parametros['comision'] = $comision;
		return catalogo::consultar('zona_clases', 'get_propuesta', $parametros);
	}
        
        
    function info__examenes_indice()
	{
                $fecha_actual = date("d-m-Y");
                $fecha_examen_desde = date("Y-m-d",strtotime($fecha_actual."- 7 days"));
		$fecha_examen_hasta = date("Y-m-d",strtotime($fecha_actual."+ 7 days"));
		

		// Para los combos de la zona
		$parametros = [
		'docente' => kernel::persona()->get_id_docente(),
		'fecha_examen_desde' => $fecha_examen_desde,
		'fecha_examen_hasta' => $fecha_examen_hasta,
		];

		$rs = new rs_consulta('docente', 'lista_mesas_examen_docente', $parametros);
		$rs->set_columna_id('llamado_mesa');
		$rs->agregar_entidad('llamado_mesa');
               
		return $rs;
	}
}
