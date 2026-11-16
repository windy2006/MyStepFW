<?php
require_once dirname(__DIR__).'/class.php';
try { PSeDetectSupport::admin(); $content=plugin_se_detect::manage(); }
catch(InvalidArgumentException|DomainException|OutOfBoundsException|JsonException $e){http_response_code(http_response_code()>=400?http_response_code():400);$content=PSeDetectSupport::error($e->getMessage());}
catch(RuntimeException $e){if($e->getCode()!==409)throw $e;http_response_code(409);$content=PSeDetectSupport::error($e->getMessage());}
