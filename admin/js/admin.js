jQuery(document).ready(function($) {
  let editor;
  let currentFile = '';
  let currentDirectory = '';
  const folderSelect = $('#folder-select');
  const jsonFileList = $('#json-file-list');
  const jsonEditorContainer = $('.json-editor-container');

  function initAceEditor() {
    const editorElement = document.getElementById("ace-editor");
    if (typeof ace !== 'undefined' && editorElement) {
      editor = ace.edit(editorElement, {
        theme: "ace/theme/github_dark",
        mode: "ace/mode/json",
        fontSize: "14px",
        tabSize: 2,
        useSoftTabs: true,
				enableAutoIndent: true,
				showPrintMargin: false,
				wrap: 160
      });
    } else {
      console.error('Ace editor not loaded or target element not found');
    }
  }

  function ajaxRequest(action, data, successCallback, errorCallback) {
    $.ajax({
      url: wpJsonEditor.ajax_url,
      type: 'POST',
      data: Object.assign({ action, nonce: wpJsonEditor.nonce }, data),
      success: function(response) {
        if (response.success) {
          successCallback(response.data);
        } else {
          console.error(`Erreur lors de ${action}:`, response.data);
          if (errorCallback) errorCallback(response.data);
        }
      },
      error: function(jqXHR, textStatus, errorThrown) {
        console.error(`Erreur AJAX lors de ${action}:`, textStatus, errorThrown);
        if (errorCallback) errorCallback(errorThrown);
      }
    });
  }

  function loadFolders() {
    ajaxRequest('get_theme_directories', {}, function(directories) {
      folderSelect.empty().append($('<option>', {
        value: '',
        text: '-- Select a folder --'
      }));
      directories.forEach(function(dir) {
        folderSelect.append($('<option>', {
          value: dir,
          text: dir || '/ (racine du thème)'
        }));
      });
    });
	}
	
	function getSvgIcon(iconName) {
		return wpJsonEditor.svgIcons[iconName] || '';
	}

 function loadJsonFiles(directory) {
		ajaxRequest('get_json_files', { directory }, function(files) {
			jsonFileList.empty();
			if (files.length > 0) {
				const ul = $('<ul>');
				files.forEach(function(file) {
					const li = $('<li class="json-file-item">');
					const link = $('<a>', {
						href: '#',
						html: getSvgIcon('file-code') + file,
						click: function(e) {
							e.preventDefault();
							// Retirer la classe is-active de tous les éléments
							jsonFileList.find('a').removeClass('is-active');
							// Ajouter la classe is-active à l'élément cliqué
							$(this).addClass('is-active');
							loadJsonFile(directory, file);
						}
					});
					li.append(link);
					ul.append(li);
				});
				jsonFileList.append(ul);
			} else {
				jsonFileList.append('<p>No JSON files found in this folder.</p>');
			}
		});
	}

	function loadJsonFile(directory, file) {
		ajaxRequest('load_json_file', { directory, file }, function(data) {
			currentFile = file;
			currentDirectory = directory;
			editor.setValue(data.content);
			editor.clearSelection();
			jsonEditorContainer.show();
		});
	}

  function saveJsonFile() {
    ajaxRequest('save_json_file', {
      directory: currentDirectory,
      file: currentFile,
      content: editor.getValue()
    }, function() {
      alert('File saved successfully');
    }, function() {
      alert('Error saving file');
    });
  }

  // Initialisation
  initAceEditor();
  loadFolders();

  // Event listeners
  folderSelect.on('change', function() {
    const selectedDirectory = $(this).val();
    if (selectedDirectory) {
      loadJsonFiles(selectedDirectory);
    }
  });

  $('#save-json').on('click', saveJsonFile);
});