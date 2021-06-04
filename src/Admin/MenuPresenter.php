<?php

declare(strict_types=1);

namespace Web\Admin;

use Admin\BackendPresenter;
use Admin\Controls\AdminForm;
use Nette\Utils\Arrays;
use Nette\Utils\Random;
use Nette\Utils\Strings;
use StORM\DIConnection;
use StORM\Entity;
use Web\DB\MenuAssign;
use Web\DB\MenuAssignRepository;
use Web\DB\MenuItem;
use Web\DB\MenuItemRepository;
use Web\DB\MenuTypeRepository;
use Web\DB\Page;
use Web\DB\PageRepository;
use Forms\Form;
use StORM\Connection;

class MenuPresenter extends BackendPresenter
{
	protected const CONFIGURATIONS = [
		'background' => false,
	];
	
	public array $menuTypes = [];

	/** @inject */
	public MenuItemRepository $menuItemRepository;

	/** @inject */
	public PageRepository $pageRepository;

	/** @inject */
	public MenuTypeRepository $menuTypeRepository;

	/** @inject */
	public MenuAssignRepository $menuAssignRepository;

	/** @persistent */
	public string $tab = 'main';

	protected array $pageTypes = ['index' => '', 'content' => null, 'contact' => null, 'news' => '', 'pickup_points' => null];

	private array $selectedAncestors = [];

	public function createComponentGrid()
	{
		$grid = $this->gridFactory->create($this->menuItemRepository->many()
			->join(['nxn' => 'web_menuassign'], 'this.uuid = nxn.fk_menuitem')
			->join(['type' => 'web_menutype'], 'nxn.fk_menutype = type.uuid')
			->where('type.uuid', $this->tab)
			->select(['path' => 'nxn.path']), 20);
		
		$grid->setDefaultOrder('priority');

		$grid->setNestingCallback(static function ($source, $parent) {
			if (!$parent) {
				return $source->where('LENGTH(path)=4');
			}

			return $source->where('path!=:parent AND path LIKE :path',
				['path' => $parent->path . '%', 'parent' => $parent->path]);
		});

		$grid->addColumnSelector();

		$grid->addColumnText('Název', 'name', '%s', 'name')->onRenderCell[] = function (\Nette\Utils\Html $td, $object) {
			$level = \strlen($object->path) / 4 - 1;
			$td->setHtml(\str_repeat('- - ', $level) . $td->getHtml());
		};

		$grid->addColumnText('Titulek', 'page.title', '%s', 'page.title_cs');
		$grid->addColumnText('URL', 'getUrl',
			'<a href="%1$s"  target="_blank"><i class="fa fa-external-link-square-alt"></i> %1$s</a>');

		$grid->addColumnInputInteger('Priorita', 'priority', '', '', 'priority', [], true);
		$grid->addColumnInputCheckbox('<i title="Skryto" class="far fa-eye-slash"></i>', 'hidden', '', '', 'hidden');

		$grid->addColumnLinkDetail('Detail');

		$btnSecondary = 'btn btn-sm btn-outline-danger';
		$removeIco = "<a href='%s' class='$btnSecondary' title='Odebrat z menu'><i class='far fa-minus-square'\"'></i></a>";
		$grid->addColumnAction('', $removeIco, function (MenuItem $menuItem) {
			$this->onRemove($menuItem);
			$menuItem->delete();
		}, [], null, ['class' => 'minimal']);

		$deleteCb = function (?MenuItem $menuItem) {
			if (!$menuItem) {
				return;
			}

			$this->onRemove($menuItem);

			$page = $menuItem->page;
			$menuItem->update(['page' => null]);

			if (!$menuItem->isSystemic()) {
				$page->delete();
			}
		};

		$grid->addColumnActionDelete($deleteCb);
		$grid->addButtonSaveAll();
		$grid->addButtonDeleteSelected($deleteCb, false, null, 'this.uuid');

		$grid->addFilterTextInput('search', ['name_cs'], null, 'Název');
		$grid->addFilterButtons();

		return $grid;
	}

