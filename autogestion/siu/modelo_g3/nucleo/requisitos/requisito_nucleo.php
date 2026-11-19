<?php
/**
 * Catalogo de tipos de requisitos
 *
 * @package 	Requisitos
 * @subpackage 	-
 * 
 */
class requisito_nucleo
{
	// Tipos de Requisitos
	const tipo_correlativas_cursar = 1;
	const tipo_correlativas_rendir = 2;
	const tipo_correlativas_especiales = 3;
    const tipo_documentales = 4;
	const tipo_documentales_nombre = 'Documentales';
    const tipo_procesos = 5;
	const tipo_procesos_nombre = 'Procesos';

    // Componente de un Requisito Entidad (si es un requisito con parámetros o una entidad estado)
    const comp_requisito = 'R';
    const comp_entidad_estado = 'E';
    const comp_entidad = 'E';
    
    //Opción de Requisito o Negación.
    const requisito = 0;
    const not_requisito = 1;
    
    // Tipos de Requisitos que se pueden agregar a una acción.
	const req_existente = 'RE';
	const ent_estado = 'EE';

	//Estados de Requisitos
	const estado_vigente	= 'VIGENTE';
	const estado_pendiente	= 'PENDIENTE';
	const estado_vencido	= 'VENCIDO';
	
	// Valores de campos autocompletar de Requisitos por Operación.
	const autocompletar_campo_comportamiento	= 'C';
	const autocompletar_campo_estado 			= 'E';
	
	const autocompletar_para_gestion 		= 1;
	const autocompletar_para_autogestion 	= 2;
	const autocompletar_para_todos			= 3;

	// Requisito propuesta para ingreso o egreso.
	const requisito_propuesta_para_ingreso = 'I';
	const requisito_propuesta_para_egreso = 'E';

	// Requisitos solitiados para ingres/egreso.
	const requisito_para_ingreso_descr = 'Ingreso';
	const requisito_para_egreso_descr = 'Egreso';
	const requisito_para_ingreso_egreso_descr = 'Ingreso y egreso';
	const requisito_para_ninguno_descr = '---';
}
?>
