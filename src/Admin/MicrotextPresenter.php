<?php

declare(strict_types=1);

namespace Web\Admin;

use Admin\BackendPresenter;
use App\Admin\Controls\AdminForm;
use App\Admin\PresenterTrait;
use Forms\Form;
use League\Csv\Reader;
use League\Csv\Writer;
use Nette\Application\Responses\FileResponse;
use Nette\Utils\DateTime;
use Nette\Utils\FileSystem;
use StORM\DIConnection;
use Translator\DB\Translation;
use Translator\DB\TranslationRepository;

class MicrotextPresenter extends BackendPresenter
{
	/** @inject */
	public TranslationRepository $translationRepository;

	/** @inject */
	public DIConnection $storm;

	public function createComponentGrid()
	{
		$grid = $this->gridFactory->create($this->translationRepository->many(), 20, 'name', 'ASC', true);
		$grid->addColumnSelector();

		$grid->addColumnText('ID', 'uuid', '%s', 'uuid', ['class' => 'fit']);
		$grid->addColumnText('Popisek', 'label', '%s', 'label');

		foreach ($this->formFactory->getDefaultMutations() as $mutation) {
			$img = $this->createFlag($mutation);

			$suffix = $this->storm->getAvailableMutations()[$mutation];
			$grid->addColumnInputText($img . " Překlad", "text$suffix", '', '', null, ['style' => 'min-width: 240px;']);
		}

		$grid->addColumnMutations('text');

		$grid->addColumnLinkDetail('detail');

		$grid->addButtonSaveAll();

		$grid->addFilterTextInput('search', ['uuid', 'label', 'text_cs'], null, 'ID, popisek, překlad');
		$grid->addFilterButtons();

		return $grid;
	}

	public function createComponentForm(): Form
	{
		$form = $this->formFactory->create();

		$form->addText('label', 'Popisek')->setDisabled(true);
		$form->addLocaleText('text', 'Překlad');

		$form->addSubmits();

		$form->onSuccess[] = function (AdminForm $form) {
			$values = $form->getValues('array');

			if (!$values['uuid']) {
				$values['uuid'] = DIConnection::generateUuid();
			}

			$translation = $this->translationRepository->syncOne($values);

			$this->flashMessage('Uloženo', 'success');
			$form->processRedirect('detail', 'default', [$translation]);
		};

		return $form;
	}

	public function renderDefault()
	{
		$this->template->headerLabel = 'Mikrotexty';
		$this->template->headerTree = [
			['Mikrotexty'],
		];

		$this->template->displayButtons = [
			$this->createButtonWithClass('import', '<i class="fas fa-arrow-alt-circle-down"></i> Import textů', 'btn btn-outline-primary btn-sm'),
			$this->createButtonWithClass('export', '<i class="fas fa-arrow-alt-circle-up"></i> Export textů', 'btn btn-outline-primary btn-sm'),
			$this->createButtonWithClass('restoreLastImport!', '<i class="fas fa-sync"></i> Obnovit poslední zálohu', 'btn btn-outline-primary btn-sm')
		];
		$this->template->displayControls = [$this->getComponent('grid')];
	}

	public function renderDetail()
	{
		$this->template->headerLabel = 'Detail textu';
		$this->template->headerTree = [
			['Mikrotexty', 'default'],
			['Detail'],
		];
		$this->template->displayButtons = [$this->createBackButton('default')];
		$this->template->displayControls = [$this->getComponent('form')];
	}

	public function actionDetail(Translation $translation)
	{
		/** @var Form $form */
		$form = $this->getComponent('form');

		$form->setDefaults($translation->toArray());
	}

	public function renderExport(): void
	{
		$this->template->headerLabel = 'Export textů';
		$this->template->headerTree = [
			['Mikrotexty', 'default'],
			['Export'],
		];
		$this->template->displayButtons = [$this->createBackButton('default')];
		$this->template->displayControls = [$this->getComponent('exportForm')];
	}

