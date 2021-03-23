<?php

declare(strict_types=1);

namespace Web\Admin;

use Admin\BackendPresenter;
use Admin\Controls\AdminForm;
use Web\DB\MenuItem;
use Web\DB\MenuItemRepository;
use Web\DB\MenuTypeRepository;
use Web\DB\Page;
use Web\DB\PageRepository;
use Forms\Form;
use StORM\Connection;

class MenuPresenter extends BackendPresenter
{
	public array $menuTypes = [];

	/** @inject */
	public MenuItemRepository $menuItemRepository;

	/** @inject */
	public PageRepository $pageRepository;

	/** @inject */
	public MenuTypeRepository $menuTypeRepository;

	/** @persistent */
	public string $tab = 'main';

	public function beforeRender()
	{
		parent::beforeRender();
		
		$this->menuTypes = $this->menuTypeRepository->getArrayForSelect();
	}

	public function createComponentGrid()
	{
		$grid = $this->gridFactory->create($this->menuItemRepository->many()
			->join(['nxn' => 'web_menuitem_nxn_web_menutype'], 'this.uuid = nxn.fk_menuitem')
			->join(['type' => 'web_menutype'], 'nxn.fk_menutype = type.uuid')
			->where('type.uuid', $this->tab), 20, 'type');
		$grid->setSecondaryOrder(['this.priority' => 'ASC']);
		$grid->addColumnSelector();

		$grid->addColumnText('Název', 'name', '%s', 'name');
		$grid->addColumnText('Titulek', 'page.title', '%s', 'page.title_cs');
		$grid->addColumnText('URL', 'getUrl', '<a href="%1$s"  target="_blank"><i class="fa fa-external-link-square-alt"></i> %1$s</a>');

		$grid->addColumnInputInteger('Priorita', 'priority', '', '', 'priority', [], true);
		$grid->addColumnInputCheckbox('<i title="Skryto" class="far fa-eye-slash"></i>', 'hidden', '', '', 'hidden');

		$grid->addColumnLinkDetail('Detail');

		$btnSecondary = 'btn btn-sm btn-outline-danger';
		$removeIco = "<a href='%s' class='$btnSecondary' title='Odebrat z menu'><i class='far fa-minus-square'\"'></i></a>";
		$grid->addColumnAction('', $removeIco, function (MenuItem $menuItem) {
			$menuItem->delete();
		}, [], null, ['class' => 'minimal']);

		$deleteCb = function (MenuItem $menuItem) {
			$page = $menuItem->page;
			$menuItem->update(['page' => null]);

			if (!$menuItem->isSystemic()) {
				$page->delete();
			}
		};

		$grid->addColumnActionDelete($deleteCb);
		$grid->addButtonSaveAll();
		$grid->addButtonDeleteSelected($deleteCb);

		$grid->addFilterTextInput('search', ['name_cs'], null, 'Název');
		$grid->addFilterButtons();

		return $grid;
	}

	public function createComponentPageGrid()
	{
		$types = ['index', 'content'];

		$grid = $this->gridFactory->create($this->pageRepository->getPagesWithoutMenu($types), 20, 'this.type');
		$grid->addColumnSelector();

		$grid->addColumnText('Název', 'name', '%s', 'this.name_cs');

		$btnSecondary = 'btn btn-sm btn-outline-primary';
		$baseUrl = $this->getHttpRequest()->getUrl()->getBaseUrl();

		$grid->addColumn('URL', static function ($item) use ($baseUrl) {
			return '<a href="' . $baseUrl . $item->url . '" target="_blank"><i class="fa fa-external-link-square-alt"></i>&nbsp;' . $baseUrl . $item->url . '</a>';
		}, '%s', 'this.url_cs');

		$grid->addColumnInputCheckbox('Nedostupná', 'isOffline');

		$grid->addColumn('', function ($object, $datagrid) {
			return $datagrid->getPresenter()->link('linkMenuItemToPage', $object);
		}, "<a class='$btnSecondary' title='Zařadit do menu' href='%s'><i class='fa fa-plus-square'></i></a>", null, ['class' => 'minimal']);

		$grid->addColumnLinkDetail('PageDetail');

		$grid->addColumnActionDeleteSystemic();

		$grid->addButtonSaveAll([], [], 'this.uuid');

		$grid->addButtonDeleteSelected(null, false, function (Page $page) {
			return $page->isSystemic();
		});

		$grid->addFilterTextInput('search', ['this.name_cs', 'this.url_cs', 'this.title_cs'], null, 'Název, url, titulek');

		$grid->addFilterButtons();

		return $grid;
	}

	public function createComponentForm(): Form
	{
		$form = $this->formFactory->create();

		$nameInput = $form->addLocaleText('name', 'Název');
		$form->addLocaleRichEdit('content', 'Obsah');
		$form->addDataMultiSelect('types', 'Umístění', $this->menuTypeRepository->getArrayForSelect())->setRequired();
		$form->addInteger('priority', 'Priorita')->setRequired()->setDefaultValue(10);
		$form->addCheckbox('hidden', 'Skryto');


		/** @var MenuItem $menu */
		$menu = $this->getParameter('menuItem');
		$params = $menu && $menu->page && $menu->page->getType() === 'content' ? ['page' => $menu->getValue('page')] : [];
		$type = $menu && $menu->page && $menu->page->getType() ? $menu->page->getType() : 'content';

		$form->addPageContainer($type, $params, $nameInput);

		$form->addSubmits(!$this->getParameter('menuItem'));

		$form->onSuccess[] = function (AdminForm $form) {
			$values = $form->getValues('array');

			$values['page']['content'] = $values['content'];
			$values['page']['name'] = $values['name'];
			$values['page']['params'] = $values['page']['params'] ?: '';
			$type = $values['page']['type'];
			$values['page'] = (string)$this->pageRepository->syncOne($values['page']);

			$values['path'] = '';

			/** @var MenuItem $menuItem */
			$menuItem = $this->menuItemRepository->syncOne($values, null, true);

			if ($type === 'content') {
				$menuItem->page->update(['params' => 'page=' . $menuItem->page->getPK() . '&']);
			}

			$this->flashMessage('Uloženo', 'success');
			$form->processRedirect('detail', 'default', [$menuItem]);
		};

		return $form;
	}