	public function createComponentPageGrid()
	{
		$types = $this->pageTypes;

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
		}, "<a class='$btnSecondary' title='Zařadit do menu' href='%s'><i class='fa fa-plus-square'></i></a>", null,
			['class' => 'minimal']);

		$grid->addColumnLinkDetail('PageDetail');

		$grid->addColumnActionDeleteSystemic();

		$grid->addButtonSaveAll([], [], 'this.uuid');

		$grid->addButtonDeleteSelected(null, false, function (Page $page) {
			return !$page->isSystemic();
		},'this.uuid');

		$grid->addFilterTextInput('search', ['this.name_cs', 'this.url_cs', 'this.title_cs'], null,
			'Název, url, titulek');

		$grid->addFilterButtons();

		return $grid;
	}

	public function createComponentForm(): Form
	{
		$form = $this->formFactory->create(true);
		$form->setPrettyPages(true);

		/** @var MenuItem $menu */
		$menu = $this->getParameter('menuItem');

		$nameInput = $form->addLocaleText('name', 'Název');
		
		if (static::CONFIGURATIONS['background']) {
			$imagePicker = $form->addImagePicker('image', 'Pozadí (desktop)', [
					Page::IMAGE_DIR => null,
				]
			);
			
			$imagePicker->onDelete[] = function ($dir, $file) use ($menu) {
				
				if ($menu->page) {
					$menu->page->update(['image' => null]);
				}
				$this->redirect('this');
			};
			
			$imagePicker = $form->addImagePicker('mobileImage', 'Pozadí (mobil)', [
					Page::IMAGE_DIR => null,
				]
			);
			
			$imagePicker->onDelete[] = function ($dir, $file) use ($menu) {
				
				if ($menu->page) {
					$menu->page->update(['mobileImage' => null]);
				}
				$this->redirect('this');
			};
		}
		
		$form->addLocaleRichEdit('content', 'Obsah');
		$form->addDataMultiSelect('types', 'Umístění',
			$this->menuItemRepository->getTreeArrayForSelect(false, null, $menu))->setRequired();
		$form->addInteger('priority', 'Priorita')->setRequired()->setDefaultValue(10);
		$form->addCheckbox('hidden', 'Skryto');
		
		$params = $menu && $menu->page ? $menu->page->getParsedParameters() : [];
		$type = $menu && $menu->page ? $menu->page->getType() : 'content';

		$form->addPageContainer($type, $params, $nameInput);

		$form->addSubmits(!$menu);

		$form->onValidate[] = function (AdminForm $form) {
			if (!$form->isValid()) {
				return;
			}

			$this->menuItemRepository->checkAncestors($form, $this->selectedAncestors);
		};

		$form->onSuccess[] = function (AdminForm $form) {
			$values = $form->getValues('array');
			unset($values['types']);

			if (!$values['uuid']) {
				$values['uuid'] = DIConnection::generateUuid();
			}

			if (!$values['page']['uuid']) {
				$values['page']['uuid'] = Connection::generateUuid();
				$values['page']['params'] = 'page=' . $values['page']['uuid'] . '&';
			}
			
			if (static::CONFIGURATIONS['background']) {
				if ($values['image']->isOK()) {
					$values['page']['image'] = $form['image']->upload($values['image']->getSanitizedName());
				}
				
				unset($values['image']);
				
				if ($values['mobileImage']->isOK()) {
					$values['page']['mobileImage'] = $form['mobileImage']->upload($values['mobileImage']->getSanitizedName());
				}
				
				unset($values['mobileImage']);
			}

			$values['page']['content'] = $values['content'];
			$values['page']['name'] = $values['name'];
			$values['page']['params'] = $values['page']['params'] ?: '';
			$type = $values['page']['type'];
			$values['page'] = (string)$this->pageRepository->syncOne($values['page']);

			$menuItem = $this->menuItemRepository->syncOne($values, null, true);

			$selectedMenuTypes = [];

			foreach ($this->selectedAncestors as $type) {
				if (isset($type['item'])) {
					$ancestor = $this->menuAssignRepository->many()
						->where('fk_menuitem', $type['item']->getPK())
						->where('fk_menutype', $type['type']->getPK())
						->first();
				} else {
					$ancestor = null;
				}

				$prefix = $ancestor ? $ancestor->path : '';
				$path = null;

				do {
					$path = $prefix . Random::generate(4);
					$temp = $this->menuItemRepository->many()
						->join(['nxn' => 'web_menuassign'], 'this.uuid = nxn.fk_menuitem')
						->where('nxn.path', $path)
						->first();
				} while ($temp);

				$data = [
					'ancestor' => ($ancestor ? $ancestor->getPK() : null),
					'path' => $path
				];

				if ($current = $this->menuAssignRepository->many()
					->where('fk_menuitem', $menuItem->getPK())
					->where('fk_menutype', $type['type']->getPK())
					->first()) {
					$current->update($data);
				} else {
					$this->menuAssignRepository->createOne([
						'menuitem' => $menuItem->getPK(),
						'menutype' => $type['type']->getPK(),
						'ancestor' => ($ancestor ? $ancestor->getPK() : null),
						'path' => $path
					]);
				}

				$this->menuItemRepository->recalculatePaths($type['type']);

				$selectedMenuTypes[$type['type']->getPK()] = true;
			}

			foreach ($this->menuTypeRepository->many()->whereNot('uuid', \array_keys($selectedMenuTypes))->toArray() as $notSelectedType) {
				$this->menuAssignRepository->many()->where('fk_menutype', $notSelectedType)->where('fk_menuitem', $menuItem->getPK())->delete();
			}
//
//			foreach ($typesExists as $type) {
//				$assign = $this->menuAssignRepository->syncOne([
//					'menuitem' => $menuItem->getPK(),
//					'menutype' => $type['type']->getPK()
//				]);
//
//				$prefix = isset($type['item']) ? $type['item']->path : '';
//
//				$path = null;
//
//				do {
//					$path = $prefix . Random::generate(4);
//					$temp = $this->menuItemRepository->many()
//						->join(['nxn' => 'web_menuassign'], 'this.uuid = nxn.fk_menuitem')
//						->where('nxn.path', $path)
//						->first();
//				} while ($temp);
//
//				/** @var \Web\DB\MenuItem $menuItem */
//				$menuItem = $this->menuItemRepository->getCollection()
//					->join(['nxn' => 'web_menuassign'], 'this.uuid = nxn.fk_menuitem')
//					->where('nxn.fk_menuitem', $menuItem->getPK())
//					->select(['path' => 'nxn.path'])
//					->first();
//
//				if ((\strlen($path) / 4) + ($this->menuItemRepository->getMaxDeepLevel($menuItem) - (\strlen($menuItem->path) / 4)) > $type['type']->maxLevel) {
//					$this->flashMessage('Chyba! Položku "' . (isset($selectedTypeItem) ? $selectedTypeItem->name : $type['type']->name) . '" nelze více zanořit!',
//						'error');
//					$this->redirect('this');
//				}
//
//				$ancestor = isset($type['item']) ? $type['item']->getPK() : null;
//
//				$assign->update([
//					'ancestor' => $ancestor,
//					'path' => $path
//				]);
//
//				$this->menuItemRepository->recalculatePaths($type['type']);
//			}
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
		$form = $this->formFactory->create(true);
		$form->setPrettyPages(true);

		$page = $this->getParameter('page');

		$inputName = $form->addLocaleText('name', 'Název');
		$form->addLocaleRichEdit('content', 'Obsah');

		$form->addPageContainer($page ? $page->type : 'content',
			$this->getParameter('page') ? ['page' => $this->getParameter('page')] : [], $inputName);
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
		$form = $this->formFactory->create(true);
		$form->setPrettyPages(true);

		$form->addLocaleText('name', 'Název menu');

		$form->addDataMultiSelect('types', 'Umístění',
			$this->menuItemRepository->getTreeArrayForSelect())->setRequired();
		$form->addInteger('priority', 'Priorita')->setRequired()->setDefaultValue(10);
		$form->addCheckbox('hidden', 'Skryto');

		$form->addSubmit('submit', 'Uložit');

		$form->onValidate[] = function (AdminForm $form) {
			$this->menuItemRepository->checkAncestors($form, $this->selectedAncestors);
		};

		$form->onSuccess[] = function (AdminForm $form) {
			$values = $form->getValues('array');
			unset($values['types']);

			$values['uuid'] = DIConnection::generateUuid();
			$values['page'] = $form->getPresenter()->getParameter('page')->getPK();

			/** @var MenuItem $menuItem */
			$menuItem = $this->menuItemRepository->createOne($values);

			foreach ($this->selectedAncestors as $type) {
				if (isset($type['item'])) {
					$ancestor = $this->menuAssignRepository->many()
						->where('fk_menuitem', $type['item']->getPK())
						->where('fk_menutype', $type['type']->getPK())
						->first();
				} else {
					$ancestor = null;
				}

				$prefix = $ancestor ? $ancestor->path : '';
				$path = null;

				do {
					$path = $prefix . Random::generate(4);
					$temp = $this->menuItemRepository->many()
						->join(['nxn' => 'web_menuassign'], 'this.uuid = nxn.fk_menuitem')
						->where('nxn.path', $path)
						->first();
				} while ($temp);

				$this->menuAssignRepository->syncOne([
					'menuitem' => $menuItem->getPK(),
					'menutype' => $type['type']->getPK(),
					'ancestor' => ($ancestor ? $ancestor->getPK() : null),
					'path' => $path
				]);
			}

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

		foreach ($this->menuTypeRepository->getCollection()->toArrayOf('name') as $type => $label) {
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
		$defaults['types'] = $this->menuItemRepository->getMenuItemPositions($menuItem);

		$form->setDefaults($defaults);
		$form['content']->setDefaults($defaults['page']['content'] ?? []);
		
		if (static::CONFIGURATIONS['background']) {
			$form['image']->setDefaultValue($menuItem->page->image ?? null);
		}
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

	public function onRemove(MenuItem $menuItem)
	{
		$menuItem = $this->menuItemRepository->many()->join(['assign' => 'web_menuassign'], 'this.uuid = assign.fk_menuitem')
			->where('assign.fk_menutype', $this->tab)
			->where('this.uuid', $menuItem->getPK())
			->select(['path' => 'assign.path'])
			->first();

		$this->menuItemRepository->many()->join(['assign' => 'web_menuassign'], 'this.uuid = assign.fk_menuitem')
			->where('fk_menutype', $this->tab)
			->where('assign.path LIKE :path', ['path' => "$menuItem->path%"])
			->delete();
	}

}