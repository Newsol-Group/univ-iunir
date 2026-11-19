<?php
	toba::zona()->resetear();
	toba::zona()->cargar(toba::memoria()->get_parametro('comision'));
	$info = array('elemento' => toba::memoria()->get_parametro('elemento'));
	toba::zona()->set_info($info);
	toba::zona()->set_back($_SERVER['REQUEST_URI']);

	$container = toba::contenedor_gadgets();

	$gadget1 = new gadget_ul_link();
	$gadget1->set_titulo('Alumnos');
	$gadget1->set_icono('alumnos.png');
	$gadget1->set_eliminable('S');
	$gadget1->set_ul(array(array('texto' => 'Listado', 'proyecto' => 'guarani', 'item' => 41000008), array('texto' => 'Asistencia', 'proyecto' => 'guarani', 'item' => 2), array('texto' => 'Actas', 'proyecto' => 'guarani', 'item' => 2)));
	$gadget1->set_orden(1);

	$gadget2 = new gadget_ul_link();
	$gadget2->set_titulo('Planificacion');
	$gadget2->set_icono('planificacion.png');
	$gadget2->set_eliminable('S');
	$gadget2->set_ul(array(array('texto' => 'Temas', 'proyecto' => 'guarani', 'item' => 2), array('texto' => 'Competencias', 'proyecto' => 'guarani', 'item' => 141000063)));
	$gadget2->set_orden(2);

	$gadget3 = new gadget_ul_link();
	$gadget3->set_titulo('Parciales');
	$gadget3->set_icono('parciales.png');
	$gadget3->set_eliminable('S');
	$gadget3->set_ul(array(array('texto' => 'Primer Parcial', 'proyecto' => 'guarani', 'item' => 2), array('texto' => 'Segundo Parcial', 'proyecto' => 'guarani', 'item' => 2)));
	$gadget3->set_orden(3);

	$gadget4 = new gadget_ul_link();
	$gadget4->set_titulo('Moodle');
	$gadget4->set_icono('moodle.png');
	$gadget4->set_eliminable('S');
	$gadget4->set_ul(array(array('texto' => 'Asociar', 'proyecto' => 'guarani', 'item' => 2), array('texto' => 'Desasociar', 'proyecto' => 'guarani', 'item' => 2)));
	$gadget4->set_orden(4);

	// Se agrega al contenedor
	$container->agregar_gadgets(array($gadget1, $gadget2, $gadget3, $gadget4));

	// Se grafican los gadgets
	$container->generar_html();
	echo '<div class="logo">';
	echo toba_recurso::imagen_proyecto("logo_grande.png", true);
	echo '</div>';
?>