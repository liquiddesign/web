<?php

declare(strict_types=1);

namespace Web\Admin;

use Admin\BackendPresenter;
use Admin\Controls\AdminForm;
use Admin\Controls\AdminGrid;
use Nette\Utils\Image;
use Nette\Utils\Random;
use StORM\DIConnection;
use Web\DB\Gallery;
use Web\DB\GalleryImage;
use Web\DB\GalleryImageRepository;
use Web\DB\GalleryRepository;

class GalleryPresenter extends BackendPresenter
{
	/**
	 * @inject
	 */
	public GalleryRepository $galleryRepo;
	
	/**
	 * @inject
	 */
	public GalleryImageRepository $galleryImageRepo;
	
	public string $tGallery;
	
	public string $tGalleryItems;
	
	public function beforeRender(): void
	{
		parent::beforeRender();
		
		$this->tGallery = $this->_('gallery', 'Galerie');
		$this->tGalleryItems = $this->_('galleryItems', 'Obrázky galerie');
	}
	
	public function renderDefault(): void
	{
		$this->template->headerLabel = $this->tGallery;
		$this->template->headerTree = [
			[$this->tGallery],
		];
		$this->template->displayButtons = [$this->createNewItemButton('new')];
		$this->template->displayControls = [$this->getComponent('grid')];
	}
	
	public function renderNew(): void
	{
		$tNew = $this->_('newGallery', 'Nová galerie');
		$this->template->headerLabel = $tNew;
		$this->template->headerTree = [
			[$this->tGallery, 'default'],
			[$tNew],
		];
		$this->template->displayButtons = [$this->createBackButton('default')];
		$this->template->displayControls = [$this->getComponent('form')];
	}
	
	public function renderDetail(Gallery $gallery): void
	{
		$tDetail = $this->_('detailGallery', 'Detail galerie');
		$this->template->headerLabel = $tDetail. ": " . $gallery->name;
		$this->template->headerTree = [
			[$this->tGallery, 'default'],
			[$tDetail],
		];
		$this->template->displayButtons = [$this->createBackButton('default')];
		$this->template->displayControls = [$this->getComponent('form')];
	}
	
	public function renderItems(Gallery $gallery): void
	{
		$tNewItems = $this->_('newMultiItems', 'Hromadné nahrávání');
		$this->template->headerLabel = $this->tGalleryItems . ": " . $gallery->name;
		$this->template->headerTree = [
			[$this->tGallery, 'default'],
			[$this->tGalleryItems],
		];
		$newItem = $this->createNewItemButton('newItem', [$gallery]);
		$this->template->displayButtons = [$this->createBackButton('default'), $newItem, $this->createNewItemButton('newMultipleItems', [$gallery], $tNewItems)];
		$this->template->displayControls = [$this->getComponent('itemGrid')];
	}
	
	public function renderNewItem(Gallery $gallery): void
	{
		$tNewItem = $this->_('newItem', 'Nový obrázek');
		$this->template->headerLabel = $tNewItem;
		$this->template->headerTree = [
			[$this->tGallery, 'default'],
			[$this->tGalleryItems, 'items', $gallery],
			[$tNewItem],
		];
		$this->template->displayButtons = [$this->createBackButton('items', $gallery)];
		$this->template->displayControls = [$this->getComponent('itemForm')];
	}
	
	public function renderDetailItem(GalleryImage $galleryImage): void
	{
		$tDetailItem = $this->_('detailItem', 'Detail obrázku');
		$this->template->headerLabel = $tDetailItem;
		$this->template->headerTree = [
			[$this->tGallery, 'default'],
			[$this->tGalleryItems, 'items', $galleryImage->gallery],
			[$tDetailItem],
		];
		$this->template->displayButtons = [$this->createBackButton('items', $galleryImage->gallery)];
		$this->template->displayControls = [$this->getComponent('itemForm')];
	}
	
