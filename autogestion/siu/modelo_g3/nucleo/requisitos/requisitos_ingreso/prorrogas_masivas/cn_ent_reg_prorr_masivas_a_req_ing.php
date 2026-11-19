<?php

/**
 *
 *
 * @package 	Requisitos
 * @subpackage 	Requisitos de Ingreso
 * 
 */
class cn_ent_reg_prorr_masivas_a_req_ing extends cn_procesos_bk 
{
    protected $hay_errores = false;

    function ejecutar_proceso($datos) 
    {
        $this->s__proceso = guarani::proceso_bk(pro_reg_prorr_masivas_a_req_ing::nombre);
        $this->s__proceso->set_datos($datos);
        guarani::ejecutar_proceso_bk($this->s__proceso);
    }

    function validar_inscripciones($inscripciones) 
    {
        $this->validar_existencia_inscripciones($inscripciones);

        if ($this->hay_errores()) {
            throw new guarani_error($this->get_mensaje_error());
        }
    }

    function validar_existencia_inscripciones($inscripciones) 
    {
        if (guarani_arreglos::count($inscripciones) == 0) {
            $this->set_error('800MAT_datos_masivos_req_inexistentes', array('prorrogar'));
        }
    }

    /**
     * Acumula mensajes a enviar a la pantalla
     */
    function set_error($descripcion_corta, $parms = null, $tipo = null) 
    {
        $mensaje = guarani_mensajes::get_msg_info($descripcion_corta, $parms);
        //Si no está el mensaje definido en la base
        if (!$mensaje) {
            $mensaje = array('mensaje' => $descripcion_corta, 'tipo' => comunes::error_critico);
        }
        //Si me definió un tratamiento para el mensaje
        if ($tipo) {
            $mensaje['tipo'] = $tipo;
        }
        $this->hay_errores = true;
        $this->errores[] = $mensaje;
    }

    function hay_errores()
	{
		return $this->hay_errores;	
	}
}

?>