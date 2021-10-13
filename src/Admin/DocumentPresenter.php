<?php

namespace Web\Admin;

use Admin\BackendPresenter;
use Admin\Controls\AdminForm;
use Admin\Controls\AdminGrid;
use Web\DB\Document;
use Web\DB\DocumentRepository;

class DocumentPresenter extends BackendPresenter
{
	public string $tDocuments;
	
	/** @inject */
	public DocumentRepository $documentRepository;
	
	public function beforeRender(): void
	{
		parent::beforeRender();
		
		$this->tDocuments = $this->_('documents', 'Dokumenty');
	}
	
	public function renderDefault(): void
	{
		$this->template->headerLabel = $this->tDocuments;
		$this->template->headerTree = [
			[$this->tDocuments],
		];
		$this->template->displayButtons = [$this->createNewItemButton('new')];
		$this->template->displayControls = [$this->getComponent('grid')];
	}
	
	public function renderNew(): void
	{
		$tNew = $this->_('newDocument', 'Nový dokument');
		$this->template->headerLabel = $tNew;
		$this->template->headerTree = [
			[$this->tDocuments, 'default'],
			[$tNew],
		];
		$this->template->displayButtons = [$this->createBackButton('default')];
		$this->template->displayControls = [$this->getComponent('form')];
	}
	
	public function renderDetail(): void
	{
		$tDetail = $this->_('detailDocument', 'Detail dokumentu');
		$this->template->headerLabel = $tDetail;
		$this->template->headerTree = [
			[$this->tDocuments, 'default'],
			[$tDetail],
		];
		$this->template->displayButtons = [$this->createBackButton('default')];
		$this->template->displayControls = [$this->getComponent('form')];
	}
	
	public function actionDetail(Document $document): void
	{
		/** @var \Admin\Controls\AdminForm $form */
		$form = $this->getComponent('form');
		$form->setDefaults($document->toArray());
	}
	
	public function createComponentForm(): AdminForm
	{
		$form = $this->formFactory->create(true, true, true);
		$form->addLocaleText('name', $this->_('name', 'Název'))->setRequired();
		$form->addLocaleUpload('filename', $this->_('document', 'Dokument'));
		$form->addInteger('priority', $this->_('.priority', 'Pořadí'))->setRequired()->setDefaultValue(10);
		$form->addCheckbox('hidden', $this->_('.hidden', 'Skryto'));
		
		/** @var \Web\DB\Document $document */
		$document = $this->getParameter('document');
		
		$form->addSubmits(!$document);
		
		$form->onSuccess[] = function (AdminForm $form) use ($document): void {
			$values = $form->getValues('array');
			$this->generateDirectories([Document::FILE_DIR], $this->langs);
			/** @var \Nette\Http\FileUpload $files[] */
			$files = $form->getValues()->filename;
			
			/** @var \Nette\Http\FileUpload $file */
			foreach ($files as $lang => $file) {
				if ($file->isOk()) {
					$values['fileSize'][$lang] = $file->getSize();
					$fileName = $file->getSanitizedName();
					$file->move($this->wwwDir . '/userfiles/' . Document::FILE_DIR . '/' . $lang . '/' . $fileName);
					$values['filename'][$lang] = $fileName;
				} else {
					unset($values['filename'][$lang]);
				}
			}
			
			$document = $this->documentRepository->syncOne($values, null, true);
			
			$this->flashMessage($this->_('.saved', 'Uloženo'), 'success');
			$form->processRedirect('detail', 'default', [$document]);
		};
		
		return $form;
	}
	
	public function createComponentGrid(): AdminGrid
	{
		$grid = $this->gridFactory->create($this->documentRepository->many());
		$grid->addColumnSelector();
		$grid->addColumnText($this->_('name', 'Název'), 'name', '%s', 'name');
		$grid->addColumnHidden();
		$grid->addColumnPriority();
		$grid->addColumnLinkDetail();
		$grid->addColumnActionDelete();
		$grid->addButtonSaveAll();
		$grid->addButtonDeleteSelected();
		
		return $grid;
	}
}
