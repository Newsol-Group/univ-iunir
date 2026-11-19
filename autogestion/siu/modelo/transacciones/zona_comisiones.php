<?php
namespace siu\modelo\transacciones;

use acta;
use SIU\Chulupi\ErrorKernel;
use SIU\Chulupi\kernel;
use siu\errores\error_guarani;
use siu\errores\error_guarani_procesar_renglones;
use siu\modelo\datos\catalogo;
use siu\modelo\entidades\rs_arreglo;
use siu\modelo\entidades\rs_consulta;


class zona_comisiones extends transaccion
{

    const COMISION_ESTADO_ACTIVA = 'A';
    const COMISION_ESTADO_BAJA = 'B';

	//---------------------------------------------
	//	INFO
	//---------------------------------------------

	function info__comisiones($perido_lectivo = null)
	{
		if (!$perido_lectivo) {
			$perido_lectivo = $this->get_ids_periodos_lectivos_vigentes();
		}
		return $this->info__comisiones_docente(null, $perido_lectivo, null);
	}

	function info__comisiones_docente($anio_academico = null, $periodo_lectivo = null, $actividad = null, $ubicacion = null, $comisiones_activas = null)
	{
		$rs = new rs_consulta('docente', 'lista_comisiones', array(
			'anio_academico' => $anio_academico,
			'docente' => kernel::persona()->get_id_docente(),
			'periodo_lectivo' => $periodo_lectivo,
			'actividad' => $actividad,
			'ubicacion' => $ubicacion,
                        'comisiones_activas' => $comisiones_activas,
		));
		$rs->set_columna_id('id');
		$rs->agregar_entidad('comision');
		return $rs;
	}

	function get_periodo_lectivo_actual()
	{
		$periodos = catalogo::consultar('unidad_academica', 'periodos_lectivos_vigentes');
		return \guarani_arreglos::aplanar_matriz_sin_nulos($periodos,'periodo_lectivo');
	}

    function get_ids_periodos_lectivos_vigentes()
    {
        $periodos = catalogo::consultar('unidad_academica', 'periodos_lectivos_vigentes_ids');
        return \guarani_arreglos::aplanar_matriz_sin_nulos($periodos,'periodo_lectivo');
    }
	
	function info__comision_zona($comision)
	{
		$parametros = array('id' => $comision['id'],
			'docente' => kernel::persona()->get_id_docente());

		$datos = catalogo::consultar('docente', 'subcomision_cabecera', $parametros);
		if (!!$datos) return $datos[0];
		return array();

	}

	function info__comision_cabecera($datos)
	{
		if (catalogo::consultar('carga_notas_cursada_comision', 'existe_evaluacion_automatica_comision', array('entidad' => $datos['entidad']))) {
			$parametros = array('comision' => $datos['comision']);
			return catalogo::consultar('carga_notas_cursada_comision', 'comision_cabecera', $parametros);

		} else {
			$mensaje_error = kernel::traductor()->trans('800_sin_evaluacion');
			throw new error_guarani($mensaje_error);
		}
	}

}

?>