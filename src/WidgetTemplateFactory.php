<?php

declare(strict_types=1);

namespace Web;

use Latte\CompileException;
use Latte\Engine;
use Latte\Loaders\StringLoader;
use Latte\Sandbox\SecurityPolicy;
use Nette\Application\UI\Component;
use Nette\Application\UI\Template;
use Nette\Bridges\ApplicationLatte\LatteFactory;
use Nette\Bridges\ApplicationLatte\UIExtension;
use Nette\Bridges\ApplicationLatte\UIMacros;

abstract class WidgetTemplateFactory extends TemplateFactory
{
	/** @inject */
	public LatteFactory $latteFactory;
	
	private Engine $latteForWidgets;
	
	public function addFilters(Template $template): void
	{
		parent::addFilters($template);
		
		if (!$template instanceof \Nette\Bridges\ApplicationLatte\Template) {
			return;
		}
		
		$template->addFilter('parseWidgets', function ($string) use ($template) {
			if ($string === null) {
				return '';
			}
			
			if (!isset($template->presenter) || !$template->presenter->getComponent('widget', false)) {
				return $string;
			}
			
			try {
				return $this->getLatteForWidgets($template->presenter->getComponent('widget'))->renderToString($string, []);
			} catch (CompileException $x) {
				return $string;
			}
		});
	}
	
	private function getLatteForWidgets(Component $rootControl): Engine
	{
		if (isset($this->latteForWidgets)) {
			return $this->latteForWidgets;
		}
		
		$policy = SecurityPolicy::createSafePolicy();

		/** @phpstan-ignore-next-line @TODO LATTEV3 */
		if (\version_compare(\Latte\Engine::VERSION, '3', '<')) {
			/** @phpstan-ignore-next-line @TODO LATTEV3 */
			$policy->allowMacros(['control']);
		} else {
			$policy->allowTags(['control']);
		}
		
		$latte = $this->latteFactory->create();

		/** @phpstan-ignore-next-line @TODO LATTEV3 */
		if (\version_compare(\Latte\Engine::VERSION, '3', '<')) {
			/** @phpstan-ignore-next-line @TODO LATTEV3 */
			UIMacros::install($latte->getCompiler());
		} else {
			$latte->addExtension(new UIExtension(null));
		}

		$latte->addProvider('uiControl', $rootControl);

		$latte->setLoader(new StringLoader());
		$latte->setPolicy($policy);
		$latte->setSandboxMode();
		
		return $this->latteForWidgets = $latte;
	}
}
