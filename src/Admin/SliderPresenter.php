<?php

declare(strict_types=1);

namespace Web\Admin;

use Admin\BackendPresenter;
use Admin\Controls\AdminForm;
use Web\DB\HomepageSlide;
use Web\DB\HomepageSlideRepository;
use Nette\Forms\IControl;
use Nette\Http\FileUpload;
use Nette\Http\Request;
use Nette\Utils\FileSystem;
use Nette\Utils\Html;
use Nette\Utils\Image;
use StORM\DIConnection;

class SliderPresenter extends BackendPresenter
{
	/** @inject */
	public HomepageSlideRepository $slideRepo;

	/** @inject */
	public Request $request;

	public function renderDefault()
	{
		$this->template->headerLabel = 'Slider na úvodu';
		$this->template->headerTree = [
			['Slider na úvodu'],
		];
		$this->template->displayButtons = [$this->createNewItemButton('new')];
		$this->template->displayControls = [$this->getComponent('grid')];
	}

	public function renderNew()
	{
		$this->template->headerLabel = 'Nový slider';
		$this->template->headerTree = [
			['Slider na úvodu', 'default'],
			['Nový']
		];
		$this->template->displayButtons = [$this->createBackButton('default')];
		$this->template->displayControls = [$this->getComponent('form')];
	}

	public function renderDetail()
	{
		$this->template->headerLabel = 'Detail slideru';
		$this->template->headerTree = [
			['Slider', 'default'],
			['Detail']
		];
		$this->template->displayButtons = [$this->createBackButton('default')];
		$this->template->displayControls = [$this->getComponent('form')];
	}

	public function actionDetail(HomepageSlide $slide)
	{
		/** @var AdminForm $form */
		$form = $this->getComponent('form');
		$form->setDefaults($slide->toArray());
	}

	public function createComponentForm(): AdminForm
	{
		$form = $this->formFactory->create();
		$imageDir = $this->wwwDir . \DIRECTORY_SEPARATOR . 'userfiles' . \DIRECTORY_SEPARATOR . HomepageSlide::IMAGE_DIR;

		/** @var HomepageSlide $homepageSlide */
		$homepageSlide = $this->getParameter('slide');

		$form->addRichEdit('text', 'Popisek');
		$form->addRadioList('type', 'Typ', ['image' => 'Obrázek', 'video' => 'Video'])->setDefaultValue('image');
		
		$imagePickerDesktop = $form->addImagePicker('image', 'Obrázek (desktop) *', [
			HomepageSlide::IMAGE_DIR . \DIRECTORY_SEPARATOR . 'desktop' => static function (Image $image): void {
				$image->resize(820, 410, Image::EXACT);
			},
		])->setHtmlAttribute('data-info', 'Nahrávejte obrázky o minimální velikosti 820x410 px')
			->addRule([$this, 'validateSliderImage'], 'Obrázek je příliš malý!', [$form]);

		$imagePickerDesktop->onDelete[] = function (array $directories, $filename) use ($homepageSlide, $imageDir) {
			if ($homepageSlide->image) {
				FileSystem::delete($imageDir . \DIRECTORY_SEPARATOR . 'desktop' . \DIRECTORY_SEPARATOR . $homepageSlide->image);
			}

			$homepageSlide->update(['image' => null]);
			$this->redirect('this');
		};

		$imagePickerMobile = $form->addImagePicker('imageMobile', 'Obrázek (mobil) *', [
			HomepageSlide::IMAGE_DIR . \DIRECTORY_SEPARATOR . 'mobile' => static function (Image $image): void {
				$image->resize(700, 700, Image::EXACT);
			},
		])->setHtmlAttribute('data-info', 'Nahrávejte obrázky o minimální velikosti 700x700 px')
			->addRule([$this, 'validateSliderImageMobile'], 'Obrázek je příliš malý!', [$form]);

		$imagePickerMobile->onDelete[] = function (array $directories, $filename) use ($homepageSlide, $imageDir) {
			if ($homepageSlide->imageMobile) {
				FileSystem::delete($imageDir . \DIRECTORY_SEPARATOR . 'mobile' . \DIRECTORY_SEPARATOR . $homepageSlide->imageMobile);
			}

			$homepageSlide->update(['imageMobile' => null]);
			$this->redirect('this');
		};

		$videoUploader = $form->addFilePicker('video', 'Vybrat video', HomepageSlide::IMAGE_DIR . \DIRECTORY_SEPARATOR . 'video')
			->addRule($form::MIME_TYPE, 'Soubor musí být video!', 'video/*')
			->addRule($form::MAX_FILE_SIZE, 'Soubor je příliš velký!', 1024 * 1024 * 256);

		if ($homepageSlide && $homepageSlide->type == 'video' && $homepageSlide->image) {
			$videoUploader->setHtmlAttribute('data-info', 'Nahráním se přepíše aktuálně nahrané video!');
		}
		
		$form->addText('priority', 'Priorita')->addRule($form::INTEGER)->setRequired()->setDefaultValue(10);
		$form->addCheckbox('hidden', 'Skryto');
		$form->addCheckbox('animate', 'Animovat')->setHtmlAttribute('data-info','Slider bude mít efekt přibližování.');

		$form->addSubmits(!$this->getParameter('slide'));
		
		
		$form->onSuccess[] = function (AdminForm $form) use ($homepageSlide, $imageDir) {
			$values = $form->getValues('array');
		;
			$this->createImageDirs(HomepageSlide::IMAGE_DIR);
			
			if ($homepageSlide && $homepageSlide->type !== $values['type']) {
				$this->deleteImages($homepageSlide);
			}
			
			if (!$values['uuid']) {
				$values['uuid'] = DIConnection::generateUuid();
			}
			
			if ($values['type'] == 'video') {
				$values['image'] = $form['video']->upload($values['uuid'] . '.%2$s');
				unset($values['imageMobile'], $values['video']);
			} else {
				$values['image'] = $form['image']->upload($values['uuid'] . '.%2$s');
				$values['imageMobile'] = $form['imageMobile']->upload($values['uuid'] . '.%2$s');
				unset($values['video']);
			}

			$homepageSlide = $this->slideRepo->syncOne($values);

			$this->flashMessage('Uloženo', 'success');
			$form->processRedirect('detail', 'default', [$homepageSlide]);
		};

		return $form;
	}

