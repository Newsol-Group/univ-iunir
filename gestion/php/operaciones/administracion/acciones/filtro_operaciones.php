<?php

/**
 * La clase debe extender especificamente del filtro de toba, para que no se tome por defecto el filtro personalizado de
 * Guarani. En este caso ese filtro no funciona correctamente porque para obtener las operaciones de una accion se ejecutan
 * sqls sobre la base de Toba, no la base de negocios de Guarani, con lo cual la funcion f_limpiar_acentos no existe.
 */
class filtro_operaciones extends toba_ei_filtro
{
	
}
?>