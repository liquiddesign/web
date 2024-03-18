<?php

declare(strict_types=1);

namespace Web\Admin;

use Admin\BackendPresenter;
use Admin\Controls\AdminForm;
use Admin\Controls\AdminGrid;
use Forms\Form;
use Nette\Utils\Image;
use Pages\DB\PageRepository;
use Pages\Helpers;
use StORM\DIConnection;
use StORM\Entity;
use Web\DB\AuthorRepository;
use Web\DB\News;
use Web\DB\NewsRepository;
use Web\DB\TagRepository;

class NewsPresenter extends BackendPresenter
{
	public const DETAIL_IMAGE_RESIZE = 1920;
	public const THUMB_IMAGE_RESIZE = 400;
	
	public const TABS = [
		'news' => 'Články',
		'tags' => 'Tagy',
	];
	
	public const TYPES = [
		'news' => 'Články',
	];
	
	protected const CONFIGURATIONS = [
		'richSnippet' => false,
		'relatedNews' => false,
	];
	
	/** @persistent */
	public string $tab = 'news';
	
	/** @inject */
	public NewsRepository $newsRepository;
	
	/** @inject */
	public PageRepository $pageRepository;
	
	/** @inject */
	public TagRepository $tagRepository;
	
	/** @inject */
	public AuthorRepository $authorRepository;
	
	public function createComponentGrid(): AdminGrid
	{
		$grid = $this->gridFactory->create($this->newsRepository->many()->where('type', $this->tab), 20, 'published', 'DESC');
		$grid->addColumnSelector();
		$grid->addColumnImage('imageFileName', News::IMAGE_DIR);
		
		$grid->addColumnText('Publikováno', "published|date:'d.m.Y'", '%s', 'published', ['class' => 'minimal']);
		
		$grid->addColumn('Název', function (News $news, $grid) {
			return [$grid->getPresenter()->link(':Web:Article:detail', ['article' => (string) $news]), $news->name];
		}, '<a href="%s" target="_blank"> %s</a>', 'name');
		
		$grid->addColumn('Tagy', function (News $news) {
			$tags = $this->tagRepository->getCollection(true)
				->join(['nxn' => 'web_news_nxn_web_tag'], 'this.uuid = nxn.fk_tag')
				->where('nxn.fk_news', $news->getPK())
				->toArrayOf('name');
			
			return \implode(', ', \array_values($tags));
		});
		$grid->addColumnInputCheckbox('<i title="Skryto" class="far fa-eye-slash"></i>', 'hidden', '', '', 'hidden');
		$grid->addColumnInputCheckbox('<i title="Doporučeno" class="far fa-thumbs-up"></i>', 'recommended', '', '', 'recommended');
		$grid->addColumnLinkDetail('Detail');
		$grid->addColumnActionDelete();
		
		$grid->addButtonSaveAll();
		$grid->addButtonDeleteSelected();
		$grid->addButtonBulkEdit('newForm', ['tags']);
		
		$grid->addFilterTextInput('search', ['name_cs'], null, 'Název');
		
		$grid->addFilterButtons();
		
		$grid->onDelete[] = [$this, 'onDelete'];
		
		return $grid;
	}
	
	public function createComponentWebTagGrid(): AdminGrid
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
		$grid->addButtonDeleteSelected(null, false, function (\Web\DB\Tag $tag) {
			return $tag->isSystemic();
		}, 'this.uuid');
		
		$grid->addFilterTextInput('search', ['name_cs'], null, 'Název');
		$grid->addFilterButtons();
		
		$grid->onDelete[] = [$this, 'onDeleteTag'];
		
