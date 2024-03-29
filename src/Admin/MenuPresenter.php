<?php

declare(strict_types=1);

namespace Web\Admin;

use Admin\BackendPresenter;
use Admin\Controls\AdminForm;
use Admin\Controls\AdminGrid;
use Forms\Form;
use Nette\Caching\Cache;
use Nette\Caching\Storage;
use Nette\Forms\Controls\HiddenField;
use Nette\Utils\Image;
use Nette\Utils\Random;
use StORM\Connection;
use StORM\DIConnection;
use Web\DB\DocumentRepository;
use Web\DB\MenuAssignRepository;
use Web\DB\MenuItem;
use Web\DB\MenuItemRepository;
use Web\DB\MenuTypeRepository;
use Web\DB\Page;
use Web\DB\PageRepository;
use Web\Helpers;

class MenuPresenter extends BackendPresenter
{
	protected const CONFIGURATIONS = [
		'background' => false,
		'icon' => 'false',
		'documents' => false,
		'iconImage' => [
			 'width' => null,
			 'height' => null,
		],
		'richSnippet' => false,
	];
	
	/**
	 * @var mixed[]
	 */
	public array $menuTypes = [];

	/**
	 * @inject
	 */
	public MenuItemRepository $menuItemRepository;

	/**
	 * @inject
	 */
	public PageRepository $pageRepository;

	/**
	 * @inject
	 */
	public MenuTypeRepository $menuTypeRepository;

	/**
	 * @inject
	 */
	public MenuAssignRepository $menuAssignRepository;

	/**
	 * @inject
	 */
	public DocumentRepository $documentRepository;

	/**
	 * @inject
	 */
	public Storage $storage;

	/**
	 * @persistent
	 */
	public string $tab = 'main';

	protected Cache $cache;
	
	/**
	 * @var mixed[]
	 */
	protected array $pageTypes = ['index' => '', 'content' => null, 'contact' => null, 'news' => '', 'pickup_points' => null];
	
	/**
	 * @var mixed[]
	 */
	private array $selectedAncestors = [];

