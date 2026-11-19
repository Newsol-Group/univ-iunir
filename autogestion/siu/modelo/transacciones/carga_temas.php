<?php
namespace siu\modelo\transacciones;

use SIU\Chulupi\kernel;
use siu\modelo\datos\catalogo;
use siu\errores\error_guarani;
use siu\errores\error_guarani_procesar_renglones;

class carga_temas extends transaccion
{
	protected $operacion = 'curxxxxx';
	protected $evento = 'xx';
  
    
	function info__clase_cabecera($clase_id)
	{
        $parametros['clase'] = $clase_id;
		$datos = catalogo::consultar('carga_temas', 'clase_cabecera', $parametros);
        return $datos;
	}	
	
	function info__clase_detalle($comision_id, $clase_id, $subcomision_id=null)
	{
		$parametros['comision'] = $comision_id;
		$parametros['clase'] = $clase_id;
		$parametros['subcomision'] = $subcomision_id;
        
		return  \toba::consulta_php('co_clases')->get_clases_temas( $parametros);
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
	
	function grabar($clase, $tema_dictado, $tema_planificado)
	{
		
		
        kernel::db()->abrir_transaccion();
		
            try {
                $parametros = array();
                $parametros['clase'] = $clase;
                $parametros['tema_dictado'] = $tema_dictado;
                $parametros['tema_planificado'] = $tema_planificado;			
                        
                $ok = catalogo::consultar('carga_temas', 'guardar', $parametros);
                
                
		} catch (ErrorKernel $e) {
				kernel::log()->add_error($e);				
              	}
		
        kernel::db()->cerrar_transaccion();
	}
    
}

?>
