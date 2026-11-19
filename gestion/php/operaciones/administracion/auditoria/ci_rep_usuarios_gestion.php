<?php
class ci_rep_usuarios_gestion extends ci_rep_sin_listado_inicial
{
	function get_datos($filtro)
	{
        $datos_cuadro = [];
        $perfiles_de_datos = toba::consulta_php('co_usuarios')->get_perfiles_datos();//OK
        $perfiles_funcionales = toba::consulta_php('co_usuarios')->get_grupos_acceso_gestion();//OK
        
        $datos = toba::consulta_php('co_usuarios')->get_usuarios_gestion($filtro);
        foreach ($datos as $key => $value) {
            $p_dato_valor = $this->asigna_perfil_datos($value['usuario_perfil_datos'], $perfiles_de_datos);
            if ($p_dato_valor != false){
                $value['usuario_perfil_datos'] = $p_dato_valor;
                $value['usuario_grupo_acc'] = $this->asigna_perfil_funcional($value['usuario_grupo_acc'], $perfiles_funcionales);
                array_push($datos_cuadro,$value);
            }else{
                continue;
            }            
        }
        return $datos_cuadro;
	}

	function get_tabla_principal()
	{
		return 'mdp_personas';
	}
	
    function asigna_perfil_datos($usuario_perfil_datos='', $perfiles_datos){
        if(isset($perfiles_datos))
        {
            foreach ($perfiles_datos as $p) {
                if($usuario_perfil_datos == $p['usuario_perfil_datos'])
                        return $p['nombre'];
            }
        }
        return false;
    }
    
    function asigna_perfil_funcional($usuario_perfil_funcional ='', $perfiles_funcional){
        if(isset($perfiles_funcional))
        {
            foreach ($perfiles_funcional as $p) {
                if($usuario_perfil_funcional == $p['grupo_acceso'])
                        return $p['nombre'];
            }
        }
        return false;
    }
    
}
