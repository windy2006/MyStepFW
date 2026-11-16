<?php
require_once dirname(__DIR__).'/class.php';
try { PCrontabSupport::admin(); $content=plugin_crontab::manage(); }
catch(InvalidArgumentException|DomainException|OutOfBoundsException|JsonException $e){http_response_code(http_response_code()>=400?http_response_code():400);$content=PCrontabSupport::error($e->getMessage());}
catch(RuntimeException $e){if($e->getCode()!==409)throw $e;http_response_code(409);$content=PCrontabSupport::error($e->getMessage());}