		return $grid;
	}
	
	public function createComponentNewForm(): Form
	{
		$form = $this->formFactory->create(true);
		
		$presenter = $this;
		
		$nameInput = $form->addLocaleText('name', 'Název');
		$form->addLocaleText('metaInfo', 'Dodatečné informace');
		$form->addLocaleTextArea('perex', 'Perex');
		$form->addLocaleRichEdit('content', 'Obsah');
		$imagePicker = $form->addImagePicker('imageFileName', 'Obrázek', [
			News::IMAGE_DIR . \DIRECTORY_SEPARATOR . 'origin' => null,
			News::IMAGE_DIR . \DIRECTORY_SEPARATOR . 'detail' => static function (Image $image) use ($presenter): void {
				$image->resize($presenter::DETAIL_IMAGE_RESIZE, null);
			},
			News::IMAGE_DIR . \DIRECTORY_SEPARATOR . 'thumb' => static function (Image $image) use ($presenter): void {
				$image->resize($presenter::THUMB_IMAGE_RESIZE, null);
			},
		], 'Nahrávejte obrázek o minimální šířce ' . $presenter::DETAIL_IMAGE_RESIZE . 'px');
		
		/** @var \Web\DB\News $news */
		$news = $this->getParameter('news');
		
		$imagePicker->onDelete[] = function () use ($news): void {
			$this->onDeleteImage($news);
		};
		
		$form->addDataMultiSelect('tags', 'Tagy', $this->tagRepository->getArrayForSelect());
		
		if (isset($this::CONFIGURATIONS['relatedNews']) && $this::CONFIGURATIONS['relatedNews']) {
			$form->addDataMultiSelect('relatedNews', 'Podobné články', $this->newsRepository->getArrayForSelect());
		}
		
		$form->addPolyfillDate('published', 'Publikováno')->setRequired();
		$form->addSelect('author', 'Autor')->setItems($this->authorRepository->getArrayForSelect())->setPrompt('- bez autora -');
		
		$form->addCheckbox('hidden', 'Skryto');
		$form->addCheckbox('recommended', 'Doporučeno');
		$form->addHidden('type', $this->tab);
		
		
		$form->addPageContainer(
			'news_detail',
			['article' => $this->getParameter('news')],
			$nameInput,
			false,
			true,
			false,
			'URL a SEO',
			false,
			true,
			isset($this::CONFIGURATIONS['richSnippet']) && $this::CONFIGURATIONS['richSnippet'],
		);

		$this->formFactory->addShopsContainerToAdminForm($form, false);
		
		$form->addSubmits(!$news);
		
		$form->onSuccess[] = function (AdminForm $form) use ($news): void {
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
			
			$values['content'] = \Web\Helpers::sanitizeMutationsStrings($values['content']);
			
			/** @var \Web\DB\News $news */
			$news = $this->newsRepository->syncOne($values, null, true);
			
			$form->syncPages(function () use ($news, $values): void {
				$values['page']['params'] = Helpers::serializeParameters(['article' => $news->getPK()]);
				$this->pageRepository->syncOne($values['page']);
			});
			
			$this->flashMessage('Uloženo', 'success');
			$form->processRedirect('detail', 'default', [$news]);
		};
		
		return $form;
	}
	
	public function createComponentTagForm(): AdminForm
	{
		$tag = $this->getParameter('tag');
		
		$form = $this->formFactory->create(true);
		
		$nameInput = $form->addLocaleText('name', 'Název')->setDefaults(['cs' => '', 'en' => '']);
		$form->addLocalePerexEdit('perex', 'Perex');
		$form->addLocaleRichEdit('content', 'Obsah');
		$form->addInteger('priority', 'Priorita')->setDefaultValue(10);
		$form->addCheckbox('hidden', 'Skryto');
		$form->addCheckbox('recommended', 'Doporučeno');
		
		$form->addPageContainer('news', ['tag' => $this->getParameter('tag')], $nameInput, true);
		
		$form->addSubmits(!$tag);
		
		$form->onSuccess[] = function (AdminForm $form): void {
			$values = $form->getValues('array');
			
			if (!$values['uuid']) {
				$values['uuid'] = DIConnection::generateUuid();
			}
			
			$tag = $this->tagRepository->syncOne($values, null, true);
			
			$form->syncPages(function () use ($tag, $values): void {
				$values['page']['params'] = Helpers::serializeParameters(['tag' => $tag->getPK()]);
				$this->pageRepository->syncOne($values['page']);
			});
			
			$this->flashMessage('Uloženo', 'success');
			$form->processRedirect('detailTag', 'default', [$tag]);
		};
		
		return $form;
	}
	
	public function renderDefault(): void
	{
		$this->template->headerLabel = 'Články';
		$this->template->headerTree = [
			['Články'],
		];
		
		if ($this->tab === 'tags') {
			$this->template->displayButtons = [$this->createNewItemButton('newTag')];
			$this->template->displayControls = [$this->getComponent('webTagGrid')];
		} else {
			$this->template->displayButtons = [$this->createNewItemButton('new')];
			$this->template->displayControls = [$this->getComponent('grid')];
		}
		
		// phpcs:ignore
		$this->template->tabs = static::TABS;
	}
	
	public function renderNew(): void
	{
		$this->template->headerLabel = 'Nová položka';
		$this->template->headerTree = [
			['Články', 'default'],
			['Nová položka'],
		];
		$this->template->displayButtons = [$this->createBackButton('default')];
		$this->template->displayControls = [$this->getComponent('newForm')];
	}
	
	public function renderDetail(): void
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
		/** @var \Forms\Form $form */
		$form = $this->getComponent('newForm');
		$form->setDefaults([
			'published' => (new \Carbon\Carbon())->format('Y-m-d\TH:i'),
		]);
	}
	
	public function actionDetail(News $news): void
	{
		/** @var \Forms\Form $form */
		$form = $this->getComponent('newForm');
		$form->setDefaults($news->toArray(['tags', 'relatedNews']));
	}
	
	public function renderNewTag(): void
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
	
	public function renderDetailTag(): void
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
	
	public function actionDetailTag(\Web\DB\Tag $tag): void
	{
		/** @var \Forms\Form $form */
		$form = $this->getComponent('tagForm');
		$form->setDefaults($tag->toArray());
	}
	
	public function onDelete(Entity $object): void
	{
		$this->onDeleteImage($object);
		
		/** @var \Web\DB\Page|null $page */
		$page = $this->pageRepository->getPageByTypeAndParams('news_detail', null, ['article' => $object->getPK()]);
		
		if (!$page) {
			return;
		}
		
		$page->delete();
	}
	
	public function onDeleteTag(Entity $object): void
	{
		/** @var \Web\DB\Page|null $page */
		$page = $this->pageRepository->getPageByTypeAndParams('news', null, ['tag' => $object->getPK()]);
		
		if (!$page) {
			return;
		}
		
		$page->delete();
	}
}
