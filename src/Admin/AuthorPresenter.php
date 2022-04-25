<?php

declare(strict_types=1);

namespace Web\Admin;

use Admin\BackendPresenter;
use Admin\Controls\AdminForm;
use Admin\Controls\AdminGrid;
use Nette\Utils\Image;
use Pages\DB\PageRepository;
use Pages\Helpers;
use StORM\DIConnection;
use Web\DB\Author;
use Web\DB\AuthorRepository;

class AuthorPresenter extends BackendPresenter
{
	public const TABS = [
		'authors' => 'Autoři',
	];
	
	public const TYPES = [
		'authors' => 'Autoři',
	];
	
	protected const CONFIGURATIONS = [
		'richSnippet' => false,
	];
	
	/** @persistent */
	public string $tab = 'authors';
	
	/** @inject */
	public PageRepository $pageRepository;
	
	/** @inject */
	public AuthorRepository $authorRepository;
	
	public function createComponentAuthorForm(): AdminForm
	{
		$author = $this->getParameter('author');
		
		$form = $this->formFactory->create(true);
		
		$nameInput = $form->addLocaleText('name', 'Jméno a příjmení / Název');
		$form->addLocaleText('position', 'Pozice');
		$form->addLocaleRichEdit('text', 'Bio');
		$form->addText('linkedInUrl', 'LinkedIn profil');
		
		$imagePicker = $form->addImagePicker('image', $this->_('picture', 'Fotka osoby'), [
			Author::IMAGE_DIR => static function (Image $image): void {
				$image->resize(Author::MIN_WIDTH, Author::MIN_HEIGHT, Image::EXACT);
			}], 'Obrázky vkládejte o velikosti %dx%d px', [Author::MIN_WIDTH, Author::MIN_HEIGHT]);
		
		if ($author) {
			$imagePicker->onDelete[] = function () use ($author): void {
				$author->update(['image' => null]);
				$this->redirect('this');
			};
		}
		
		$form->addPageContainer('author', ['author' => $author], $nameInput);
		
		$form->addSubmits(!$author);
		
		$form->onSuccess[] = function (AdminForm $form): void {
			$values = $form->getValues('array');
			
			if (!$values['uuid']) {
				$values['uuid'] = DIConnection::generateUuid();
			}
			
			$this->generateDirectories([Author::IMAGE_DIR]);
			
			$values['image'] = $form['image']->upload($values['uuid'] . '.%2$s');
			
			$author = $this->authorRepository->syncOne($values, null, true);
			
			$form->syncPages(function () use ($author, $values): void {
				$values['page']['params'] = Helpers::serializeParameters(['author' => $author->getPK()]);
				$this->pageRepository->syncOne($values['page']);
			});
			
			$this->flashMessage('Uloženo', 'success');
			$form->processRedirect('detailAuthor', 'default', [$author]);
		};
		
		return $form;
	}
	
	public function createComponentAuthorsGrid(): AdminGrid
	{
		$grid = $this->gridFactory->create($this->authorRepository->many(), 20, 'priority', 'ASC', true);
		$grid->addColumnSelector();
		
		$grid->addColumnImage('image', Author::IMAGE_DIR, '');
		$grid->addColumnText('Název', 'name', '%s', 'name');
		$grid->addColumnText('Pozice', 'position', '%s', 'position');
		
		$grid->addColumnLinkDetail('detailAuthor');
		$grid->addColumnActionDelete();
		$grid->addButtonSaveAll();
		
		$grid->addFilterTextInput('search', ['name_cs'], null, 'Název');
		$grid->addFilterButtons();
		
		return $grid;
	}
	
	public function renderDefault(): void
	{
		$this->template->headerLabel = 'Autoři';
		$this->template->headerTree = [
			['Autoři'],
		];
		
		if ($this->tab === 'authors') {
			$this->template->displayButtons = [$this->createNewItemButton('newAuthor')];
			$this->template->displayControls = [$this->getComponent('authorsGrid')];
		}
		
		$this->template->tabs = self::TABS;
	}
	
	public function renderNewAuthor(): void
	{
		$this->template->headerLabel = 'Nová položka';
		$this->template->headerTree = [
			['Články', 'default'],
			['Autoři', 'default'],
			['Nová položka'],
		];
		$this->template->displayButtons = [$this->createBackButton('default')];
		$this->template->displayControls = [$this->getComponent('authorForm')];
	}
	
	public function renderDetailAuthor(): void
	{
		$this->template->headerLabel = 'Detail';
		$this->template->headerTree = [
			['Články', 'default'],
			['Autoři', 'default'],
			['Detail'],
		];
		$this->template->displayButtons = [$this->createBackButton('default')];
		$this->template->displayControls = [$this->getComponent('authorForm')];
	}
	
	public function actionDetailAuthor(\Web\DB\Author $author): void
	{
		/** @var \Forms\Form $form */
		$form = $this->getComponent('authorForm');
		$form->setDefaults($author->toArray());
	}
}
