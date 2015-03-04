<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "vcc".
 *
 * Auto generated 04-03-2015 11:13
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = array (
	'title' => 'Varnish Cache Control',
	'description' => 'Extension to clear Varnish cache on demand',
	'author' => 'Nicole Cordes',
	'author_email' => 'cordes@cps-it.de',
	'author_company' => 'CPS-IT',
	'category' => 'module',
	'constraints' => 
	array (
		'depends' => 
		array (
			'cms' => '',
			'php' => '5.2.0-0.0.0',
			'typo3' => '4.5.0-6.2.99',
		),
		'conflicts' => 
		array (
		),
		'suggests' => 
		array (
		),
	),
	'state' => 'stable',
	'version' => '2.0.0',
	'clearCacheOnLoad' => 0,
	'createDirs' => '',
	'uploadfolder' => 0,
	'_md5_values_when_last_written' => 'a:35:{s:9:"ChangeLog";s:4:"ebd4";s:16:"ext_autoload.php";s:4:"0d79";s:21:"ext_conf_template.txt";s:4:"0425";s:12:"ext_icon.gif";s:4:"7736";s:17:"ext_localconf.php";s:4:"f137";s:14:"ext_tables.php";s:4:"816b";s:14:"ext_tables.sql";s:4:"685c";s:48:"Classes/Hook/Tx_Vcc_Hook_AbstractVarnishHook.php";s:4:"391d";s:47:"Classes/Hook/Tx_Vcc_Hook_ClearCacheIconHook.php";s:4:"29d5";s:54:"Classes/Hook/Tx_Vcc_Hook_ClearCachePostProcessHook.php";s:4:"14dc";s:55:"Classes/Hook/Tx_Vcc_Hook_RecordSavedPostProcessHook.php";s:4:"5295";s:55:"Classes/Interface/CommunicationServiceHookInterface.php";s:4:"38e8";s:40:"Classes/Service/CommunicationService.php";s:4:"bf0e";s:43:"Classes/Service/ExtensionSettingService.php";s:4:"ef78";s:34:"Classes/Service/LoggingService.php";s:4:"3295";s:35:"Classes/Service/TsConfigService.php";s:4:"7786";s:26:"Documentation/Includes.txt";s:4:"cd93";s:23:"Documentation/Index.rst";s:4:"68e5";s:26:"Documentation/Settings.yml";s:4:"3727";s:25:"Documentation/default.vcl";s:4:"cb01";s:38:"Documentation/Administration/Index.rst";s:4:"ae09";s:61:"Documentation/Administration/ExtensionConfiguration/Index.rst";s:4:"8589";s:51:"Documentation/Administration/Installation/Index.rst";s:4:"ecdc";s:45:"Documentation/Administration/PageTs/Index.rst";s:4:"0f1e";s:59:"Documentation/Administration/VarnishConfiguration/Index.rst";s:4:"8b74";s:64:"Documentation/Images/Introduction/Screenshots/IconInEditView.png";s:4:"71dc";s:63:"Documentation/Images/Introduction/Screenshots/IconInWebView.png";s:4:"9bbd";s:58:"Documentation/Images/Introduction/Screenshots/Messages.png";s:4:"2e60";s:36:"Documentation/Introduction/Index.rst";s:4:"3384";s:48:"Documentation/Introduction/Screenshots/Index.rst";s:4:"3140";s:44:"Documentation/Introduction/Support/Index.rst";s:4:"bf43";s:49:"Documentation/Introduction/WhatDoesItDo/Index.rst";s:4:"6f61";s:36:"Documentation/Requirements/Index.rst";s:4:"11ce";s:38:"Resources/Public/Icons/CachePlugin.png";s:4:"4312";s:14:"doc/manual.sxw";s:4:"b4b2";}',
);

