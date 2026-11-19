<?php
namespace siu\modelo\transacciones;

use acta;
use SIU\Chulupi\ErrorKernel;
use SIU\Chulupi\kernel;
use siu\errores\error_guarani;
use siu\errores\error_guarani_procesar_renglones;
use siu\modelo\datos\catalogo;
use siu\modelo\entidades\alumno_foto;
use siu\modelo\entidades\rs_arreglo;
use siu\modelo\entidades\rs_consulta;


class admin_derechos_comision extends transaccion
{

	function iniciar() {}
	
	//---------------------------------------------
	//	INFO
	//---------------------------------------------
	
	function info__comisiones($filtro)
	{
		$parametros = array('persona' => $this->persona);

		if(isset($filtro['actividad'])){
			$parametros['actividad'] = $filtro['actividad'];
		}else{
			$parametros['actividad'] = null;
		}

		$datos = catalogo::consultar('derechos_comision', 'lista_comisiones_docente', $parametros);
		
		$rs = new rs_arreglo($datos);
		$rs->set_columna_id('comision');
		$rs->agregar_entidad('comision');

		return $rs;
	}

	
	function info__comision_cabecera($datos)
	{ 
		$parametros = array('comision' => $datos['comision']);
		$consulta = catalogo::consultar('derechos_comision', 'comision_cabecera', $parametros);
		return $consulta;

	}


	function info__derechos_comision($datos)
	{
		$parametros = array('comision' => $datos['comision']);

		return catalogo::consultar('derechos_comision', 'derechos_comision', $parametros);
	}

	public function info__docentes_comision($datos){
		$parametros = array('comision' => $datos['comision']);
		$docentes_comision = catalogo::consultar('derechos_comision', 'docentes_comision', $parametros);

        foreach($docentes_comision as $key => $docente_comision){
            $id_imagen_docente = ($docente_comision['id_imagen'])? $docente_comision['id_imagen'] : null;
            $docentes_comision[$key]['url_imagen_chica'] = alumno_foto::url_imagen($id_imagen_docente);
        }

        return $docentes_comision;

	}

	public function info__derechos_para_comision(){
		return catalogo::consultar('derechos_comision', 'derechos_asignables');
	}


	function info__actividades_docente($params)
	{
		$parametros = array('persona' => $this->persona, 'term' => $params['term']);

		return catalogo::consultar('derechos_comision', 'lista_actividades_docente', $parametros);
	}

    public function info__responsabilidad_docente_comision($docente, $comision){

        $parametros = array('docente' => $docente, 'comision' => $comision);

        return catalogo::consultar('derechos_comision', 'responsabilidad_docente_comision', $parametros);
    }

    public function info__derechos_comision_rol($rol){

        $parametros = array('rol' => $rol);

        return catalogo::consultar('derechos_comision', 'derechos_comision_rol', $parametros);
    }

	//---------------------------------------------
	//	EVENTOS
	//---------------------------------------------

	function evt__procesar_cambios($comision_params, $cambios)
	{

		foreach ($cambios as $derecho) {
			$parametros = array('entidad' => $comision_params['entidad'],
								'persona_' => $derecho[1],
								'derecho' => $derecho[2]);
			if($derecho[0] == 'A'){
				$afectados = catalogo::consultar('derechos_comision', 'agregar_derecho', $parametros);
			}else if($derecho[0] == 'B'){
				$afectados = catalogo::consultar('derechos_comision', 'eliminar_derecho', $parametros);
			}

			if($afectados != 1){
			    $error = new error_guarani_procesar_renglones();
				throw $error;
			}
		}

	}

}
?>