	public function createComponentGrid(): AdminGrid
	{
		$grid = $this->gridFactory->create($this->menuItemRepository->many()
			->join(['nxn' => 'web_menuassign'], 'this.uuid = nxn.fk_menuitem')
			->join(['type' => 'web_menutype'], 'nxn.fk_menutype = type.uuid')
			->where('type.uuid', $this->tab)
			->select(['path' => 'nxn.path']), 20);

		$grid->setDefaultOrder('this.priority');

		$grid->setNestingCallback(static function ($source, $parent) {
			if (!$parent) {
				return $source->where('LENGTH(nxn.path)=4');
			}

			return $source->where(
				'nxn.path!=:parent AND nxn.path LIKE :path',
				['path' => $parent->path . '%', 'parent' => $parent->path],
			);
		});

		$grid->addColumnSelector();

		$grid->addColumnText('Název', 'name', '%s', 'name')->onRenderCell[] = function (\Nette\Utils\Html $td, $object): void {
			$level = \strlen($object->path) / 4 - 1;
			$td->setHtml(\str_repeat('- - ', $level) . $td->getHtml());
		};

		$grid->addColumnText('Titulek', 'page.title', '%s', 'page.title_cs');
		$grid->addColumnText(
			'URL',
			'getUrl',
			'<a href="%1$s"  target="_blank"><i class="fa fa-external-link-square-alt"></i> %1$s</a>',
		);

		$grid->addColumnInputInteger('Priorita', 'priority', '', '', 'priority', [], true);
		$grid->addColumnInputCheckbox('<i title="Skryto" class="far fa-eye-slash"></i>', 'hidden', '', '', 'hidden');

		$grid->addColumnLinkDetail('Detail');

		$btnSecondary = 'btn btn-sm btn-outline-danger';
		$confirmJS = 'return confirm("' . $this->translator->translate('admin.really', 'Opravdu?') . '")';
		$title = $this->_('remove', 'Odebrat z menu');
		$removeIco = "<a href='%s' class='$btnSecondary' title='" . $title . "' onclick='" . $confirmJS . "'><i class='far fa-minus-square mr-1'\"'></i> " . $title . '</a>';
		$grid->addColumnAction('', $removeIco, function (MenuItem $menuItem): void {
			if ($this->menuItemRepository->hasChildren($menuItem)) {
				$this->getPresenter()->flashMessage('Položku nelze odebrat protože má pod sebou položky.', 'warning');
				
				$this->getPresenter()->redirect('this');
			}
			
			$this->onRemove($menuItem);
			$menuItem->delete();
		}, [], null, ['class' => 'minimal']);
		
		$deleteCb = function (?MenuItem $menuItem): void {
			if (!$menuItem) {
				return;
			}
			
			if ($this->menuItemRepository->hasChildren($menuItem)) {
				$this->getPresenter()->flashMessage('Položku nelze odebrat protože má pod sebou položky.', 'warning');
				
				return;
			}
			
			$this->onRemove($menuItem);
			
			$page = $menuItem->page;
			$menuItem->update(['page' => null]);
			
			if ($page && !$menuItem->isSystemic()) {
				$page->delete();
			}
			
			$menuItem->delete();
			
			$this->menuItemRepository->clearMenuCache();
		};
		
		$grid->addColumnActionDeleteSystemic($deleteCb, true);
		$grid->addButtonSaveAll([], [], null, false, null, null, true, null, function (): void {
			$this->menuItemRepository->clearMenuCache();
		});
		$grid->addButtonDeleteSelected($deleteCb, false, function (MenuItem $object) {
			return !$object->isSystemic();
		}, 'this.uuid', function (): void {
			$this->menuItemRepository->clearMenuCache();
		});

		$grid->addFilterTextInput('search', ['this.name_cs'], null, 'Název');
		$grid->addFilterButtons();

		return $grid;
	}

