<?php

namespace Web\Admin;

use Admin\BackendPresenter;
use Admin\Controls\AdminForm;
use Admin\Controls\AdminFormFactory;
use Forms\Form;
use Nette\DI\Container;
use Nette\Utils\FileSystem;
use Nette\Utils\Image;
use Nette\Utils\Random;
use Web\DB\SettingRepository;

class MetaPresenter extends BackendPresenter
{
	public const META_IMAGE_DIR = 'meta_images';

	public const SETTING_META_OG_IMAGE = 'meta_ogImageFileName';

	/**
	 * @inject
	 */
	public SettingRepository $settingsRepository;

	/**
	 * @inject
	 */
	public AdminFormFactory $formFactory;

	/**
	 * @inject
	 */
	public Container $container;

	public function createComponentForm(): Form
	{
		$form = $this->formFactory->create(true);

		$values = $this->settingsRepository->many()
			->where('this.name', self::SETTING_META_OG_IMAGE)
			->setIndex('this.name')
			->toArrayOf('value');

		$imagePicker = $form->addImagePicker(self::SETTING_META_OG_IMAGE, 'Výchozí OG Image', [
			self::META_IMAGE_DIR . \DIRECTORY_SEPARATOR . 'origin' => null,
			self::META_IMAGE_DIR . \DIRECTORY_SEPARATOR . 'detail' => function (Image $image): void {
				$image->resize(600, 315);
			},
			self::META_IMAGE_DIR . \DIRECTORY_SEPARATOR . 'thumb' => function (Image $image): void {
				$image->resize(300, 158);
			},
		]);

		$imagePicker->setHtmlAttribute('data-info', 'Vkládejte obrázky o minimálních rozměrech 600 x 315 px.');

		$imagePicker->onDelete[] = function (array $directories, $filename) use ($form): void {
			$this->settingsRepository->many()->where('name', self::SETTING_META_OG_IMAGE)->delete();

			$form->getPresenter()->redirect('this');
		};

		$form->addSubmits(false, false);

		$form->setDefaults($values);

		$form->onSuccess[] = function (AdminForm $form): void {
			$values = $form->getValues('array');

			/** @var \Forms\Controls\UploadImage $upload */
			$upload = $form['meta_ogImageFileName'];

			unset($values['meta_ogImageFileName']);

			if ($upload->isOk() && $upload->isFilled()) {
				$userDir = $form->getUserDir();
				$fileName = \pathinfo($upload->getValue()->getSanitizedName(), \PATHINFO_FILENAME);
				$fileExtension = \strtolower(\pathinfo($upload->getValue()->getSanitizedName(), \PATHINFO_EXTENSION));

				$imageDir = self::META_IMAGE_DIR;

				FileSystem::createDir("$userDir/$imageDir/origin");
				FileSystem::createDir("$userDir/$imageDir/detail");
				FileSystem::createDir("$userDir/$imageDir/thumb");

				while (\is_file("$userDir/$imageDir/origin/$fileName.$fileExtension")) {
					$fileName .= '-' . Random::generate(1, '0-9');
				}

				$imageFileName = $upload->upload($fileName . '.%2$s');

				$this->settingsRepository->syncOne([
					'name' => self::SETTING_META_OG_IMAGE,
					'value' => $imageFileName,
				]);
			}

			$this->flashMessage('Uloženo', 'success');
			$form->processRedirect('default');
		};

		return $form;
	}

	public function renderDefault(): void
	{
		$this->template->headerLabel = 'Nastavení meta';
		$this->template->headerTree = [
			['Nastavení meta'],
		];

		$this->template->displayControls = [$this->getComponent('form')];
	}
}