	public function renderNewMultipleItems(Gallery $gallery): void
	{
		$tNewItems = $this->_('newItems', 'Nové obrázky');
		$this->template->headerLabel = $tNewItems;
		$this->template->headerTree = [
			[$this->tGallery, 'default'],
			[$this->tGalleryItems, 'items', $gallery],
			[$tNewItems],
		];
		$this->template->displayButtons = [$this->createBackButton('items', $gallery)];
		$this->template->displayControls = [$this->getComponent('itemsForm')];
	}
	
	public function actionDetail(Gallery $gallery): void
	{
		/** @var \Admin\Controls\AdminForm $form */
		$form = $this->getComponent('form');
		$form->setDefaults($gallery->toArray());
	}
	
	public function actionDetailItem(GalleryImage $galleryImage): void
	{
		/** @var \Admin\Controls\AdminForm $form */
		$form = $this->getComponent('itemForm');
		$form->setDefaults($galleryImage->toArray());
	}
	
	public function createComponentForm(): AdminForm
	{
		$form = $this->formFactory->create(true, true, true);
		$form->addLocaleText('name', $this->_('galleryName', 'Název'));
		$form->addInteger('originWidth', $this->_('galleryMaxWidth', 'Max šířka (px)'))->setHtmlAttribute('size', 4);
		$form->addInteger('originHeight', $this->_('galleryMaxHeight', 'Max výška (px)'))
			->setHtmlAttribute('size', 4)
			->setOption('description', $this->_('galleryDescriptionMaxWidth', 'Velké foto zachováva poměr stran'))
			->setRequired(false)
			->addConditionOn($form['originWidth'], $form::EQUAL, '')
			->setRequired($this->_('galleryRequiredMaxWidth', 'Šířka nebo výška velkého náhledu musí být větší než 0px'))
			->endCondition();
		$form->addSelect('resizeMethod', $this->_('galleryResize', 'Styl ořezu náhledu'))->setItems(Gallery::RESIZE_METHODS);
		$form->addInteger('thumbWidth', $this->_('galleryThumbWidth', 'Náhled šířka (px)'))
			->setHtmlAttribute('size', 4)
			->setRequired(false)
			->addConditionOn($form['resizeMethod'], $form::EQUAL, 'EXACT')
			->setRequired(true)
			->elseCondition()
			->addConditionOn($form['resizeMethod'], $form::EQUAL, 'STRETCH')
			->setRequired(true)
			->endCondition();
		$form->addInteger('thumbHeight', $this->_('galleryThumbHeight', 'Náhled výška (px)'))
			->setHtmlAttribute('size', 4)
			->setRequired(false)
			->addConditionOn($form['thumbWidth'], $form::EQUAL, '')
			->setRequired($this->_('galleryThumbConditionSize', 'Šířka nebo výška malého náhledu musí být větší než 0px'))
			->elseCondition()
			->addConditionOn($form['resizeMethod'], $form::EQUAL, 'EXACT')
			->setRequired(true)
			->elseCondition()
			->addConditionOn($form['resizeMethod'], $form::EQUAL, 'STRETCH')
			->setRequired(true)
			->endCondition();
		$form->addText('ratio', $this->_('galleryPhotoNumber', 'Počet fotek na řádek'))
			->setDefaultValue('4/4/3')
			->setHtmlAttribute('size', 4)
			->setOption('description', $this->_('galleryPhotoNumberDescription', 'mobile/tablet/desktop např. 4/4/3'));
		$form->addText('classes', $this->_('galleryCssClass', 'CSS třídy'));
		$form->addHidden('id')->setDefaultValue(Random::generate(4));
		
		/** @var \Web\DB\Gallery $gallery */
		$gallery = $this->getParameter('gallery');
		
		$form->addSubmits(!$gallery);
		$form->onSuccess[] = function (AdminForm $form) use ($gallery): void {
			$values = $form->getValues('array');
			
			$gallery = $this->galleryRepo->syncOne($values, null, true);
			
			$this->galleryRepo->resizeImagesFromUploaded($gallery);
			
			$this->flashMessage($this->_('.saved', 'Uloženo'), 'success');
			$form->processRedirect('detail', 'default', [$gallery]);
		};
		
		return $form;
	}
	
