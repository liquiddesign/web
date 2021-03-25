<?php

declare(strict_types=1);

namespace Web\Admin;

use Admin\BackendPresenter;
use Admin\Controls\AdminForm;
use Web\DB\News;
use Web\DB\NewsRepository;
use Web\DB\TagRepository;
use DateTime;
use Web\DB\Tag;
use Forms\Form;
use Nette\Utils\Image;
use Pages\DB\PageRepository;
use Pages\Helpers;
use StORM\DIConnection;

class NewsPresenter extends BackendPresenter
{
	public const TABS = [
		'news' => 'Články',
		'tags' => 'Tagy'
	];
	
	public const TYPES = [
		'news' => 'Články',
	];
	
	/** @persistent */
	public string $tab = 'news';
	
	/** @inject */
	public NewsRepository $newsRepository;
	
	/** @inject */
	public PageRepository $pageRepository;
	
	/** @inject */
	public TagRepository $tagRepository;
	
	public function createComponentGrid()
	{
		$grid = $this->gridFactory->create($this->newsRepository->many()->where('type', 'news'), 20, 'published', 'DESC');
		$grid->addColumnSelector();
		$grid->addColumnImage('imageFileName', News::IMAGE_DIR);
		
		$grid->addColumnText('Publikováno', "published|date:'d.m.Y'", '%s', null, ['class' => 'minimal']);
		
		$grid->addColumn('Název', function (News $news, $grid) {
			return [$grid->getPresenter()->link(':Web:Article:detail', ['article' => (string)$news]), $news->name];
		}, '<a href="%s" target="_blank"> %s</a>', 'name');
		
		$grid->addColumnInputCheckbox('<i title="Skryto" class="far fa-eye-slash"></i>', 'hidden', '', '', 'hidden');
		$grid->addColumnInputCheckbox('<i title="Doporučeno" class="far fa-thumbs-up"></i>', 'recommended', '', '', 'recommended');
		$grid->addColumnLinkDetail('Detail');
		$grid->addColumnActionDelete();
		
		$grid->addButtonSaveAll();
		$grid->addButtonDeleteSelected();
		
		$grid->addFilterTextInput('search', ['name_cs'], null, 'Název');
		
		$grid->addFilterButtons();
		
		$grid->onDelete[] = [$this, 'onDelete'];
		
		return $grid;
	}
	
	public function createComponentWebTagGrid()
	{
		$grid = $this->gridFactory->create($this->tagRepository->many(), 20, 'priority', 'ASC', true);
		$grid->addColumnSelector();
		
		
		$grid->addColumnText('Název', 'name', '%s', 'name');
		
		$grid->addColumnInputInteger('Priorita', 'priority', '', '', 'priority', [], true);
		$grid->addColumnInputCheckbox('<i title="Doporučeno" class="far fa-thumbs-up"></i>', 'recommended', '', '', 'recommended');
		$grid->addColumnInputCheckbox('<i title="Skryto" class="far fa-eye-slash"></i>', 'hidden', '', '', 'hidden');
		
		$grid->addColumnLinkDetail('detailTag');
		$grid->addColumnActionDeleteSystemic();
		
		$grid->addButtonSaveAll();
		$grid->addButtonDeleteSelected(null, false, function (\Web\DB\Tag $page) {
			return $page->isSystemic();
		});
		
		$grid->addFilterTextInput('search', ['name_cs'], null, 'Název');
		$grid->addFilterButtons();
		
		return $grid;
	}
	
	
	public function createComponentNewForm(): Form
	{
		$form = $this->formFactory->create();
		
		$nameInput = $form->addLocaleText('name', 'Název');
		$form->addLocaleTextArea('perex', 'Perex');
		$form->addLocaleRichEdit('content', 'Obsah');
		$imagePicker = $form->addImagePicker('imageFileName', 'Obrázek', [
			News::IMAGE_DIR . \DIRECTORY_SEPARATOR . 'origin' => null,
			News::IMAGE_DIR . \DIRECTORY_SEPARATOR . 'detail' => static function (Image $image): void {
				$image->resize(1920, null);
			},
			News::IMAGE_DIR . \DIRECTORY_SEPARATOR . 'thumb' => static function (Image $image): void {
				$image->resize(400, null);
			},
		]);
		
		/** @var News $news */
		$news = $this->getParameter('news');
		
		$imagePicker->onDelete[] = function () use ($news) {
			$this->onDelete($news);
		};
		
		$form->addDataMultiSelect('tags', 'Tagy', $this->tagRepository->getArrayForSelect());
		
		$form->addDate('published', 'Publikováno')->setRequired();
		
		$form->addCheckbox('hidden', 'Skryto');
		$form->addCheckbox('recommended', 'Doporučeno');
		$form->addHidden('type', 'news');
		
		
		$form->addPageContainer('news_detail', ['article' => $this->getParameter('news')], $nameInput);
		
		$form->addSubmits(!$news);
		
		$form->onSuccess[] = function (AdminForm $form) use ($news) {
			$values = $form->getValues('array');
			
			$this->createImageDirs(News::IMAGE_DIR);
			
			if (!$values['uuid']) {
				$values['uuid'] = DIConnection::generateUuid();
			}
			
			/** @var \Forms\Controls\UploadImage $uploader */
			$uploader = $form['imageFileName'];
			unset($values['imageFileName']);
			
			if ($uploader->isOk() && $uploader->isFilled()) {
				$values['imageFileName'] = $uploader->upload($values['uuid'] . '.%2$s');
			}
			
			/** @var News $news */
			$news = $this->newsRepository->syncOne($values, null, true);
			
			$values['page']['params'] = Helpers::serializeParameters(['article' => $news->getPK()]);
			$this->pageRepository->syncOne($values['page']);
			
			$this->flashMessage('Uloženo', 'success');
			$form->processRedirect('detail', 'default', [$news]);
		};
		
		return $form;
	}
	
