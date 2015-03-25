<h1 class="page_title">
	<?php echo $title_for_layout; ?>
</h1>

<?php
	echo $this->Form->create();
	echo $this->Form->input('founded');
	echo $this->Form->input('square_miles');
	echo $this->Tinymce->input('County.description',
		array(
			'label' => 'Description'
		),
		array(
			'language' => 'en',
			'theme_advanced_buttons1' => 'bold,italic,underline,separator,link,unlink,separator,undo,redo,cleanup,code',
			'theme_advanced_statusbar_location' => 'none',
			'valid_elements' => 'p,br,a[href|target=_blank],strong/b,i/em,u,img[src|style|alt|title]',
			'width' => 500,

			/* These three prevent links to other pages on this same domain
			 * from being converted to relative URLs. */
			'relative_urls' => false,
			'remove_script_host' => false,
			'convert_urls' => false
		)
	);

	echo $this->Form->end('Update');