<?php
require_once dirname(__DIR__).'/class.php';
try { PXcodeSupport::admin(); $content=plugin_xcode::manage(); }
catch(InvalidArgumentException|DomainException|OutOfBoundsException|JsonException $e){http_response_code(http_response_code()>=400?http_response_code():400);$content=PXcodeSupport::error($e->getMessage());}
catch(RuntimeException $e){if($e->getCode()!==409)throw $e;http_response_code(409);$content=PXcodeSupport::error($e->getMessage());}
