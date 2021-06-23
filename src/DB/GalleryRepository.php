<?php

declare(strict_types=1);

namespace Web\DB;

use Nette\Utils\Image;
use StORM\Repository;

class GalleryRepository extends Repository
{
	public string $wwwDir;
	
	public function resizeImagesFromUploaded(Gallery $gallery): void
	{
		$path = $this->wwwDir . '/userfiles/' . Gallery::IMAGE_DIR;
		
		foreach ($gallery->images as $image) {
			$linkUploaded = $path . '/upload/' . $image->image;
			
			if (!\file_exists($linkUploaded)) {
				return;
			}
			
			$uploadedImage = Image::fromFile($linkUploaded);
			
			$newThumb = clone $uploadedImage;
			$newOrig = clone $uploadedImage;
			//$image->resize();
			$method = $gallery->resizeMethod;
			$newThumb->resize($gallery->thumbWidth, $gallery->thumbHeight, \constant('\Nette\Utils\Image::' . $method));
			$newOrig->resize($gallery->originWidth, $gallery->originHeight, \Nette\Utils\Image::FIT);
			// save do thumb url
			$newThumb->save($path . '/thumb/' . $image->image);
			$newOrig->save($path . '/origin/' . $image->image);
		}
	}
	
	public function deleteImages(Gallery $gallery): void
	{
		foreach ($gallery->images as $image) {
			$this->getConnection()->findRepository(GalleryImage::class)->deleteImage($image);
		}
	}
}
