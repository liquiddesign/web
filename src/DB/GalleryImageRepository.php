<?php

declare(strict_types=1);

namespace Web\DB;

use Nette\Utils\FileSystem;
use StORM\Repository;

class GalleryImageRepository extends Repository
{
	public string $wwwDir;
	
	public function deleteImage(GalleryImage $image): void
	{
		$subDirs = Gallery::SUBDIRS;
		$dir = Gallery::IMAGE_DIR;
		
		if (!$image->image) {
			return;
		}
		
		foreach ($subDirs as $subDir) {
			$rootDir = $this->wwwDir . '/userfiles/' . $dir;
			FileSystem::delete($rootDir . '/' . $subDir . '/' . $image->image);
		}
	}
}
