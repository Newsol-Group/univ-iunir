<?php
namespace siu\modelo\transacciones;

use SIU\Chulupi\kernel;
use siu\modelo\datos\catalogo;
use siu\errores\error_guarani;
use siu\errores\error_guarani_procesar_renglones;

class carga_asistencias extends transaccion
{
	protected $operacion = 'curxxxxx';
	protected $evento = 'xx';
	const TIPO_PRESENTE     = 1;
	const TIPO_HORAS        = 2;
	const TIPO_FRACCIONES	= 3;
    
    function tipo_presente() {
        return self::TIPO_PRESENTE;
    }
    
    function tipo_horas() {
        return self::TIPO_HORAS;
    }
    
    function tipo_fracciones() {
        return self::TIPO_FRACCIONES;
    }
    
	function info__clase_cabecera($clase_id)
	{
        $parametros['clase'] = $clase_id;
		$datos = catalogo::consultar('carga_asistencias', 'clase_cabecera', $parametros);
        return $datos;
	}	
	
	function info__clase_detalle($comision_id, $clase_id, $subcomision_id=null)
	{
		$parametros['comision'] = $comision_id;
		$parametros['clase'] = $clase_id;
		$parametros['subcomision'] = $subcomision_id;
        
		return catalogo::consultar('carga_asistencias', 'clase_detalle', $parametros);
	}
        
    function info__tipo_carga($comision_id)
	{
		$parametros['comision'] = $comision_id;
		return catalogo::consultar('carga_asistencias', 'tipo_asistencia', $parametros);
	}
	
	function get_datos_clase($clase_id,$seleccion_comision)
	{
		$clases = $this->info__clase_detalle($seleccion_comision, $clase_id);
        $nuevo = array();
        foreach($clases as $clase){
			$nuevo[$clase[catalogo::id]] = $clase;
		}
		return $nuevo;
	}
	
	function grabar($seleccion_clase, $seleccion_comision, $alumnos)
	{
		$error = new error_guarani_procesar_renglones('Error cargando asistencias');
		$hay_renglones_actualizados = false;	
		
		$datos_clase = $this->get_datos_clase($seleccion_clase, $seleccion_comision);
        kernel::db()->abrir_transaccion();
		
		foreach ($alumnos as $id => $alumno){
			try {
				
                $parametros = [];
                $parametros['alumno'] = $datos_clase[$id]['alumno'];
                $parametros['clase'] = $seleccion_clase;
                $parametros['motivo'] = null;
                $parametros['cantidad'] = $alumno['PRESENTE'];
                        
                $ok = catalogo::consultar('carga_asistencias', 'guardar', $parametros);
                
                if($ok==1) {
                    $hay_renglones_actualizados = true;
                } else {
                    $error->add_renglon($datos_clase[$id]['legajo'], $ok[1], $parametros);
                    //kernel::log()->add_error(new error_guarani($ok[1]));					
                }
			} catch (ErrorKernel $e) {
				$error->add_renglon($datos_clase[$id]['legajo'], $e->getMessage(), $parametros);
				//kernel::log()->add_error($e);				
			}
		}
        kernel::db()->cerrar_transaccion();
		if($error->hay_renglones()) {
            kernel::db()->abortar_transaccion($e->getMessage());
			throw $error;
		}
	}
    
    function get_planilla($parametros){
        $datos_planilla = catalogo::consultar('carga_asistencias', 'get_planilla', $parametros);
		return $datos_planilla;
    }

    function tiene_banda_horaria_asignada($es_subcomision, $comision, $subcomision){

        if($es_subcomision){
            $tiene_banda_horaria = catalogo::consultar('carga_asistencias', 'tiene_banda_horaria_subcomision', array('subcomision' => $subcomision));
        }
        else{
            $tiene_banda_horaria = catalogo::consultar('carga_asistencias', 'tiene_banda_horaria_comision', array('comision' => $comision));
        }

        return $tiene_banda_horaria;
    }
}