<form method="post">
	<?php if ($permissionLimitNotification) { ?>
		<p class="warning">Nie posiadasz wystarczających uprawnień, by modyfikować zawartość lub ustawienia tej strony i&nbsp;jej&nbsp;właściwości, nie będąc jej właścicielem.</p>
	<?php } ?>

	<h3>Dane strony</h3>

	<?= (new HTMLFormFields)
		->text('Tytuł', 'nofilter_title', HTML::escape($page->title), ['required'=>true])
		->text('Identyfikator', 'nofilter_slug', HTML::escape($page->slug))
		->{$hideUserIdChange ? 'skip' : 'select'}('Właściciel', 'userId', $page->userId, $usersIdList,
			['disabled' => $disableUserIdChange])
		->option('Strona dostępna publicznie', 'isDraft', '0', $page->isDraft)
		->option('Szkic strony niewidoczny publicznie', 'isDraft', '1', $page->isDraft)
	?>

	<dl>
		<dt>Data modyfikacji</dt><dd><?= HTML::formatDateTime($page->updatedTime) ?></dd>
		<dt>Data utworzenia</dt><dd><?= HTML::formatDateTime($page->createdTime) ?></dd>
	</dl>

	<p class="information">Identyfikator to uproszczona nazwa widoczna w&nbsp;adresie strony w&nbsp;pasku adresu przeglądarki. Nie może zawierać spacji, polskich znaków i&nbsp;niestandardowych symboli.</p>

	<h3>Informacje wyszukiwarek</h3>

	<?= (new HTMLFormFields)
		->textarea('Opis', 'description', $page->description, ['maxlength' => 300, 'spellcheck' => true])
		->checkbox('Proś wyszukiwarki, by nie indeksowały zawartości tej strony', 'noIndex', $page->noIndex)
	?>

	<button <?= $disableSaveButton ? 'disabled' : '' ?>>Zapisz zmiany</button>
</form>