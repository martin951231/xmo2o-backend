<?php
	/**
	 * +----------------------------------------------------------------------
	 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
	 * +----------------------------------------------------------------------
	 * @author    合肥快鲸科技有限公司
	 * @copyright 合肥快鲸科技有限公司
	 * @link      https://www.kuaijing.com.cn
	 * @Desc      代码功能描述
	 */

	namespace app\traits;

	use Intervention\Image\Exception\ImageException;
	use Intervention\Image\Exception\InvalidArgumentException;
	use Intervention\Image\Exception\NotFoundException;
	use Intervention\Image\Exception\NotReadableException;
	use Intervention\Image\Exception\NotWritableException;
	use Intervention\Image\Exception\RuntimeException;
	use Intervention\Image\ImageManager;
	use Intervention\Image\ImageManagerStatic;
	use League\Flysystem\NotSupportedException;
	use think\Exception;

	trait ImageHandleTraits
	{

		private static function getInstanceImageManager() : ImageManager
		{
			static $instance;
			if (!$instance){
				if (extension_loaded('imagick')){
					$instance = new ImageManager(['driver' => 'imagick']);
				}else if (extension_loaded('gd')){
					$instance = new ImageManager();
				}else{
					throw new Exception('找不到 imagick 或者 gd 库支持。请确保扩展已安装或已启用');
				}
				if (!extension_loaded('exif')){
					throw new Exception('找不到 exif 库支持。请确保扩展已安装或已启用');
				}
			}
			return $instance;
		}

	
		/**
		 * 对原图片进行宽高压缩之后返回对应的处理格式
		 * @param     $imgPath  图片绝对地址 /home/wwwroot/o2o/path/image.jpg
		 * @param     $with     图片转换之后的宽度
		 * @param     $heiht    图片转换之后的高度
		 * @param string    $format    转换的格式 @see https://image.intervention.io/v2/api/encode
		 * @param int       $quality  图片压缩质量，默认 90 ，建议在 70~90 之间
		 *      
		 *                            $format ：
		 *                              jpg — return JPEG encoded image data
		 *								png — return Portable Network Graphics (PNG) encoded image data
		 *								gif — return Graphics Interchange Format (GIF) encoded image data
		 *								tif — return Tagged Image File Format (TIFF) encoded image data
		 *								bmp — return Bitmap (BMP) encoded image data
		 *								ico — return ICO encoded image data
		 *								psd — return Photoshop Document (PSD) encoded image data
		 *								webp — return WebP encoded image data
		 *								data-url — encode current image data in data URI scheme (RFC 2397)
		 * @return string
		 * @throws Exception
		 */
		public function traitEncodeImgFormat($imgPath,$with,$heiht,string $format = 'data-url',int $quality=90)
		{
 			try {
				return	(string)self::getInstanceImageManager()
							->make($imgPath)
							->fit($with,$heiht,function ($constraint){
								$constraint->aspectRatio();
								$constraint->upsize(); 	    // 避免处理时造成文件大小增加
							})->encode($format,$quality);
			}catch (Exception $e){
				return  ['err_msg'=>$e->getMessage()];
			}catch (RuntimeException $e){
                return  ['err_msg'=>$e->getMessage()];
			}catch (NotReadableException $e){
                return  ['err_msg'=>$e->getMessage()];
			}catch (NotWritableException $e){
                return  ['err_msg'=>$e->getMessage()];
			}catch (ImageException $e){
                return  ['err_msg'=>$e->getMessage()];
			}catch (InvalidArgumentException $e){
                return  ['err_msg'=>$e->getMessage()];
			}catch (NotFoundException $e){
                return  ['err_msg'=>$e->getMessage()];
			}catch (NotSupportedException $e){
                return  ['err_msg'=>$e->getMessage()];
			}
		}

		/**
		 * 对原图片进行宽高压缩之后返回对应的处理格式 并且另存
		 * @param string $imgPath   图片绝对地址 /home/wwwroot/o2o/path/image.png
		 * @param string $savePath  图片另存绝对地址 /home/wwwroot/o2o/path/image-as.jpg
		 * @param int $with      图片转换之后的宽度
		 * @param int $heiht     图片转换之后的高度
		 * @param string $format    转换的格式 @see https://image.intervention.io/v2/api/encode
		 * @param int    $quality   图片压缩质量，默认 90 ，建议在 70~90 之间
		 *
		 * @return string
		 * @throws \Exception
		 */
		public function traitEncodeImgFormatAndSaveAs($imgPath,$savePath,$with,$heiht,string $format = 'data-url',int $quality=90)
		{
			try {
				return	(string)self::getInstanceImageManager()->make($imgPath)->fit($with,$heiht,function ($constraint){
					$constraint->aspectRatio();
					$constraint->upsize(); 	    // 避免处理时造成文件大小增加
				})->encode($format,$quality)->save($savePath);
			}catch (\Exception $e){
                return  ['err_msg'=>$e->getMessage()];
			}catch (RuntimeException $e){
                return  ['err_msg'=>$e->getMessage()];
            }catch (NotReadableException $e){
                return  ['err_msg'=>$e->getMessage()];
            }catch (NotWritableException $e){
                return  ['err_msg'=>$e->getMessage()];
            }catch (ImageException $e){
                return  ['err_msg'=>$e->getMessage()];
            }catch (InvalidArgumentException $e){
                return  ['err_msg'=>$e->getMessage()];
            }catch (NotFoundException $e){
                return  ['err_msg'=>$e->getMessage()];
            }catch (NotSupportedException $e){
                return  ['err_msg'=>$e->getMessage()];
            }
		}

        public function compressImageTrait($imgPath,$savePath='',$with=0,$heiht=0 ,int $quality = 80)
        {
            
            $mimeExtension = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/gif' => 'gif',
                'image/webp' => 'webp',
                'image/bmp' => 'bmp',
                'image/tiff' => 'tiff',
            ];
            //$mime = image_type_to_mime_type($type);
            $imgArr=explode('.',$imgPath);
            $keyType=count($imgArr)-1;
            $typeTmp=isset($imgArr[$keyType]) ? $imgArr[$keyType]:'';
            $extension=$typeTmp;
            
            try {
                $image = self::getInstanceImageManager()->make($imgPath);
                $image->resize($with, $heiht, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                })->save($savePath, $quality);

                $image->destroy();
            } catch (\Exception $e) {
                return  ['err_msg'=>$e->getMessage()];
            }

            return ['err_msg'=>'','savePath'=>$savePath, 'extension'=>$extension];
        }
	}