<?php

namespace app\survey\service\strategy;

use app\survey\service\question\AreaAddressQuestion;
use app\survey\service\question\CheckboxQuestion;
use app\survey\service\question\ImageUploadQuestion;
use app\survey\service\question\PhoneQuestion;
use app\survey\service\question\RadioQuestion;
use app\survey\service\question\RateQuestion;
use app\survey\service\question\SelectQuestion;
use app\survey\service\question\SexQuestion;
use app\survey\service\question\TextQuestion;
use think\Exception;

class QuestionStrategyFactory
{
	private static $strategyMap = [
		'radio' => RadioQuestion::class,
		'checkbox' => CheckboxQuestion::class,
		'textarea' => TextQuestion::class,
		'select' => SelectQuestion::class,
		'image' => ImageUploadQuestion::class,
		'rating' => RateQuestion::class,
		'name' => TextQuestion::class,
		'phone' => PhoneQuestion::class,
		'sex' => SexQuestion::class,
		'areaAddress' => AreaAddressQuestion::class,
	];

	/**
	 * @throws Exception
	 */
	public static function create(string $type): QuestionStrategyInterface
	{
		if (!isset(self::$strategyMap[$type])) {
			throw new Exception("不支持的问题类型: {$type}");
		}

		$className = self::$strategyMap[$type];
		if (!class_exists($className)) {
			throw new Exception("问题类不存在: {$className}");
		}
		
		return new $className();
	}
}
