<?php
if ($locales):
	$currentLocale = Zend_Registry::get('bootstrap')->getResource('Locale')->toString();
	$request = Zend_Controller_Front::getInstance()->getRequest();
	$currentUrl = $request->getRequestUri();
	$query = array();
	// Append the record for managed plugins in public front-end.
	if (!is_admin_theme()):
		$module = $request->getModuleName();
		switch ($module):
			case 'exhibit-builder':
				$action = $request->getActionName();
				switch ($action):
					case 'summary':
						$query['record_type'] = 'Exhibit';
						$exhibit = get_current_record('exhibit');
						$query['id'] = $exhibit->id;
						break;
					case 'show':
						$query['record_type'] = 'ExhibitPage';
						$exhibitPage = get_current_record('exhibit_page');
						$query['id'] = $exhibitPage->id;
						break;
				endswitch;
				break;
			case 'simple-pages':
				$query['record_type'] = 'SimplePagesPage';
				$query['id'] = $request->getParam('id');
				break;
		endswitch;
	endif;
	?>
	<ul class="locale-switcher">
		<?php 
			$displayMode = get_option('multilanguage_display_mode');
			foreach ($locales as $locale):
				$country = $this->localeToCountry($locale);
				switch ($displayMode) {
					case "code":
						$content = "<span class=\"active\">[" . strtolower($country) . "]</span>";
						break;
					case "name":
						$content = "<span class=\"active\">" . locale_human($locale, true) . "</span>";
						break;
					default:
						$content = "<span class=\"active flag-icon flag-icon-" . strtolower($country) . "\"></span>";
				}
		?>
			<li>
				<?php if ($currentLocale == $locale): ?>
					<?php echo $content; ?>
				<?php else: ?>
					<?php $language = Zend_Locale::getTranslation(substr($locale, 0, 2), 'language'); ?>
					<?php $url = url('setlocale', array('locale' => $locale, 'redirect' => $currentUrl) + $query); ?>
					<a style="border-bottom: 0" href="<?php echo $url ; ?>" title="<?php echo locale_human($locale); ?>">
						<?php echo $content; ?>
					</a>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ul>
<?php endif; ?>