	public function createComponentPageForm(): Form
	{
		$form = $this->formFactory->create();

		$page = $this->getParameter('page');

		$inputName = $form->addLocaleText('name', 'Název menu');
		$form->addLocaleRichEdit('content', 'Obsah');


		$form->addPageContainer($page ? $page->type : 'content', $this->getParameter('page') ? ['page' => $this->getParameter('page')] : [], $inputName);
		$form->addSubmits(!$this->getParameter('page'));

		$form->onSuccess[] = function (AdminForm $form) {
			$values = $form->getValues('array');

			if (!$values['page']['uuid']) {
				$values['page']['uuid'] = Connection::generateUuid();
				$values['page']['params'] = 'page=' . $values['page']['uuid'] . '&';
			}

			$values['page']['name'] = $values['name'];
			$values['page']['content'] = $values['content'];
			$page = $this->pageRepository->syncOne($values['page']);

			$this->flashMessage('Uloženo', 'success');
			$form->processRedirect('pageDetail', 'default', [$page]);
		};

		return $form;
	}

	public function createComponentMenuForm()
	{
		$form = $this->formFactory->create();

		$form->addLocaleText('name', 'Název menu');

		$form->addSelect('type', 'Umístění', $this->menuTypeRepository->getArrayForSelect());
		$form->addInteger('priority', 'Priorita')->setRequired()->setDefaultValue(10);
		$form->addCheckbox('hidden', 'Skryto');

		$form->addSubmit('submit', 'Uložit');

		$form->onSuccess[] = function (AdminForm $form) {
			$values = $form->getValues('array');

			$values['path'] = '';
			$values['page'] = $form->getPresenter()->getParameter('page')->getPK();

			/** @var MenuItem $menuItem */
			$this->menuItemRepository->createOne($values);

			$this->flashMessage('Uloženo', 'success');
			$form->getPresenter()->redirect('default');
		};

		return $form;
	}

	public function actionLinkMenuItemToPage(Page $page)
	{
		$form = $this->getComponent('menuForm');
		$form['name']->setDefaults($page->toArray()['name']);
	}

	public function renderLinkMenuItemToPage(Page $page)
	{
		$this->template->headerLabel = 'Nová položku menu pro stránku';
		$this->template->headerTree = [
			['Zařezní do menu'],
		];
		$this->template->displayButtons = [$this->createBackButton('default')];
		$this->template->displayControls = [$this->getComponent('menuForm')];
	}

	public function renderDefault()
	{
		$this->template->headerLabel = 'Menu a stránky';
		$this->template->headerTree = [
			['Menu'],
		];

		if ($this->tab === 'pages') {
			$this->template->displayControls = [$this->getComponent('pageGrid')];
			$this->template->displayButtons = [$this->createNewItemButton('newPage')];
		} else {
			$this->template->displayControls = [$this->getComponent('grid')];
			$this->template->displayButtons = [$this->createNewItemButton('new')];
		}

		$this->template->tabs = [];

		foreach ($this->menuTypes as $type => $label) {
			$this->template->tabs[$type] = " $label";
		}

		$this->template->tabs['pages'] = "<i class=\"far fa-sticky-note\"></i> Nezařazené stránky";
	}

	public function renderNew()
	{
		$this->template->headerLabel = 'Nová položka menu';
		$this->template->headerTree = [
			['Menu', 'default'],
			['Nová položka menu'],
		];
		$this->template->displayButtons = [$this->createBackButton('default')];
		$this->template->displayControls = [$this->getComponent('form')];
	}

	public function renderNewPage()
	{
		$this->template->headerLabel = 'Nová položka';
		$this->template->headerTree = [
			['Menu', 'default'],
			['Nová položka'],
		];
		$this->template->displayButtons = [$this->createBackButton('default')];
		$this->template->displayControls = [$this->getComponent('pageForm')];
	}

	public function renderDetail()
	{
		$this->template->headerLabel = 'Detail menu';
		$this->template->headerTree = [
			['Menu', 'default'],
			['Detail'],
		];
		$this->template->displayButtons = [$this->createBackButton('default')];
		$this->template->displayControls = [$this->getComponent('form')];
	}

	public function actionDetail(MenuItem $menuItem)
	{
		/** @var Form $form */
		$form = $this->getComponent('form');
		$defaults = $menuItem->jsonSerialize();

		$form->setDefaults($defaults);
		$form['content']->setDefaults($defaults['page']['content'] ?? []);
	}

	public function renderPageDetail()
	{
		$this->template->headerLabel = 'Detail';
		$this->template->headerTree = [
			['Volné stránky', 'default'],
			['Detail'],
		];
		$this->template->displayButtons = [$this->createBackButton('default')];
		$this->template->displayControls = [$this->getComponent('pageForm')];
	}

	public function actionPageDetail(Page $page)
	{
		/** @var Form $form */
		$form = $this->getComponent('pageForm');
		$form->setDefaults($page->toArray());
	}

}