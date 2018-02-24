<form method="post">
	<h3>Dane użytkownika</h3>

	<?= (new HTMLFormFields)
		->text('Nazwa użytkownika', 'name', $createInsteadEdit ? '' : $user->name,
			['required' => $createInsteadEdit, 'pattern' => '[a-zA-Z0-9_\-.]*', 'title' => 'Dozwolone znaki: litery, cyfry, minus, kropka, podkreślnik.']
		)
		->email('Adres e-mail', 'email', $createInsteadEdit ? '' : $user->email)
	?>

	<?php if (!$createInsteadEdit) { ?>
		<dl>
			<dt>Data ostatniego zalogowania</dt><dd><?= $user->lastLoginTime ? HTML::formatDateTime($user->lastLoginTime) : 'nigdy' ?></dd>
			<dt>Data utworzenia</dt><dd><?= HTML::formatDateTime($user->createdTime) ?></dd>
		</dl>
	<?php } ?>

	<p class="information">W nazwie użytkownika dozwolone są wyłącznie litery (bez polskich znaków diakrytycznych), cyfry, minus, kropka i podkreślnik. Wielkość liter ma znaczenie.</p>

	<h3>Hasło</h3>

	<?= (new HTMLFormFields)
		->password('Hasło', 'nofilter_passwordText_1', ['required' => $createInsteadEdit])
		->password('Ponownie hasło', 'nofilter_passwordText_2', ['required' => $createInsteadEdit])
	?>

	<?php if (!$createInsteadEdit) { ?>
		<p class="information">Pozostaw powyższe pola puste, by nie zmieniać hasła.</p>
	<?php } ?>

	<h3>Uprawnienia</h3>

	<?= (new HTMLFormFields)
		->checkbox('Tworzenie szkiców stron oraz edycja stron i szkiców należących do użytkownika',
			'permissions[CREATE_PAGES]',     $permissions['CREATE_PAGES'])
		->checkbox('Publikowanie tych szkiców i ukrywanie tych stron, które użytkownik może edytować',
			'permissions[PUBLISH_PAGES]',    $permissions['PUBLISH_PAGES'])
		->checkbox('Edycja wszystkich istniejących stron i szkiców oraz zmiana właścicieli stron i szkiców',
			'permissions[EDIT_PAGES]',       $permissions['EDIT_PAGES'])
		->checkbox('Wysyłanie plików i zarządzanie wszystkimi wysłanymi plikami',
			'permissions[MANAGE_FILES]',     $permissions['MANAGE_FILES'])
		->checkbox('Modyfikacja elementów witryny — obszarów i menu',
			'permissions[WEBSITE_ELEMENTS]', $permissions['WEBSITE_ELEMENTS'])
		->checkbox('Modyfikacja konfiguracji witryny — ustawienia, personalizacja, informacje wyszukiwarek',
			'permissions[WEBSITE_SETTINGS]', $permissions['WEBSITE_SETTINGS'])
		->checkbox('<b>Superużytkownik</b>: zarządzanie użytkownikami, dostęp do edytora plików i kopii zapasowej',
			'permissions[SUPER_USER]',       $permissions['SUPER_USER'])
	?>
	<button>Zapisz zmiany</button>
</form>