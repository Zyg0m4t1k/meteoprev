<?php

/* This file is part of Jeedom. */

try {
	require_once __DIR__ . '/../../../../core/php/core.inc.php';
	include_file( 'core', 'authentification', 'php' );
	ajax::init();
	if ( !isConnect() ) {
		throw new Exception( __( '401 - Accès non autorisé', __FILE__ ), -1234 );
	}

	$id = filter_var( init( 'id' ), FILTER_VALIDATE_INT );
	if ( $id === false || $id <= 0 ) {
		throw new Exception( __( 'Identifiant d’équipement invalide', __FILE__ ) );
	}
	$eq = meteoprev::byId( $id );
	if ( !is_object( $eq ) || !$eq->hasRight( 'r' ) ) {
		throw new Exception( __( 'Équipement MeteoPrev introuvable ou accès refusé', __FILE__ ), -1234 );
	}

	$action = init( 'action' );
	$datas = $eq->loadCache( true );
	if ( $action === 'getDatas' ) {
		ajax::success( $datas );
	}

	if ( $action === 'getForecastHtml' || $action === 'getStation' ) {
		$template = $action === 'getStation' ? 'graph.html' : 'forecast.html';
		$templateFile = __DIR__ . '/../config/' . $template;
		if ( !is_readable( $templateFile ) ) {
			throw new Exception( __( 'Template MeteoPrev indisponible', __FILE__ ) );
		}
		$replace = array();
		foreach ( $datas as $key => $values ) {
			if ( !is_array( $values ) ) {
				continue;
			}
			foreach ( $values as $subKey => $value ) {
				if ( !is_array( $value ) ) {
					$replace[ '#' . $key . '_' . $subKey . '#' ] = htmlspecialchars( (string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' );
				}
			}
		}
		$result = array(
			'state' => 'ok',
			'html' => template_replace( $replace, file_get_contents( $templateFile ) ),
		);
		if ( $action === 'getStation' ) {
			$result[ 'station' ] = $eq->getSafeStation();
		}
		ajax::success( $result );
	}

	throw new Exception( __( 'Aucune méthode correspondante à : ', __FILE__ ) . $action );
} catch ( Throwable $e ) {
	ajax::error( displayExeption( $e ), $e->getCode() );
}
