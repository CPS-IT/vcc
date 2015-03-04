<?php
$extensionPath = t3lib_extMgm::extPath('vcc') . 'Classes/';

return array(
	'tx_vcc_hook_abstractvarnishhook' => $extensionPath . 'Hook/Tx_Vcc_Hook_AbstractVarnishHook.php',
	'tx_vcc_hook_communicationservicehookinterface' => $extensionPath . 'Interface/CommunicationServiceHookInterface.php',
	'tx_vcc_service_communicationservice' => $extensionPath . 'Service/CommunicationService.php',
	'tx_vcc_service_extensionsettingservice' => $extensionPath . 'Service/ExtensionSettingService.php',
	'tx_vcc_service_loggingservice' => $extensionPath . 'Service/LoggingService.php',
	'tx_vcc_service_tsconfigservice' => $extensionPath . 'Service/TsConfigService.php',
);
?>