	public function createComponentItemForm(): AdminForm
	{
		$form = $this->formFactory->create(true, true, true);
		
		$maxUpload = (int) (\ini_get('upload_max_filesize'));
		
		/** @var \Web\DB\Gallery $gallery */
		$gallery = ($this->getParameter('galleryImage') ? $this->getParameter('galleryImage')->gallery : $this->getParameter('gallery'));
		
		$form->addHidden('gallery', (string) $gallery);
		$imagePicker = $form->addImagePicker('image', $this->_('itemPicture', 'Obrázek'), [
			Gallery::IMAGE_DIR . '/upload' => null,
			Gallery::IMAGE_DIR . '/origin' => static function (Image $image) use ($gallery): void {
				$image->resize($gallery->originWidth, $gallery->originHeight, Image::FIT);
				$image->sharpen();
			},
			Gallery::IMAGE_DIR . '/thumb' => static function (Image $image) use ($gallery): void {
				$image->resize($gallery->thumbWidth, $gallery->thumbHeight, \constant('\Nette\Utils\Image::' . $gallery->resizeMethod));
				$image->sharpen();
			}])
			->addRule($form::MAX_FILE_SIZE, null, $maxUpload * 1024 * 1024)
			->setOption('description', $this->_('uploadDescription', 'Vkládejte obrázky do velikosti 2000x2000'));
		$form->addLocaleTextArea('description', $this->_('itemDescription', 'Popis'));
		$form->addInteger('priority', $this->_('.priority', 'Pořadí'))->setRequired()->setDefaultValue(10);
		$form->addCheckbox('hidden', $this->_('.hidden', 'Skryto'));
		
		/** @var \Web\DB\GalleryImage $galleryImage */
		$galleryImage = $this->getParameter('galleryImage');
		
		$imagePicker->onDelete[] = function () use ($galleryImage): void {
			if ($galleryImage) {
				$this->galleryImageRepo->deleteImage($galleryImage);
				$galleryImage->update(['image' => null]);
				$this->redirect('this');
			}
		};
		
		$form->addSubmits(!$galleryImage);
		$form->onSuccess[] = function (AdminForm $form) use ($galleryImage): void {
			$values = $form->getValues('array');
			$this->generateDirectories([Gallery::IMAGE_DIR], Gallery::SUBDIRS);
			
			$values['image'] = $form['image']->upload($values['uuid'] . '.%2$s');
			$galleryImage = $this->galleryImageRepo->syncOne($values, null, true);
			
			$this->flashMessage($this->_('.saved', 'Uloženo'), 'success');
			$form->processRedirect('detailItem', 'items', [$galleryImage], [$galleryImage->gallery]);
		};
		
		return $form;
	}
	