	public function createComponentGrid()
	{
		$grid = $this->gridFactory->create($this->slideRepo->many(), 20, 'priority', 'ASC', true);
		$grid->addColumnSelector();
		$grid->addColumnImage('image' , HomepageSlide::IMAGE_DIR, 'desktop', 'Desktop')->onRenderCell[] = function (Html $td, HomepageSlide $slide) {
			$td->setHtml($slide->type == 'image' ? $td->getHtml() : '');
		};
		$grid->addColumnImage('imageMobile' , HomepageSlide::IMAGE_DIR, 'mobile', 'Mobil')->onRenderCell[] = function (Html $td, HomepageSlide $slide) {
			$td->setHtml($slide->type == 'image' ? $td->getHtml() : '');
		};
		$grid->addColumn('Typ', function (HomepageSlide $slide) {
			return $slide->type == 'image' ? 'Obrázek' : 'Video';
		}, '%s', null, ['class' => 'fit']);
		$grid->addColumnText('Popisek', 'text|striptags', '%s');
		$grid->addColumnInputInteger('Priorita', 'priority', '', '', 'priority', [], true);
		$grid->addColumnInputCheckbox('<i title="Skryto" class="far fa-eye-slash"></i>', 'hidden', '', '', 'hidden');


		$grid->addColumnLinkDetail();
		$grid->addColumnActionDelete([$this, 'deleteImages']);

		$grid->addFilterTextInput('search', ['text'], null, 'Popisek');
		$grid->addFilterButtons();

		$grid->addButtonSaveAll();
		$grid->addButtonDeleteSelected([$this, 'deleteImages']);

		return $grid;
	}
	
	public static function validateSliderImage(IControl $control, array $args): bool
	{
		[$form] = $args;
		
		if ($form['type']->getValue() == 'image') {
			/** @var FileUpload $uploaderDesktop */
			$uploaderDesktop = $control->getValue();
			
			if ($uploaderDesktop->isOk()) {
				[$width, $height] = $uploaderDesktop->getImageSize();
				
				if ($width < 820 || $height < 410) {
					return false;
				}
			}
		}
		
		return true;
	}
	
	public static function validateSliderImageMobile(IControl $control, array $args): bool
	{
		[$form] = $args;
		
		if ($form['type']->getValue() == 'image') {
			/** @var FileUpload $uploaderMobile */
			$uploaderMobile = $control->getValue();
			
			if ($uploaderMobile->isOk()) {
				[$width, $height] = $uploaderMobile->getImageSize();
				
				if ($width < 700 || $height < 700) {
					return false;
				}
			}
		}
		
		return true;
	}
	
	public function deleteImages(HomepageSlide $slide)
	{
		$subDirs = $slide->type === 'image' ? ['desktop' => 'image', 'mobile' => 'imageMobile'] : ['video' => 'image'];
		$dir = HomepageSlide::IMAGE_DIR;
		
		foreach ($subDirs as $subDir => $property) {
			if (!$slide->$property) {
				continue;
			}

			$rootDir = $this->wwwDir . \DIRECTORY_SEPARATOR . 'userfiles' . \DIRECTORY_SEPARATOR . $dir;
			FileSystem::delete($rootDir . \DIRECTORY_SEPARATOR . $subDir . \DIRECTORY_SEPARATOR . $slide->$property);
		}
	}
	
	protected function createImageDirs(string $dir)
	{
		$subDirs = ['desktop', 'mobile', 'video'];
		$rootDir = $this->wwwDir . \DIRECTORY_SEPARATOR . 'userfiles' . \DIRECTORY_SEPARATOR . $dir;
		FileSystem::createDir($rootDir);
		
		foreach ($subDirs as $subDir) {
			FileSystem::createDir($rootDir . \DIRECTORY_SEPARATOR . $subDir);
		}
	}
}