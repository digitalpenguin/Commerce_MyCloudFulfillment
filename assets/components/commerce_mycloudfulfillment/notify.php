<?php

use DigitalPenguin\MyCloudFulfillment\Webhook\Handler;

define('MODX_REQP',false);
$_REQUEST['ctx'] = 'web';

require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/config.core.php';
require_once MODX_CORE_PATH . 'config/' . MODX_CONFIG_KEY . '.inc.php';
require_once MODX_CONNECTORS_PATH . 'index.php';

$corePath = $modx->getOption('commerce.core_path', null, $modx->getOption('core_path') . 'components/commerce/');
require_once $corePath . 'model/commerce/commerce.class.php';
$mode = $modx->getOption('commerce.mode', null, Commerce::MODE_TEST);
$commerce = new Commerce($modx, array(
    'mode' => $mode
));

$modx->lexicon->load('commerce:default');

$handler = new Handler($commerce);
$response = $handler->handle($_REQUEST);
http_response_code($response->getResponseCode());
echo $response->getResponse();

@session_write_close();
exit();