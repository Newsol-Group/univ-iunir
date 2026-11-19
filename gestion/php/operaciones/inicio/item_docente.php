<?php 
	$container = toba::contenedor_gadgets();

	$gadget1 = new gadget_ul_link();
	$sql = "
		select 	c.comision,
			e.elemento,
			e.nombre 
		from	sga_elementos e
		inner join
			sga_comisiones c
		on	e.elemento = c.elemento
		inner join
			sga_docentes_comision dc
		on	dc.comision = c.comision
		inner join
			sga_docentes d
		on	d.docente = dc.docente
		inner join
			mdp_personas p
		on	p.persona = d.persona
		where
			current_timestamp::date between dc.fecha_desde and dc.fecha_hasta	
			and p.usuario = '".toba::usuario()->get_id()."'
		";
	
    $gadget1->set_titulo('Comisiones de Cursado');
    $gadget1->set_icono('comisiones.png');
    $gadget1->set_eliminable('S');
    $gadget1->set_sql($sql);
    $gadget1->set_text_cols(array('nombre'));
    $gadget1->set_link_params(array('comision', 'elemento'));
    $gadget1->set_proyecto('guarani');
    $gadget1->set_item(141000062);
    $gadget1->set_orden(1);
    
    $gadget2 = new gadget_ul_link();
	$sql = "
		select 	me.mesa_examen,
				e.elemento,
				e.nombre 
			from	sga_elementos e
			inner join
				sga_mesas_examen me
			on	e.elemento = me.elemento
			inner join
				sga_llamados_mesa lm
			on	lm.mesa_examen = me.mesa_examen
			inner join
				sga_docentes_mesa_llamado dml
			on	dml.llamado_mesa = lm.llamado_mesa
			inner join
				sga_docentes d
			on	d.docente = dml.docente
			inner join
				mdp_personas p
			on	p.persona = d.persona
			where	p.usuario = '".toba::usuario()->get_id()."'
		";
	
    $gadget2->set_titulo('Mesas de Examen');
    $gadget2->set_icono('examenes.png');
    $gadget2->set_eliminable('S');
    $gadget2->set_sql($sql);
    $gadget2->set_text_cols(array('nombre'));
    $gadget2->set_link_params(array('mesa_examen', 'elemento'));
    $gadget2->set_proyecto('guarani');
    $gadget2->set_item(2);
    $gadget2->set_orden(2);

    
    $gadget3 = new gadget_falso_mensajes();
    $gadget3->set_titulo('Notificaciones');
    $gadget3->set_icono('notificaciones.png');
    $gadget3->set_eliminable('S');
    $gadget3->set_proyecto('guarani');
    $gadget3->set_item(3);
    $gadget3->set_orden(3);
    
    //Lo agrego al contenedor
    $container->agregar_gadgets(array($gadget1, $gadget2, $gadget3));

     //Grafico los gadgets
    $container->generar_html();
		echo '<div class="logo">';
	echo toba_recurso::imagen_proyecto("logo_grande.png",true);
	echo '</div>';
?>