	public function createComponentTagForm(): AdminForm
	{
		$tag = $this->getParameter('tag');
		
		$form = $this->formFactory->create();
		
		$nameInput = $form->addLocaleText('name', 'Název')->setDefaults(['cs' => '', 'en' => '']);
		$form->addLocalePerexEdit('perex', 'Perex');
		$form->addLocaleRichEdit('content', 'Obsah');
		$form->addInteger('priority', 'Priorita')->setDefaultValue(10);
		$form->addCheckbox('hidden', 'Skryto');
		$form->addCheckbox('recommended', 'Doporučeno');
		
		$form->addPageContainer('news', ['tag' => $this->getParameter('tag')], $nameInput);
		
		$form->addSubmits(!$tag);
		
		$form->onSuccess[] = function (AdminForm $form) {
			$values = $form->getValues('array');
			
			
			if (!$values['uuid']) {
				$values['uuid'] = DIConnection::generateUuid();
			}
			
			$tag = $this->tagRepository->syncOne($values, null, true);
			
			$values['page']['params'] = Helpers::serializeParameters(['tag' => $tag->getPK()]);
			$this->pageRepository->syncOne($values['page']);
			
			$this->flashMessage('Uloženo', 'success');
			$form->processRedirect('detailTag', 'default', [$tag]);
		};
		
		return $form;
	}
	
	public function renderDefault()
	{
		$this->template->headerLabel = 'Články';
		$this->template->headerTree = [
			['Články'],
		];
		
		if ($this->tab == 'news') {
			$this->template->displayButtons = [$this->createNewItemButton('new')];
			$this->template->displayControls = [$this->getComponent('grid')];
		} elseif ($this->tab == 'tags') {
			$this->template->displayButtons = [$this->createNewItemButton('newTag')];
			$this->template->displayControls = [$this->getComponent('webTagGrid')];
		}
		
		$this->template->tabs = self::TABS;
	}
	
	public function renderNew()
	{
		$this->template->headerLabel = 'Nová položka';
		$this->template->headerTree = [
			['Články', 'default'],
			['Nová položka'],
		];
		$this->template->displayButtons = [$this->createBackButton('default')];
		$this->template->displayControls = [$this->getComponent('newForm')];
	}
	
	public function renderDetail()
	{
		$this->template->headerLabel = 'Detail';
		$this->template->headerTree = [
			['Články', 'default'],
			['Detail'],
		];
		$this->template->displayButtons = [$this->createBackButton('default')];
		$this->template->displayControls = [$this->getComponent('newForm')];
	}
	
	public function actionNew(): void
	{
		/** @var Form $form */
		$form = $this->getComponent('newForm');
		$form->setDefaults([
			'published' => (new DateTime())->format('Y-m-d\TH:i')
		]);
	}
	
	public function actionDetail(News $news)
	{
		/** @var Form $form */
		$form = $this->getComponent('newForm');
		$form->setDefaults($news->toArray(['tags']));
	}
	
	public function renderNewTag()
	{
		$this->template->headerLabel = 'Nová položka';
		$this->template->headerTree = [
			['Články', 'default'],
			['Tagy', 'default'],
			['Nová položka'],
		];
		$this->template->displayButtons = [$this->createBackButton('default')];
		$this->template->displayControls = [$this->getComponent('tagForm')];
	}
	
	public function renderDetailTag()
	{
		$this->template->headerLabel = 'Detail';
		$this->template->headerTree = [
			['Články', 'default'],
			['Tagy', 'default'],
			['Detail'],
		];
		$this->template->displayButtons = [$this->createBackButton('default')];
		$this->template->displayControls = [$this->getComponent('tagForm')];
	}
	
	public function actionNewTag(): void
	{
		/** @var Form $form */
		$form = $this->getComponent('tagForm');
	}
	
	public function actionDetailTag(\Web\DB\Tag $tag)
	{
		/** @var Form $form */
		$form = $this->getComponent('tagForm');
		$form->setDefaults($tag->toArray());
	}
}
