<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "vcc".
 *
 * Auto generated 13-03-2018 14:47
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
      'typo3' => '7.6.0-8.7.99',
    ),
    'conflicts' => 
    array (
    ),
    'suggests' => 
    array (
    ),
  ),
  'state' => 'stable',
  'version' => '3.1.0',
  'clearCacheOnLoad' => 0,
  'createDirs' => '',
  'uploadfolder' => 0,
  'autoload' => 
  array (
    'psr-4' => 
    array (
      'CPSIT\\Vcc\\' => 'Classes',
    ),
  ),
  '_md5_values_when_last_written' => 'a:35:{s:9:"ChangeLog";s:4:"9de5";s:9:"Readme.md";s:4:"78c9";s:21:"ext_conf_template.txt";s:4:"0425";s:12:"ext_icon.gif";s:4:"7736";s:17:"ext_localconf.php";s:4:"565d";s:14:"ext_tables.php";s:4:"08da";s:14:"ext_tables.sql";s:4:"685c";s:37:"Classes/Hooks/AbstractVarnishHook.php";s:4:"83c5";s:36:"Classes/Hooks/ClearCacheIconHook.php";s:4:"fcbb";s:43:"Classes/Hooks/ClearCachePostProcessHook.php";s:4:"2dd4";s:44:"Classes/Hooks/RecordSavedPostProcessHook.php";s:4:"796b";s:40:"Classes/Service/CommunicationService.php";s:4:"b428";s:53:"Classes/Service/CommunicationServiceHookInterface.php";s:4:"94c0";s:43:"Classes/Service/ExtensionSettingService.php";s:4:"68f4";s:34:"Classes/Service/LoggingService.php";s:4:"be91";s:35:"Classes/Service/TsConfigService.php";s:4:"bc1a";s:26:"Documentation/Includes.txt";s:4:"cd93";s:23:"Documentation/Index.rst";s:4:"68e5";s:26:"Documentation/Settings.yml";s:4:"3727";s:25:"Documentation/default.vcl";s:4:"cb01";s:38:"Documentation/Administration/Index.rst";s:4:"ae09";s:61:"Documentation/Administration/ExtensionConfiguration/Index.rst";s:4:"8589";s:51:"Documentation/Administration/Installation/Index.rst";s:4:"ecdc";s:45:"Documentation/Administration/PageTs/Index.rst";s:4:"0f1e";s:59:"Documentation/Administration/VarnishConfiguration/Index.rst";s:4:"8b74";s:64:"Documentation/Images/Introduction/Screenshots/IconInEditView.png";s:4:"71dc";s:63:"Documentation/Images/Introduction/Screenshots/IconInWebView.png";s:4:"9bbd";s:58:"Documentation/Images/Introduction/Screenshots/Messages.png";s:4:"2e60";s:36:"Documentation/Introduction/Index.rst";s:4:"3384";s:48:"Documentation/Introduction/Screenshots/Index.rst";s:4:"3140";s:44:"Documentation/Introduction/Support/Index.rst";s:4:"bf43";s:49:"Documentation/Introduction/WhatDoesItDo/Index.rst";s:4:"6f61";s:36:"Documentation/Requirements/Index.rst";s:4:"11ce";s:38:"Resources/Public/Icons/CachePlugin.png";s:4:"4312";s:14:"doc/manual.sxw";s:4:"b4b2";}',
);