	public function createComponentItemsForm(): AdminForm
	{
		$form = $this->formFactory->create(false, false);
		
		$maxUpload = (int) (\ini_get('upload_max_filesize'));
		
		/** @var \Web\DB\Gallery $gallery */
		$gallery = $this->getParameter('gallery');
		
		$form->addHidden('gallery', (string) $gallery);
		$form->addMultiUpload('images', $this->_('itemsPicture', 'Obrázky'))
			->addRule($form::MAX_FILE_SIZE, null, $maxUpload * 1024 * 1024)
			->addRule($form::IMAGE, $this->_('allowedImages', 'Povoleny pouze JPG, PNG, GIF formáty.'))
			->setOption('description', $this->_('uploadDescription', 'Vkládejte obrázky do velikosti 2000x2000'));
		$form->addSubmits(false, false);
		$form->onSuccess[] = function (AdminForm $form) use ($gallery): void {
			$values = $form->getValues('array');
			$this->generateDirectories([Gallery::IMAGE_DIR], Gallery::SUBDIRS);
			
			foreach ($values['images'] as $image) {
				if ($image->isImage() && $image->isOk()) {
					$uuid = DIConnection::generateUuid();
					$ext = \pathinfo($image->getSanitizedName(), \PATHINFO_EXTENSION);
					$galleryImage = $this->galleryImageRepo->createOne([
						'uuid' => $uuid,
						'gallery' => $values['gallery'],
						'image' => $uuid . '.' . $ext,
					]);
					
					/* Prepare path, ext, origin and thumbnail */
					$path = $this->wwwDir . '/userfiles/' . Gallery::IMAGE_DIR;
					$uploadedImage = $image->toImage();
					$origin = clone $uploadedImage;
					$thumbnail = clone $uploadedImage;
					
					/* Resize both */
					$origin->resize($gallery->originWidth, $gallery->originHeight, \Nette\Utils\Image::FIT);
					$thumbnail->resize($gallery->thumbWidth, $gallery->thumbHeight, \constant('\Nette\Utils\Image::' . $gallery->resizeMethod));
					$origin->sharpen();
					$thumbnail->sharpen();
					
					$uploadedImage->save($path. '/upload/' . $galleryImage->image);
					$origin->save($path . '/origin/' . $galleryImage->image);
					$thumbnail->save($path . '/thumb/' . $galleryImage->image);
				}
			}
			
			$this->flashMessage($this->_('.saved', 'Uloženo'), 'success');
			
			if (isset($galleryImage)) {
				$form->processRedirect('detailItem', 'items', [$galleryImage], [$galleryImage->gallery]);
			}
		};
		
		return $form;
	}
	
	public function createComponentGrid(): AdminGrid
	{
		$tPictures = $this->_('galleryPictures', 'Obrázky');
		$grid = $this->gridFactory->create($this->galleryRepo->many(), 200, 'name', 'ASC', true);
		$grid->addColumnSelector();
		$grid->addColumnText($this->_('galleryName', 'Název'), 'name', '%s', 'name');
		$grid->addColumnText($this->_('.code', 'Kód'), 'id', '{control gallery-%s}', 'id');
		$grid->addColumnText($this->_('galleryMaxWidth', 'Max šířka (px)'), 'originWidth', '%s', 'originWidth');
		$grid->addColumnText($this->_('galleryMaxHeight', 'Max výška (px)'), 'originHeight', '%s', 'originHeight');
		$grid->addColumnText($this->_('galleryThumbWidth', 'Náhled šířka (px)'), 'thumbWidth', '%s', 'thumbWidth');
		$grid->addColumnText($this->_('galleryThumbHeight', 'Náhled výška (px)'), 'thumbHeight', '%s', 'thumbHeight');
		$grid->addColumnHidden();
		$grid->addColumnLink('Items', '<i title="'. $tPictures .'" class="far fa-images"></i> '. $tPictures .'');
		$grid->addColumnMutations('active', false);
		$grid->addColumnLinkDetail();
		$grid->addColumnActionDelete([$this->galleryRepo, 'deleteImages']);
		$grid->addButtonSaveAll();
		$grid->addButtonDeleteSelected([$this->galleryRepo, 'deleteImages']);
		
		return $grid;
	}
	
	public function createComponentItemGrid(): AdminGrid
	{
		$grid = $this->gridFactory->create($this->galleryImageRepo->many()->where('fk_gallery', $this->getParameter('gallery')->getPK()), 200, 'priority', 'ASC', true);
		$grid->addColumnSelector();
		$grid->addColumnImage('image', Gallery::IMAGE_DIR, 'thumb', $this->_('itemPicture', 'Obrázek'));
		$grid->addColumnText($this->_('itemDescription', 'Popis'), 'description', '%s', 'description');
		$grid->addColumnHidden();
		$grid->addColumnPriority();
		$grid->addColumnLinkDetail('DetailItem');
		$grid->addColumnActionDelete([$this->galleryImageRepo, 'deleteImage']);
		$grid->addButtonSaveAll();
		$grid->addButtonDeleteSelected([$this->galleryImageRepo, 'deleteImage']);
		
		return $grid;
	}
}
