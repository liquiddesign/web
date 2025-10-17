<?php

declare(strict_types=1);

namespace Web\Admin;

use Admin\BackendPresenter;
use Admin\Controls\AdminForm;
use Admin\Controls\AdminGrid;
use Base\DB\Shop;
use Forms\Form;
use Nette\Caching\Cache;
use Nette\Caching\Storage;
use Nette\Forms\Controls\HiddenField;
use Nette\Utils\Arrays;
use Nette\Utils\Image;
use Nette\Utils\Random;
use Nette\Utils\Strings;
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
	 * @var array<mixed>
	 */
	public array $menuTypes = [];

	/**
	 * @var array<callable(\Admin\Controls\AdminForm $form): void>
	 */
	public array $onBeforeSubmitMenuItemForm = [];

	/**
	 * @var array<callable(\Admin\Controls\AdminForm $form, array& $values): void>
	 */
	public array $onBeforeSuccessRedirectMenuItemForm = [];

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
	 * @var array<mixed>
	 */
	protected array $pageTypes = ['index' => '', 'content' => null, 'contact' => null, 'news' => '', 'pickup_points' => null];
	
	/**
	 * @var array<mixed>
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
			$level = Strings::length($object->path) / 4 - 1;
			$td->setHtml(\str_repeat('- - ', $level) . $td->getHtml());
		};

		$grid->addColumnText('Titulek', 'page.title', '%s', 'page.title_cs');
		$grid->addColumn('URL', function (MenuItem $item) use ($grid) {
			if (!$item->page) {
				return null;
			}

			$url = $this->gridFactory->getPageUrl($grid, $item->page);

			return '<a href="' . $url . '" target="_blank"><i class="fa fa-external-link-square-alt"></i>&nbsp;' . $url . '</a>';
		}, '%s', 'this.url_cs');

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
				$this->getPresenter()->flashMessage('Položku nelze odebrat, protože má pod sebou položky.', 'warning');
				
				return;
			}
			
			$this->onRemove($menuItem);
			
			$page = $menuItem->page;
			$menuItem->update(['page' => null]);
			
			if ($page && !$page->isSystemic()) {
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

		$grid->onDelete[] = function (MenuItem $object): void {
			$this->onDelete($object);
		};

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

		$grid->addColumn('URL', function ($item) use ($grid) {
			$url = $this->gridFactory->getPageUrl($grid, $item);

			return '<a href="' . $url . '" target="_blank"><i class="fa fa-external-link-square-alt"></i>&nbsp;' . $url . '</a>';
		}, '%s', 'this.url_cs');

		$grid->addColumnInputCheckbox('Nedostupná', 'isOffline');

		$grid->addColumn('', function ($object, $datagrid) {
			return $datagrid->getPresenter()->link('linkMenuItemToPage', $object);
		}, "<a class='$btnSecondary' title='Zařadit do menu' href='%s'><i class='fa fa-plus-square mr-1'></i>Zařadit do menu</a>", null,
			['class' => 'minimal']);

		$grid->addColumnLinkDetail('PageDetail');

		$grid->onDelete[] = function (Page $object): void {
			$this->onDelete($object);
		};

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
		$form = $this->formFactory->create(true, true, useShops: true);

		if (\count($form->getMutations()) === 1) {
			$form->addLocaleHidden('active')->forAll(function (HiddenField $hidden): void {
				$hidden->setDefaultValue(true)->addFilter(function ($value) {
					return (bool) $value;
				});
			});
		}

		$form->setPrettyPages(true);

		/** @var \Web\DB\MenuItem|null $menu */
		$menu = $this->getParameter('menuItem');

		$nameInput = $form->addLocaleText('name', 'Název');

		if ($this::CONFIGURATIONS['background']) {
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

		if (isset($this::CONFIGURATIONS['icon']) && $this::CONFIGURATIONS['icon']) {
			$form->addText('icon', $this->_('icon', 'Ikona v menu'))
				->setOption('description', $this->_('iconDescription', 'Vkládejte kód v tomto formátu') . ' <i class="far fa-address-card"></i>')->setNullable();
		}

		if (isset($this::CONFIGURATIONS['iconImage'])) {
			$iconPicker = $form->addImagePicker('iconImage', $this->_('icon', 'Ikona v menu'), [
				MenuItem::IMAGE_DIR => function (Image $image): void {
					$width = $this::CONFIGURATIONS['iconImage']['width'] ?? 32;
					$height = $this::CONFIGURATIONS['iconImage']['height'] ?? 32;
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

		if (isset($this::CONFIGURATIONS['documents']) && $this::CONFIGURATIONS['documents']) {
			/** @phpstan-ignore-next-line */
			$form['page']->addMultiSelect2('documents', $this->_('documents', 'Dokumenty'), $this->documentRepository->many()->toArray());
		}

		$form->addCheckbox('hidden', 'Skryto');

		$params = $menu && $menu->page ? $menu->page->getParsedParameters() : [];
		$type = $menu && $menu->page ? $menu->page->getType() : 'content';

		$form->addPageContainer(
			$type,
			$params,
			$nameInput,
			false,
			true,
			false,
			'URL a SEO',
			true,
			true,
			isset($this::CONFIGURATIONS['richSnippet']) && $this::CONFIGURATIONS['richSnippet'],
			[],
		);

		Arrays::invoke($this->onBeforeSubmitMenuItemForm, $form);

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

			if (isset($this::CONFIGURATIONS['iconImage'])) {
				$this->generateDirectories([MenuItem::IMAGE_DIR]);
				/** @phpstan-ignore-next-line */
				$values['iconImage'] = $form['iconImage']->upload($values['uuid'] . '.%2$s');
			}

			if (isset($this::CONFIGURATIONS['documents']) && $this::CONFIGURATIONS['documents']) {
				$values['page']['page_']['documents'] = $values['documents'];
				unset($values['documents']);
			}

			unset($values['types']);

			if (!$values['page']['page_']['uuid']) {
				$values['page']['page_']['uuid'] = Connection::generateUuid();
				$values['page']['page_']['params'] = 'page=' . $values['page']['page_']['uuid'] . '&';
			}

			if (isset($values['page']['page_']['opengraph'])) {
				$values['page']['page_']['opengraph'] = $form['page']['page_']['opengraph']->upload($values['page']['page_']['uuid'] . '.%2$s');
			}

			if ($this::CONFIGURATIONS['background']) {
				if ($values['image']->isOK()) {
					/** @phpstan-ignore-next-line */
					$values['page']['page_']['image'] = $form['image']->upload($values['image']->getSanitizedName());
				}

				unset($values['image']);

				if ($values['mobileImage']->isOK()) {
					/** @phpstan-ignore-next-line */
					$values['page']['page_']['mobileImage'] = $form['mobileImage']->upload($values['mobileImage']->getSanitizedName());
				}

				unset($values['mobileImage']);
			}

			$values['page']['page_']['content'] = Helpers::sanitizeMutationsStrings($values['content']);
			$values['page']['page_']['name'] = $values['name'];
			$values['page']['page_']['params'] = $values['page']['page_']['params'] ?: '';
			$type = $values['page']['page_']['type'];
			$values['page']['page_']['shop'] = $values['shop'] ?? null;

			foreach ($this->onBeforeSuccessRedirectMenuItemForm as $callback) {
				$callback($form, $values);
			}

			$values['page'] = (string) $this->pageRepository->syncOne($values['page']['page_'], ignore: false);

			$menuItem = $this->menuItemRepository->syncOne($values, null, true, false);

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
			//              if ((Strings::length($path) / 4) + ($this->menuItemRepository->getMaxDeepLevel($menuItem) - (Strings::length($menuItem->path) / 4)) > $type['type']->maxLevel) {
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

			$this->formFactory->cleanPagesCache();
			$this->menuItemRepository->clearMenuCache();

			$this->flashMessage('Uloženo', 'success');
			$form->processRedirect('detail', 'default', [$menuItem]);
		};

		return $form;
	}

	public function createComponentPageForm(): Form
	{
		$form = $this->formFactory->create(true, useShops: true);

		/** @var \Nette\Forms\Controls\SelectBox|null $shopInput */
		$shopInput = $form['shop'] ?? null;

		/** @var \Base\DB\Shop|null $preSelectedShop */
		$preSelectedShop = $this->getParameter('preSelectedShop');

		$shopInput?->setDisabled()->setDefaultValue($preSelectedShop);

		$form->setPrettyPages(true);

		$page = $this->getParameter('page');

		$inputName = $form->addLocaleText('name', 'Název')->forPrimary(function ($input): void {
			$input->setRequired();
		});

		if ($this::CONFIGURATIONS['background']) {
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

		if (isset($this::CONFIGURATIONS['documents']) && $this::CONFIGURATIONS['documents']) {
			/** @phpstan-ignore-next-line */
			$form['page']->addMultiSelect2('documents', $this->_('documents', 'Dokumenty'), $this->documentRepository->many()->toArray());
		}

		$form->addLocaleRichEdit('content', 'Obsah');

		/** @var \Web\DB\Page|null $page */
		$page = $this->getParameter('page');

		$shops = $page?->shop ? [$page->shop] : ($preSelectedShop ? [$preSelectedShop] : null);

		$form->addPageContainer(
			$page ? $page->type : 'content',
			$this->getPageParamsInPageFormForPageContainer($page),
			$inputName,
			false,
			true,
			false,
			'URL a SEO',
			false,
			true,
			isset($this::CONFIGURATIONS['richSnippet']) && $this::CONFIGURATIONS['richSnippet'],
			shops: $shops,
		);

		Arrays::invoke($this->onBeforeSubmitMenuItemForm, $form);

		$form->addSubmits(!$this->getParameter('page'));

		$form->onSuccess[] = function (AdminForm $form): void {
			$values = $form->getValues('array');

			$pages = [];

			$defaultMutation = $this->connection->getMutation();

			$form->syncPages(function (array $pageValues, Shop|null $shop, string $containerIndex) use (&$pages, $form, $values, $defaultMutation): void {
				if (!isset($pageValues['url'][$defaultMutation])) {
					if (isset($pageValues['uuid'])) {
						$this->pageRepository->many()->where('uuid', $pageValues['uuid'])->delete();
					}

					return;
				}

				if (!isset($pageValues['uuid'])) {
					$pageValues['params'] = \Pages\Helpers::serializeParameters(['page' => $pageValues['uuid']]);
				}

				$pageValues['uuid'] ??= DIConnection::generateUuid();

				if ($this::CONFIGURATIONS['background']) {
					if ($pageValues['image']->isOK()) {
						$pageValues['image'] = $form['page'][$containerIndex]['image']->upload($pageValues['image']->getSanitizedName());
					} else {
						unset($pageValues['image']);
					}

					if ($pageValues['mobileImage']->isOK()) {
						$pageValues['mobileImage'] = $form['page'][$containerIndex]['mobileImage']->upload($pageValues['mobileImage']->getSanitizedName());
					} else {
						unset($pageValues['mobileImage']);
					}
				}

				$pageValues['name'] = $values['name'];
				$pageValues['content'] = Helpers::sanitizeMutationsStrings($values['content']);

				$form->uploadOpenGraphImage($form, $pageValues, $shop);

				$pages[] = $this->pageRepository->syncOne($pageValues);
			});

			foreach ($this->onBeforeSuccessRedirectMenuItemForm as $callback) {
				$callback($form, $values);
			}

			$this->menuItemRepository->clearMenuCache();
			$this->formFactory->cleanPagesCache();

			$this->flashMessage('Uloženo', 'success');
			$form->processRedirect('pageDetail', 'default', [Arrays::first($pages)]);
		};

		return $form;
	}

	public function createComponentMenuForm(): AdminForm
	{
		$form = $this->formFactory->create(true, true);

		if (\count($form->getMutations()) === 1) {
			$form->addLocaleHidden('active')->forAll(function (HiddenField $hidden): void {
				$hidden->setDefaultValue(true)->addFilter(function ($value) {
					return (bool) $value;
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
		/** @phpstan-ignore-next-line */
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

			$shops = $this->shopsConfig->getAvailableShops();

			foreach ($shops as $shop) {
				$this->template->displayButtons[] = $this->createNewItemButton('newPage', ['preSelectedShop' => $shop], "Nová položka ($shop->name)");
			}

			if (!$shops) {
				$this->template->displayButtons = [$this->createNewItemButton('newPage')];
			}
		} else {
			$this->template->displayControls = [$this->getComponent('grid')];
			$this->template->displayButtons = [$this->createNewItemButton('new')];
		}

		$this->template->tabs = [];

		$menuTypes = $this->menuTypeRepository->getCollection();

		foreach ($menuTypes->toArrayOf('name') as $type => $label) {
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

	public function renderNewPage(Shop|null $preSelectedShop = null): void
	{
		unset($preSelectedShop);

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
		$defaults['shop'] = $menuItem->page?->shop?->getPK();

		foreach (\array_keys($this->menuItemRepository->getConnection()->getAvailableMutations()) as $mutation) {
			$defaults['active'][$mutation] = $defaults['active'][$mutation] ? '1' : '0';
		}

		$form->setDefaults($defaults);
		/** @phpstan-ignore-next-line */
		$form['content']->setDefaults($defaults['page']['content'] ?? []);

		if (!$this::CONFIGURATIONS['background']) {
			return;
		}

		/** @phpstan-ignore-next-line */
		$form['image']->setDefaultValue($menuItem->page->image ?? null);
		/** @phpstan-ignore-next-line */
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
			/** @phpstan-ignore-next-line */
			->where('assign.path LIKE :path', ['path' => "$menuItem->path%"])
			->delete();

		$this->menuItemRepository->clearMenuCache();
	}

	/**
	 * @param array<mixed> $content Array with mutations as keys
	 * @return array<mixed>
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
