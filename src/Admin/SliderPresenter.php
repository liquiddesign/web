<?php

declare(strict_types=1);

namespace Web\Admin;

use Admin\BackendPresenter;
use Admin\Controls\AdminForm;
use Admin\Controls\AdminGrid;
use Nette\Forms\Control;
use Nette\Http\Request;
use Nette\Utils\FileSystem;
use Nette\Utils\Html;
use Nette\Utils\Image;
use StORM\DIConnection;
use Web\DB\HomepageSlide;
use Web\DB\HomepageSlideRepository;

class SliderPresenter extends BackendPresenter
{
	public const DESKTOP_MIN_WIDTH = 820;
	public const DESKTOP_MIN_HEIGHT = 410;
	public const MOBILE_MIN_WIDTH = 700;
	public const MOBILE_MIN_HEIGHT = 700;

	/**
	 * @inject
	 */
	public HomepageSlideRepository $slideRepo;

	/**
	 * @inject
	 */
	public Request $request;

	public function renderDefault(): void
	{
		$this->template->headerLabel = 'Slider na úvodu';
		$this->template->headerTree = [
			['Slider na úvodu'],
		];
		$this->template->displayButtons = [$this->createNewItemButton('new')];
		$this->template->displayControls = [$this->getComponent('grid')];
	}

	public function renderNew(): void
	{
		$this->template->headerLabel = 'Nový slider';
		$this->template->headerTree = [
			['Slider na úvodu', 'default'],
			['Nový'],
		];
		$this->template->displayButtons = [$this->createBackButton('default')];
		$this->template->displayControls = [$this->getComponent('form')];
	}

	public function renderDetail(): void
	{
		$this->template->headerLabel = 'Detail slideru';
		$this->template->headerTree = [
			['Slider', 'default'],
			['Detail'],
		];
		$this->template->displayButtons = [$this->createBackButton('default')];
		$this->template->displayControls = [$this->getComponent('form')];
	}

	public function actionDetail(HomepageSlide $slide): void
	{
		/** @var \Admin\Controls\AdminForm $form */
		$form = $this->getComponent('form');
		$form->setDefaults($slide->toArray());
	}

