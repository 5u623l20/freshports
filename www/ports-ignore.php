<?php
	#
	# $Id: ports-ignore.php,v 1.2 2006-12-17 12:06:15 dan Exp $
	#
	# Copyright (c) 1998-2004 DVL Software Limited
	#

	require_once($_SERVER['DOCUMENT_ROOT'] . '/../include/common.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/../include/freshports.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/../include/databaselogin.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/../include/getvalues.php');

	require_once($_SERVER['DOCUMENT_ROOT'] . '/../include/freshports_page_list_ports.php');

	# not using this yet, but putting it in here.	
	if (IsSet($_REQUEST['branch'])) {
		$Branch = NormalizeBranch(htmlspecialchars($_REQUEST['branch']));
	} else {
		$Branch = BRANCH_HEAD;
	}
	
	$attributes = array('branch' => $Branch);

	$page = new freshports_page_list_ports($attributes);
	
	$page->setDebug(0);

	$page->setDB($db);

	$page->setTitle('Ignored ports');
	$page->setDescription('These are the ignored ports');


	$page->setSQL("ports.ignore <> ''", $User->id);

	$page->display();
