<?php

namespace Web;

class Helpers
{
	/**
	 * Replace { and } with HTML code unless {control ...}
	 * @param array $content Array with mutations as keys
	 * @return array Sanitized strings
	 */
	public static function sanitizeMutationsStrings(array $content): array
	{
		//@TODO konfigurace, univerzální mazání
		// $states = ['normal', 'check-control', 'control'];
		$toDeleteStrings = [];

		foreach ($content as $mutation => $string) {
			if ($string == null) {
				continue;
			}

			foreach ($toDeleteStrings as $toDeleteString => $toReplaceString) {
				$content[$mutation] = $string = \str_replace($toDeleteString, $toReplaceString, $string);
			}

			$state = 'normal';
			$substr = '';
			$pos = -1;
			$offset = 0;
			$length = \strlen($string);

			for ($i = 0; $i < $length; $i++) {
				$char = $string[$i];

				if ($state == 'check-control') {
					$substr .= $char;

					if ($substr === 'control') {
						$state = 'control';
						$substr = '';
						$pos = -1;
						continue;
					}

					if (\strlen($substr) > 7 || (\strlen($substr) > 0 && \stripos('control', $substr) === false)) {
						$content[$mutation] = \substr_replace($content[$mutation], '', $pos + $offset, 1);
						$content[$mutation] = \substr_replace($content[$mutation], '&#123;', $pos + $offset, 0);
						$offset += 5;

						$state = 'normal';
						$substr = '';
						$pos = -1;
					}
				}

				if ($state == 'control') {
					if ($char === '}') {
						$state = 'normal';
						continue;
					}
				}

				if ($char === '{') {
					$pos = $i;
					$state = 'check-control';
				}

				if ($state == 'normal' && $char === '}') {
					$content[$mutation] = \substr_replace($content[$mutation], '', $i + $offset, 1);
					$content[$mutation] = \substr_replace($content[$mutation], '&#125;', $i + $offset, 0);
					$offset += 5;
				}
			}

//			$content[$mutation] = \preg_replace('/({(?!control))/i','&#123;', $string);
//			$content[$mutation] = \preg_replace('/((?<!control)})/i','&#125;', $content[$mutation]);
		}

		return $content;
	}
	
	public static function codeReviewTest()
	{
		if (true) {
			return true;
		} else {
			return false;
		}
	}
}