	public function renderImport(): void
	{
		$this->template->headerLabel = 'Import textů';
		$this->template->headerTree = [
			['Mikrotexty', 'default'],
			['Import'],
		];
		$this->template->displayButtons = [$this->createBackButton('default')];
		$this->template->displayControls = [$this->getComponent('importForm')];
	}

	public function handleRestoreLastImport(): void
	{
		$backupDir = $this->context->getParameters()['tempDir'] . \DIRECTORY_SEPARATOR . 'translations_backup';

		if (!\is_dir($backupDir)) {
			$this->flashMessage('Nejsou k dispozici žádné zálohy!', 'warning');
			$this->redirect('this');
		}

		$files = \scandir($backupDir, SCANDIR_SORT_DESCENDING);

		if (!$files || \count($files) == 0) {
			$this->flashMessage('Obnova poslední zálohy se nezdařila! ', 'error');
			$this->redirect('this');
		}

		try {
			$this->translationRepository->many()->delete();
			$this->translationRepository->importTranslationsFromFile($backupDir . \DIRECTORY_SEPARATOR . $files[0], \array_keys($this->storm->getAvailableMutations()));
		} catch (\Exception $e) {
			$this->flashMessage('Obnova poslední zálohy se nezdařila! ', 'error');
			$this->redirect('this');
		}

		$this->flashMessage('Obnova poslední zálohy byla úspěšně provedena.', 'success');
		$this->redirect('this');
	}

	public function createComponentExportForm(): Form
	{
		$form = $this->formFactory->create();

		$mutations = $this->storm->getAvailableMutations();
		$mutationsForSelect = \array_combine(\array_keys($mutations), \array_keys($mutations));

//		$form->addDataSelect('referenceMutation', 'Jazyk návěští', $mutationsForSelect);
		$form->addDataMultiSelect('exportMutations', 'Jazyky k exportu', $mutationsForSelect);

		$form->addSubmit('submit', 'Exportovat');

		$form->onSubmit[] = function (Form $form) {
			$dir = $this->context->getParameters()['tempDir'] . \DIRECTORY_SEPARATOR . 'export';
			FileSystem::createDir($dir);
			$tempFilename = \tempnam($dir, 'csv');

			$this->context->getService('application')->onShutdown[] = function () use ($tempFilename) {
				\unlink($tempFilename);
			};

			try {
				$this->translationRepository->exportTranslationsCsv($tempFilename, $form->getValues('array')['exportMutations']);
			} catch (\Exception $e) {
				$this->flashMessage('Export se nezdařil!', 'error');
				$this->redirect('this');
			}

			$response = new FileResponse($tempFilename, "texty.csv", 'text/csv');

			$this->flashMessage('Export proběhl úspěšně.', 'success');
			$this->sendResponse($response);
		};

		return $form;
	}

	public function createComponentImportForm(): Form
	{
		$form = $this->formFactory->create();

		$form->addFilePicker('csvFile', 'CSV soubor')->setRequired();
		$form->addSubmit('submit', 'Importovat');

		$form->onSubmit[] = function (Form $form) {
			$backupDir = $this->context->getParameters()['tempDir'] . \DIRECTORY_SEPARATOR . 'translations_backup';

			$this->translationRepository->createTranslationsSnapshot($backupDir, \array_keys($this->storm->getAvailableMutations()));

			$values = $form->getValues('array');

			/** @var \Nette\Http\FileUpload $csvFile */
			$csvFile = $values['csvFile'];
			$content = $csvFile->getContents();

			if (!$content) {
				$this->flashMessage('Špatný formát souboru!', 'error');
				$this->redirect('this');
			}

			try {
				$this->translationRepository->importTranslationsFromString($content, \array_keys($this->storm->getAvailableMutations()));
			} catch (\Exception $e) {
				$this->flashMessage('Chyba při provádění importu!', 'error');
				$this->redirect('this');
			}

			$this->flashMessage('Import proběhl úspěšně.', 'success');
			$this->redirect('default');
		};

		return $form;
	}
}