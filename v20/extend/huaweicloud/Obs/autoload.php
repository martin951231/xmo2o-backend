<?php


$mapping = [
	'Obs\Internal\Common\CheckoutStream' => __DIR__.'/Internal/Common/CheckoutStream.php',
	'Obs\Internal\Common\ITransform' => __DIR__.'/Internal/Common/ITransform.php',
	'Obs\Internal\Common\Model' => __DIR__.'/Internal/Common/Model.php',
	'Obs\Internal\Common\ObsTransform' => __DIR__.'/Internal/CommonTransform.php',
	'Obs\Internal\Common\SchemaFormatter' => __DIR__.'/Internal/Common/SchemaFormatter.php',
	'Obs\Internal\Common\SdkCurlFactory' => __DIR__.'/Internal/Common/SdkCurlFactory.php',
	'Obs\Internal\Common\SdkStreamHandler' => __DIR__.'/Internal/Common/SdkStreamHandler.php',
	'Obs\Internal\Common\ToArrayInterface' => __DIR__.'/Internal/Common/ToArrayInterface.php',
	'Obs\Internal\Common\V2Transform' => __DIR__.'/Internal/Common/V2Transform.php',
	'Obs\Internal\GetResponseTrait' => __DIR__.'/Internal/GetResponseTrait.php',
	'Obs\Internal\Resource\Constants' => __DIR__.'/Internal/Resource/Constants.php',
	'Obs\Internal\Resource\OBSConstants' => __DIR__.'/Internal/ResourceConstants.php',
	'Obs\Internal\Resource\OBSRequestResource' => __DIR__.'/Internal/ResourceRequestResource.php',
	'Obs\Internal\Resource\V2Constants' => __DIR__.'/Internal/Resource/V2Constants.php',
	'Obs\Internal\Resource\V2RequestResource' => __DIR__.'/Internal/Resource/V2RequestResource.php',
	'Obs\Internal\SendRequestTrait' => __DIR__.'/Internal/SendRequestTrait.php',
	'Obs\Internal\Signature\AbstractSignature' => __DIR__.'/Internal/Signature/AbstractSignature.php',
	'Obs\Internal\Signature\DefaultSignature' => __DIR__.'/Internal/Signature/DefaultSignature.php',
	'Obs\Internal\Signature\SignatureInterface' => __DIR__.'/Internal/Signature/SignatureInterface.php',
	'Obs\Internal\Signature\V4Signature' => __DIR__.'/Internal/Signature/V4Signature.php',
	'Obs\Log\ObsConfig' => __DIR__.'/Log/ObsConfig.php',
	'Obs\Log\ObsLog' => __DIR__.'/Log/ObsLog.php',
	'Obs\ObsClient' => __DIR__.'/ObsClient.php',
	'Obs\ObsException' => __DIR__.'/ObsException.php',
];


spl_autoload_register(function ($class) use ($mapping) {
    if (isset($mapping[$class])) {
        require $mapping[$class];
    }
}, true);