	public function createComponentPageGrid(): AdminGrid
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
		}, "<a class='$btnSecondary' title='Zařadit do menu' href='%s'><i class='fa fa-plus-square mr-1'></i>Zařadit do menu</a>", null,
			['class' => 'minimal']);

		$grid->addColumnLinkDetail('PageDetail');

		$grid->addColumnActionDeleteSystemic();

		$grid->addButtonSaveAll([], [], 'this.uuid');

		$grid->addButtonDeleteSelected(null, false, function (Page $page) {
			return !$page->isSystemic();
		}, 'this.uuid');

		$grid->addFilterTextInput(
			'search',
			['this.name_cs', 'this.url_cs', 'this.title_cs'],
			null,
			'Název, URL, titulek',
		);

		$grid->addFilterButtons();

		return $grid;
	}

	public function createComponentForm(): Form
	{
		$form = $this->formFactory->create(true, true);

		if (\count($form->getMutations()) === 1) {
			$form->addLocaleHidden('active')->forAll(function (HiddenField $hidden): void {
				$hidden->setDefaultValue(true)->addFilter(function ($value) {
					return (bool)$value;
				});
			});
		}

		$form->setPrettyPages(true);

		/** @var \Web\DB\MenuItem $menu */
		$menu = $this->getParameter('menuItem');

		$nameInput = $form->addLocaleText('name', 'Název');

		if (self::CONFIGURATIONS['background']) {
			$imagePicker = $form->addImagePicker('image', 'Pozadí (desktop)', [
					Page::IMAGE_DIR => null,
				]);

			$imagePicker->onDelete[] = function ($dir, $file) use ($menu): void {
				if ($menu->page) {
					$menu->page->update(['image' => null]);
				}

				$this->redirect('this');
			};

			$imagePicker = $form->addImagePicker('mobileImage', 'Pozadí (mobil)', [
					Page::IMAGE_DIR => null,
				]);

			$imagePicker->onDelete[] = function ($dir, $file) use ($menu): void {
				if ($menu->page) {
					$menu->page->update(['mobileImage' => null]);
				}

				$this->redirect('this');
			};
		}

		$form->addLocaleRichEdit('content', 'Obsah');
		$form->addDataMultiSelect(
			'types',
			'Umístění',
			$this->menuItemRepository->getTreeArrayForSelect(false, null, $menu),
		)->setRequired();
		$form->addInteger('priority', 'Priorita')->setRequired()->setDefaultValue(10);

		if (isset(self::CONFIGURATIONS['icon']) && self::CONFIGURATIONS['icon']) {
			$form->addText('icon', $this->_('icon', 'Ikona v menu'))
				->setOption('description', $this->_('iconDescription', 'Vkládejte kód v tomto formátu') . ' <i class="far fa-address-card"></i>')->setNullable();
		}

		if (isset(self::CONFIGURATIONS['iconImage'])) {
			$iconPicker = $form->addImagePicker('iconImage', $this->_('icon', 'Ikona v menu'), [
				MenuItem::IMAGE_DIR => static function (Image $image): void {
					$width = self::CONFIGURATIONS['iconImage']['width'] ?? 32;
					$height = self::CONFIGURATIONS['iconImage']['height'] ?? 32;
					$image->resize($width, $height);
				},
			]);

			$iconPicker->onDelete[] = function () use ($menu): void {
				if ($menu) {
					$menu->update(['iconImage' => null]);
					$this->redirect('this');
				}
			};
		}

		if (isset(self::CONFIGURATIONS['documents']) && self::CONFIGURATIONS['documents']) {
			$form['page']->addMultiSelect2('documents', $this->_('documents', 'Dokumenty'), $this->documentRepository->many()->toArray());
		}

		$form->addCheckbox('hidden', 'Skryto');

		$params = $menu && $menu->page ? $menu->page->getParsedParameters() : [];
		$type = $menu && $menu->page ? $menu->page->getType() : 'content';

		$form->addPageContainer($type, $params, $nameInput, false, true, false, 'URL a SEO', true, true, isset($this::CONFIGURATIONS['richSnippet']) && $this::CONFIGURATIONS['richSnippet']);

		$form->addSubmits(!$menu);

		$form->onValidate[] = function (AdminForm $form): void {
			if (!$form->isValid()) {
				return;
			}

			$this->menuItemRepository->checkAncestors($form, $this->selectedAncestors);
		};

		$form->onSuccess[] = function (AdminForm $form): void {
			$this->generateDirectories([Page::IMAGE_DIR], Page::SUBDIRS);
			$values = $form->getValues('array');

			if (!$values['uuid']) {
				$values['uuid'] = DIConnection::generateUuid();
			}

			if (isset(self::CONFIGURATIONS['iconImage'])) {
				$this->generateDirectories([MenuItem::IMAGE_DIR]);
				$values['iconImage'] = $form['iconImage']->upload($values['uuid'] . '.%2$s');
			}

			if (isset(self::CONFIGURATIONS['documents']) && self::CONFIGURATIONS['documents']) {
				$values['page']['documents'] = $values['documents'];
				unset($values['documents']);
			}

			unset($values['types']);

			if (!$values['page']['uuid']) {
				$values['page']['uuid'] = Connection::generateUuid();
				$values['page']['params'] = 'page=' . $values['page']['uuid'] . '&';
			}

			if (isset($values['page']['opengraph'])) {
				$values['page']['opengraph'] = $form['page']['opengraph']->upload($values['page']['uuid'] . '.%2$s');
			}

			if (self::CONFIGURATIONS['background']) {
				if ($values['image']->isOK()) {
					$values['page']['image'] = $form['image']->upload($values['image']->getSanitizedName());
				}

				unset($values['image']);

				if ($values['mobileImage']->isOK()) {
					$values['page']['mobileImage'] = $form['mobileImage']->upload($values['mobileImage']->getSanitizedName());
				}

				unset($values['mobileImage']);
			}

			$values['page']['content'] = static::sanitizePageContent($values['content']);
			$values['page']['name'] = $values['name'];
			$values['page']['params'] = $values['page']['params'] ?: '';
			$type = $values['page']['type'];
			$values['page'] = (string)$this->pageRepository->syncOne($values['page']);

			$menuItem = $this->menuItemRepository->syncOne($values, null, true);

			$selectedMenuTypes = [];

			foreach ($this->selectedAncestors as $type) {
				$ancestor = isset($type['item']) ? $this->menuAssignRepository->many()
						->where('fk_menuitem', $type['item']->getPK())
						->where('fk_menutype', $type['type']->getPK())
						->first() : null;

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
					'path' => $path,
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
						'path' => $path,
					]);
				}

				$this->menuItemRepository->recalculatePaths($type['type']);

				$selectedMenuTypes[$type['type']->getPK()] = true;
			}

			foreach ($this->menuTypeRepository->many()->whereNot('uuid', \array_keys($selectedMenuTypes))->toArray() as $notSelectedType) {
				$this->menuAssignRepository->many()->where('fk_menutype', $notSelectedType)->where('fk_menuitem', $menuItem->getPK())->delete();
			}

			//          foreach ($typesExists as $type) {
			//              $assign = $this->menuAssignRepository->syncOne([
			//                  'menuitem' => $menuItem->getPK(),
			//                  'menutype' => $type['type']->getPK()
			//              ]);
			//
			//              $prefix = isset($type['item']) ? $type['item']->path : '';
			//
			//              $path = null;
			//
			//              do {
			//                  $path = $prefix . Random::generate(4);
			//                  $temp = $this->menuItemRepository->many()
			//                      ->join(['nxn' => 'web_menuassign'], 'this.uuid = nxn.fk_menuitem')
			//                      ->where('nxn.path', $path)
			//                      ->first();
			//              } while ($temp);
			//
			//              /** @var \Web\DB\MenuItem $menuItem */
			//              $menuItem = $this->menuItemRepository->getCollection()
			//                  ->join(['nxn' => 'web_menuassign'], 'this.uuid = nxn.fk_menuitem')
			//                  ->where('nxn.fk_menuitem', $menuItem->getPK())
			//                  ->select(['path' => 'nxn.path'])
			//                  ->first();
			//
			//              if ((\strlen($path) / 4) + ($this->menuItemRepository->getMaxDeepLevel($menuItem) - (\strlen($menuItem->path) / 4)) > $type['type']->maxLevel) {
			//                  $this->flashMessage('Chyba! Položku "' . (isset($selectedTypeItem) ? $selectedTypeItem->name : $type['type']->name) . '" nelze více zanořit!',
			//                      'error');
			//                  $this->redirect('this');
			//              }
			//
			//              $ancestor = isset($type['item']) ? $type['item']->getPK() : null;
			//
			//              $assign->update([
			//                  'ancestor' => $ancestor,
			//                  'path' => $path
			//              ]);
			//
			//              $this->menuItemRepository->recalculatePaths($type['type']);
			//          }
			if ($type === 'content') {
				$menuItem->page->update(['params' => 'page=' . $menuItem->page->getPK() . '&']);
			}

			$this->menuItemRepository->clearMenuCache();

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

		$inputName = $form->addLocaleText('name', 'Název')->forPrimary(function ($input): void {
			$input->setRequired();
		});

		if (self::CONFIGURATIONS['background']) {
			$imagePicker = $form->addImagePicker('image', 'Pozadí (desktop)', [
					Page::IMAGE_DIR => null,
				]);

			$imagePicker->onDelete[] = function ($dir, $file) use ($page): void {
				$page->update(['image' => null]);
				$this->redirect('this');
			};

			$imagePicker = $form->addImagePicker('mobileImage', 'Pozadí (mobil)', [
					Page::IMAGE_DIR => null,
				]);

			$imagePicker->onDelete[] = function ($dir, $file) use ($page): void {
				$page->update(['mobileImage' => null]);
				$this->redirect('this');
			};
		}

		if (isset(self::CONFIGURATIONS['documents']) && self::CONFIGURATIONS['documents']) {
			$form['page']->addMultiSelect2('documents', $this->_('documents', 'Dokumenty'), $this->documentRepository->many()->toArray());
		}

		$form->addLocaleRichEdit('content', 'Obsah');

		$form->addPageContainer(
			$page ? $page->type : 'content',
			$this->getPageParamsInPageFormForPageContainer($this->getParameter('page')),
			$inputName,
			false,
			true,
			false,
			'URL a SEO',
			false,
			true,
			isset($this::CONFIGURATIONS['richSnippet']) && $this::CONFIGURATIONS['richSnippet'],
		);
		$form->addSubmits(!$this->getParameter('page'));

		$form->onSuccess[] = function (AdminForm $form): void {
			$values = $form->getValues('array');

			if (!$values['page']['uuid']) {
				$values['page']['uuid'] = Connection::generateUuid();
				$values['page']['params'] = 'page=' . $values['page']['uuid'] . '&';
			}

			if (self::CONFIGURATIONS['background']) {
				if ($values['image']->isOK()) {
					$values['page']['image'] = $form['image']->upload($values['image']->getSanitizedName());
				}

				unset($values['image']);

				if ($values['mobileImage']->isOK()) {
					$values['page']['mobileImage'] = $form['mobileImage']->upload($values['mobileImage']->getSanitizedName());
				}

				unset($values['mobileImage']);
			}

			$values['page']['name'] = $values['name'];
			$values['page']['content'] = static::sanitizePageContent($values['content']);
			$page = $this->pageRepository->syncOne($values['page']);

			$this->menuItemRepository->clearMenuCache();

			$this->flashMessage('Uloženo', 'success');
			$form->processRedirect('pageDetail', 'default', [$page]);
		};

		return $form;
	}

	public function createComponentMenuForm(): AdminForm
	{
		$form = $this->formFactory->create(true, true);

		if (\count($form->getMutations()) === 1) {
			$form->addLocaleHidden('active')->forAll(function (HiddenField $hidden): void {
				$hidden->setDefaultValue(true)->addFilter(function ($value) {
					return (bool)$value;
				});
			});
		}

		$form->setPrettyPages(true);

		$form->addLocaleText('name', 'Název položky');

		$form->addDataMultiSelect('types', 'Umístění', $this->menuItemRepository->getTreeArrayForSelect())->setRequired();
		$form->addInteger('priority', 'Priorita')->setRequired()->setDefaultValue(10);
		$form->addCheckbox('hidden', 'Skryto');

		$form->addSubmit('submit', 'Uložit');

		$form->onValidate[] = function (AdminForm $form): void {
			$this->menuItemRepository->checkAncestors($form, $this->selectedAncestors);
		};

		$form->onSuccess[] = function (AdminForm $form): void {
			$values = $form->getValues('array');
			unset($values['types']);

			$values['uuid'] = DIConnection::generateUuid();
			$values['page'] = $form->getPresenter()->getParameter('page')->getPK();

			/** @var \Web\DB\MenuItem $menuItem */
			$menuItem = $this->menuItemRepository->createOne($values);

			foreach ($this->selectedAncestors as $type) {
				$ancestor = isset($type['item']) ? $this->menuAssignRepository->many()
						->where('fk_menuitem', $type['item']->getPK())
						->where('fk_menutype', $type['type']->getPK())
						->first() : null;

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
					'path' => $path,
				]);
			}

			$this->menuItemRepository->clearMenuCache();

			$this->flashMessage('Uloženo', 'success');
			$form->getPresenter()->redirect('default');
		};

		return $form;
	}

	public function actionLinkMenuItemToPage(Page $page): void
	{
		$form = $this->getComponent('menuForm');
		$form['name']->setDefaults($page->toArray()['name']);
	}

	public function renderLinkMenuItemToPage(Page $page): void
	{
		unset($page);
		$this->template->headerLabel = 'Nová položku menu pro stránku';
		$this->template->headerTree = [
			['Zařezní do menu'],
		];
		$this->template->displayButtons = [$this->createBackButton('default')];
		$this->template->displayControls = [$this->getComponent('menuForm')];
	}

	public function renderDefault(): void
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

		$this->template->tabs['pages'] = '<i class="far fa-sticky-note"></i> Nezařazené stránky';
	}

	public function renderNew(): void
	{
		$this->template->headerLabel = 'Nová položka menu';
		$this->template->headerTree = [
			['Menu', 'default'],
			['Nová položka menu'],
		];
		$this->template->displayButtons = [$this->createBackButton('default')];
		$this->template->displayControls = [$this->getComponent('form')];
	}

	public function renderNewPage(): void
	{
		$this->template->headerLabel = 'Nová položka';
		$this->template->headerTree = [
			['Menu', 'default'],
			['Nová položka'],
		];
		$this->template->displayButtons = [$this->createBackButton('default')];
		$this->template->displayControls = [$this->getComponent('pageForm')];
	}

	public function renderDetail(): void
	{
		$this->template->headerLabel = 'Detail menu';
		$this->template->headerTree = [
			['Menu', 'default'],
			['Detail'],
		];
		$this->template->displayButtons = [$this->createBackButton('default')];
		$this->template->displayControls = [$this->getComponent('form')];
	}

	public function actionDetail(MenuItem $menuItem): void
	{
		/** @var \Forms\Form $form */
		$form = $this->getComponent('form');
		$defaults = $menuItem->toArray(['page']);
		$defaults['page'] = $menuItem->page->toArray(['documents']);
		$defaults['types'] = $this->menuItemRepository->getMenuItemPositions($menuItem);

		foreach (\array_keys($this->menuItemRepository->getConnection()->getAvailableMutations()) as $mutation) {
			$defaults['active'][$mutation] = $defaults['active'][$mutation] ? '1' : '0';
		}

		$form->setDefaults($defaults);
		$form['content']->setDefaults($defaults['page']['content'] ?? []);

		if (!self::CONFIGURATIONS['background']) {
			return;
		}

		$form['image']->setDefaultValue($menuItem->page->image ?? null);
		$form['mobileImage']->setDefaultValue($menuItem->page->mobileImage ?? null);
	}

	public function renderPageDetail(): void
	{
		$this->template->headerLabel = 'Detail';
		$this->template->headerTree = [
			['Volné stránky', 'default'],
			['Detail'],
		];
		$this->template->displayButtons = [$this->createBackButton('default')];
		$this->template->displayControls = [$this->getComponent('pageForm')];
	}

	public function actionPageDetail(Page $page): void
	{
		/** @var \Forms\Form $form */
		$form = $this->getComponent('pageForm');
		$form->setDefaults($page->toArray(['documents']));
	}

	public function onRemove(MenuItem $menuItem): void
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

		$this->menuItemRepository->clearMenuCache();
	}

	/**
	 * @param mixed[] $content Array with mutations as keys
	 * @return mixed[]
	 * @deprecated user function from Helpers
	 */
	public static function sanitizePageContent(array $content): array
	{
		return Helpers::sanitizeMutationsStrings($content);
	}

	protected function startup(): void
	{
		parent::startup();

		$this->cache = new Cache($this->storage);
	}

	/**
	 * @param \Web\DB\Page|null $page
	 * @return array<mixed>
	 */
	protected function getPageParamsInPageFormForPageContainer(?Page $page = null): array
	{
		if (!$page) {
			return [];
		}

		if ($page->getType() === 'faq' && isset($page->getParsedParameters()['tag'])) {
			return ['tag' => $page->getParsedParameters()['tag']];
		}

		return ['page' => $page->getPK()];
	}
}
