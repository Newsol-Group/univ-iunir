<?php

class guarani_autenticacion_ldap extends guarani_pers_autenticacion_ldap
{
    protected function recuperar_usuario_toba($id_usuario)
	{
        //La función en el parent solo busca si el usuario existe en la instancia
        //En la class guarani_usuario esa validación se hace antes.
		return true;
	}
}