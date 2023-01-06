<?php

require __DIR__ . '/vendor/autoload.php';

$engine = new Latte\Engine;
$engine->addExtension(new \Nette\Bridges\ApplicationLatte\UIExtension(null));
$engine->addExtension(new \Nette\Bridges\FormsLatte\FormsExtension());

$linter = new Latte\Tools\Linter($engine);
$ok = $linter->scanDirectory('src/Controls');
exit($ok ? 0 : 1);