<?php

declare(strict_types=1);

namespace Web;

use Nette\Application\UI\Presenter;
use Nette\Application\UI\Template;
use Nette\Caching\Cache;
use Nette\DI\Attributes\Inject;
use Nette\Http\Request;
use Nette\Utils\Arrays;
use Nette\Utils\Strings;
use Pages\DB\Page;
use Pages\Pages;

abstract class TemplateFactory extends \Base\TemplateFactory
{
	#[Inject]
	public Pages $pages;

	#[Inject]
	public Request $request;
	
	public function setTemplateParameters(Template $template): void
	{
		$this->setGlobalParameters($template);

		if ($template instanceof \Nette\Bridges\ApplicationLatte\Template) {
			$template->setTranslator($this->translator);
		}

		if (!isset($template->control) || !($template->control instanceof Presenter)) {
			return;
		}

		[$module] = \Nette\Application\Helpers::splitName((string) $template->control->getName());

		Strings::substring($module, -5) !== 'Admin' ? $this->setFrontendPresenterParameters($template) : $this->setBackendPresenterParameters($template);
	}

	/**
	 * @param array<string> $additionalParameters
	 */
	public function getUtmCanonicalUrl(array $additionalParameters = []): ?string
	{
		if ($this->request->isAjax() || (!$this->request->isMethod('GET') && !$this->request->isMethod('HEAD'))) {
			return null;
		}

		$utm = [];
		$loweredAdditionalParameters = [];

		foreach ($additionalParameters as $key => $value) {
			$loweredAdditionalParameters[Strings::lower($value)] = $value;
		}

		foreach ($params = $this->request->getQuery() as $name => $value) {
			$name = Strings::lower($name);

			if (!\str_starts_with($name, 'utm_') &&
				!\str_starts_with('a_box', $name) &&
				!\str_starts_with('fbclid', $name) &&
				!\str_starts_with('ehub', $name) &&
				!isset($loweredAdditionalParameters[$name])
			) {
				continue;
			}

			if (isset($loweredAdditionalParameters[$name])) {
				$name = $loweredAdditionalParameters[$name];
			}

			unset($params[$name]);
			$utm[$name] = $value;
		}

		$url = clone $this->request->getUrl();

		return $utm ? $url->withQuery($params)->getAbsoluteUrl() : $url->getAbsoluteUrl();
	}

	protected function setFrontendPresenterParameters(Template $template): void
	{
		$page = $this->pages->getPage();
		
		$template->pages = $this->pages;
		$template->page = $this->pages->getPage();
		$template->lang = $template->control->lang ?? null;
		$template->langs = $this->mutations;
		$template->ts = $this->application->getEnvironment() === 'production' ? (new Cache($this->storage))->call('time') : \time();
		$template->shop = $this->shopsConfig->getSelectedShop();
		
		if ($page !== null && !($page instanceof Page)) {
			return;
		}

		$template->headTitle = $page ? ($page->getType() === 'index' ? $page->title : $page->title . ' | ' . $this->getBaseTitle()) : $this->getBaseTitle();
		$template->headDescription = $page ? $page->description : null;
		$template->headCanonical = $page ? $page->canonicalUrl : null;
		$template->headRobots = $this->application->getEnvironment() === 'production' ? ($page && $page->robots ? $page->robots : 'index, follow') : 'noindex, nofollow';
	}
}