	public function createComponentForm(): AdminForm
	{
		$presenter = $this;

		$form = $this->formFactory->create(true);
		$imageDir = $this->wwwDir . \DIRECTORY_SEPARATOR . 'userfiles' . \DIRECTORY_SEPARATOR . HomepageSlide::IMAGE_DIR;

		/** @var \Web\DB\HomepageSlide $homepageSlide */
		$homepageSlide = $this->getParameter('slide');

		$form->addLocaleRichEdit('text', 'Popisek');
		$form->addRadioList('type', 'Typ', ['image' => 'Obrázek', 'video' => 'Video'])->setDefaultValue('image');
		
		$imagePickerDesktop = $form->addImagePicker('image', 'Obrázek (desktop) *', [
			HomepageSlide::IMAGE_DIR . \DIRECTORY_SEPARATOR . 'desktop' => static function (Image $image) use ($presenter): void {
				$image->resize($presenter::DESKTOP_MIN_WIDTH, $presenter::DESKTOP_MIN_HEIGHT, Image::FIT);
			},
		])->setHtmlAttribute('data-info', 'Nahrávejte obrázky o minimální velikosti ' . $this::DESKTOP_MIN_WIDTH . 'x' . $this::DESKTOP_MIN_HEIGHT . ' px')
			->addRule([$this, 'validateSliderImage'], 'Obrázek je příliš malý!', [$form, $this::DESKTOP_MIN_WIDTH, $this::DESKTOP_MIN_HEIGHT]);

		$imagePickerDesktop->onDelete[] = function (array $directories, $filename) use ($homepageSlide, $imageDir): void {
			if ($homepageSlide->image) {
				FileSystem::delete($imageDir . \DIRECTORY_SEPARATOR . 'desktop' . \DIRECTORY_SEPARATOR . $homepageSlide->image);
			}

			$homepageSlide->update(['image' => null]);
			$this->redirect('this');
		};

		$imagePickerMobile = $form->addImagePicker('imageMobile', 'Obrázek (mobil) *', [
			HomepageSlide::IMAGE_DIR . \DIRECTORY_SEPARATOR . 'mobile' => static function (Image $image) use ($presenter): void {
				$image->resize($presenter::MOBILE_MIN_WIDTH, $presenter::MOBILE_MIN_HEIGHT, Image::FIT);
			},
		])->setHtmlAttribute('data-info', 'Nahrávejte obrázky o minimální velikosti ' . $this::MOBILE_MIN_WIDTH . 'x' . $this::MOBILE_MIN_HEIGHT . ' px')
			->addRule([$this, 'validateSliderImageMobile'], 'Obrázek je příliš malý!', [$form, $this::MOBILE_MIN_WIDTH, $this::MOBILE_MIN_HEIGHT]);

		$imagePickerMobile->onDelete[] = function (array $directories, $filename) use ($homepageSlide, $imageDir): void {
			if ($homepageSlide->imageMobile) {
				FileSystem::delete($imageDir . \DIRECTORY_SEPARATOR . 'mobile' . \DIRECTORY_SEPARATOR . $homepageSlide->imageMobile);
			}

			$homepageSlide->update(['imageMobile' => null]);
			$this->redirect('this');
		};

		$videoUploader = $form->addFilePicker('video', 'Vybrat video', HomepageSlide::IMAGE_DIR . \DIRECTORY_SEPARATOR . 'video')
			->addRule($form::MIME_TYPE, 'Soubor musí být video!', 'video/*');

		if ($homepageSlide && $homepageSlide->type === 'video' && $homepageSlide->image) {
			$videoUploader->setHtmlAttribute('data-info', 'Nahráním se přepíše aktuálně nahrané video!');
		}

		$form->addLocaleText('url', 'URL');
		
		$form->addText('priority', 'Priorita')->addRule($form::INTEGER)->setRequired()->setDefaultValue(10);
		$form->addCheckbox('hidden', 'Skryto');
		$form->addCheckbox('animate', 'Animovat')->setHtmlAttribute('data-info', 'Slider bude mít efekt přibližování.');

		$this->formFactory->addShopsContainerToAdminForm($form, false);

		$form->addSubmits(!$this->getParameter('slide'));
		
		$form->onSuccess[] = function (AdminForm $form) use ($homepageSlide): void {
			$values = $form->getValues('array');
			$this->createImageDirs(HomepageSlide::IMAGE_DIR);
			
			if ($homepageSlide && $homepageSlide->type !== $values['type']) {
				$this->deleteImages($homepageSlide);
			}
			
			if (!$values['uuid']) {
				$values['uuid'] = DIConnection::generateUuid();
			}
			
			if ($values['type'] === 'video') {
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

	public function createComponentGrid(): AdminGrid
	{
		$grid = $this->gridFactory->create($this->slideRepo->many(), 20, 'priority', 'ASC', true);
		$grid->addColumnSelector();
		$grid->addColumnImage('image', HomepageSlide::IMAGE_DIR, 'desktop', 'Desktop')->onRenderCell[] = function (Html $td, HomepageSlide $slide): void {
			$td->setHtml($slide->type === 'image' ? $td->getHtml() : '');
		};
		$grid->addColumnImage('imageMobile', HomepageSlide::IMAGE_DIR, 'mobile', 'Mobil')->onRenderCell[] = function (Html $td, HomepageSlide $slide): void {
			$td->setHtml($slide->type === 'image' ? $td->getHtml() : '');
		};
		$grid->addColumn('Typ', function (HomepageSlide $slide) {
			return $slide->type === 'image' ? 'Obrázek' : 'Video';
		}, '%s', null, ['class' => 'fit']);
		$grid->addColumnText('Popisek', 'text|striptags', '%s');
		$grid->addColumnInputInteger('Priorita', 'priority', '', '', 'priority', [], true);
		$grid->addColumnInputCheckbox('<i title="Skryto" class="far fa-eye-slash"></i>', 'hidden', '', '', 'hidden');


		$grid->addColumnLinkDetail();
		$grid->addColumnActionDelete([$this, 'deleteImages']);

		$grid->addFilterTextInput('search', ['this.text_cs'], null, 'Popisek');
		$grid->addFilterButtons();

		$grid->addButtonSaveAll();
		$grid->addButtonDeleteSelected([$this, 'deleteImages']);

		return $grid;
	}
	
	public function deleteImages(HomepageSlide $slide): void
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

	public static function validateSliderImage(Control $control, array $args): bool
	{
		[$form, $minWidth, $minHeight] = $args;
		
		if ($form['type']->getValue() === 'image') {
			/** @var \Nette\Http\FileUpload $uploaderDesktop */
			$uploaderDesktop = $control->getValue();
			
			if ($uploaderDesktop->isOk()) {
				[$width, $height] = $uploaderDesktop->getImageSize();
				
				if ($width < $minWidth || $height < $minHeight) {
					return false;
				}
			}
		}
		
		return true;
	}
	
	public static function validateSliderImageMobile(Control $control, array $args): bool
	{
		[$form, $minWidth, $minHeight] = $args;
		
		if ($form['type']->getValue() === 'image') {
			/** @var \Nette\Http\FileUpload $uploaderMobile */
			$uploaderMobile = $control->getValue();
			
			if ($uploaderMobile->isOk()) {
				[$width, $height] = $uploaderMobile->getImageSize();
				
				if ($width < $minWidth || $height < $minHeight) {
					return false;
				}
			}
		}
		
		return true;
	}
	
	protected function createImageDirs(string $dir): void
	{
		$subDirs = ['desktop', 'mobile', 'video'];
		$rootDir = $this->wwwDir . \DIRECTORY_SEPARATOR . 'userfiles' . \DIRECTORY_SEPARATOR . $dir;
		FileSystem::createDir($rootDir);
		
		foreach ($subDirs as $subDir) {
			FileSystem::createDir($rootDir . \DIRECTORY_SEPARATOR . $subDir);
		}
	}
}
